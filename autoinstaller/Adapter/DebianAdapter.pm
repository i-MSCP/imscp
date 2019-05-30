=head1 NAME

 autoinstaller::Adapter::DebianAdapter - Debian installer adapter

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

package autoinstaller::Adapter::DebianAdapter;

use strict;
use warnings;
use autouse 'iMSCP::Stepper' => qw/ startDetail endDetail step /;
use autoinstaller::Functions qw/
    showWelcomeDialog showGitVersionWarnDialog distributionCheckDialog
    buildDistFiles writeMasterConfigFile
/;
use Class::Autouse qw/ :nostat File::HomeDir /;
use File::Basename 'dirname';
use File::Temp;
use FindBin;
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Cwd '$CWD';
use iMSCP::Debug qw/ debug error output /;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::LsbRelease;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use version;
use parent 'autoinstaller::Adapter::AbstractAdapter';

=head1 DESCRIPTION

 Debian installer adapter

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 See autoinstaller::Adapter::AbstractAdapter::preinstall()

=cut

sub preinstall
{
    my ( $self ) = @_;

    if ( !iMSCP::Getopt->preseed
        && !( length $::imscpConfig{'FRONTEND_SERVER'}
        && length $::imscpConfig{'FTPD_SERVER'}
        && length $::imscpConfig{'HTTPD_SERVER'}
        && length $::imscpConfig{'NAMED_SERVER'}
        && length $::imscpConfig{'MTA_SERVER'}
        && length $::imscpConfig{'PHP_SERVER'}
        && length $::imscpConfig{'PO_SERVER'}
        && length $::imscpConfig{'SQL_SERVER'}
    ) ) {
        iMSCP::Getopt->noprompt( FALSE );
        $::skippackages = FALSE;
    }

    unless ( $::skippackages ) {
        unless ( iMSCP::ProgramFinder::find( 'debconf-apt-progress' ) ) {
            print STDOUT output(
                'Satisfying prerequisites... Please wait.', 'info'
            );
        }

        my $rs = $self->_updateAptSourceList();
        $rs ||= $self->_updatePackagesIndex();
        return $rs if $rs;

        # Make sure that the distribution is up-to-date, else inform the user
        # and abort.
        $rs = execute(
            "/usr/bin/apt-get --simulate --assume-yes dist-upgrade | grep '^[[:digit:]]\\+ upgraded'",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if length $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        if ( length $stdout && $stdout =~ /^(\d+)\s+upgraded/m && $1 > 0 ) {
            if ( !iMSCP::Getopt->noprompt
                && iMSCP::ProgramFinder::find( 'dialog' )
            ) {
                iMSCP::Dialog->getInstance()->error( <<"EOF" );
\\Zb\\Z1The distribution is not up-to-date\\Zn

There are distribution packages available for update.

Please update your distribution before running the i-MSCP installer.
EOF
            } elsif ( iMSCP::Getopt->verbose ) {
                print STDERR output( 'Your distribution is not up-to date. You need first update it.', 'error' );
            }

            exit 1;
        }

        local @ENV{qw/ UCF_FORCE_CONFFNEW UCF_FORCE_CONFFMISS /} = (
            TRUE, TRUE
        );

        $rs = execute(
            [
                ( !iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find(
                    'debconf-apt-progress'
                ) ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
                'apt-get',
                '--assume-yes',
                '--option', 'DPkg::Options::=--force-confnew',
                '--option', 'DPkg::Options::=--force-confmiss',
                '--auto-remove',
                '--purge',
                '--no-install-recommends',
                'install', @{ delete $self->{'preRequiredPackages'} }
            ],
            ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                ? \$stdout : undef
            ),
            \$stderr
        );
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    };

    my $dialog = iMSCP::Dialog->getInstance();

    my $rs = $dialog->execute( [
        \&showWelcomeDialog,
        \&showGitVersionWarnDialog,
        \&distributionCheckDialog,
        $self->_getPackagesDialog( @_ )
    ] );
    return $rs if $rs;

    my @steps = (
        [
            sub { $self->_processPackagesFile() },
            'Process packages file'
        ],
        [
            sub { $self->_prefillDebconfDatabase() },
            'Pre-fill Debconf database'
        ],
        [
            sub { $self->_processAptRepositories() },
            'Processing APT repositories'
        ],
        [
            sub { $self->_processAptPreferences() },
            'Processing APT preferences'
        ],
        [
            sub { $self->_updatePackagesIndex() },
            'Updating packages index'
        ],
        [
            sub { $self->_installPackages() },
            'Installing required packages'
        ]
    );

    push @steps,
        [ \&buildDistFiles, 'Building distribution files' ],
        [ \&writeMasterConfigFile, 'Writing master configuration file' ];

    my ( $step, $nSteps ) = ( 1, scalar @steps );
    for my $task ( @steps ) {
        $rs = step( @{ $task }, $nSteps, $step );
        error( 'An error occurred while pre-installation steps.' ) if $rs;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    0;
}

=item install( )

 See autoinstaller::Adapter::AbstractAdapter::install()

=cut

sub install
{
    my ( $self ) = @_;

    require "$FindBin::Bin/engine/setup/imscp-setup-functions.pl";

    # Not really the right place to do that job but we have not really choice
    # because this must be done before installation of new files
    my $service = iMSCP::Service->getInstance();
    if ( $service->hasService( 'imscp_network' ) ) {
        $service->remove( 'imscp_network' );
    }

    my $bootstrapper = iMSCP::Bootstrapper->getInstance();
    my @runningJobs = ();

    for my $job ( qw/
        imscp-backup-all imscp-backup-imscp imscp-dsk-quota imscp-srv-traff
        imscp-vrl-traff awstats_updateall.pl imscp-disable-accounts imscp
    / ) {
        next if $bootstrapper->lock( "/var/lock/$job.lock", 'nowait' );
        push @runningJobs, $job,
    }

    if ( @runningJobs ) {
        iMSCP::Dialog->getInstance()->error( <<"EOF" );
There are jobs currently running on your system that can not be locked by the installer.

You must wait until the end of these jobs.

Running jobs are: @runningJobs
EOF
        return 1;
    }

    undef @runningJobs;

    my @steps = (
        [ \&::setupInstallFiles, 'Installing distribution files' ],
        [ \&::setupBoot, 'Bootstrapping installer' ],
        [ \&::setupRegisterListeners, 'Registering event listeners' ],
        [ \&::setupDialog, 'Processing setup dialog' ],
        [ \&::setupTasks, 'Processing setup tasks' ]
    );

    my ( $step, $nSteps ) = ( 1, scalar @steps );
    for my $task ( @steps ) {
        my $rs = step( @{ $task }, $nSteps, $step );
        error( 'An error occurred while installation steps.' ) if $rs;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    0;
}

=item postinstall( )

 See autoinstaller::Adapter::AbstractAdapter::postinstall()

=cut

sub postinstall
{
    my ( $self ) = @_;

    # Delete distribution files directory
    eval { iMSCP::Dir->new( dirname => $::{'INST_PREF'} )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS/FUNCTIONS

=over 4

=item _init( )

 Initialize instance

 Return autoinstaller::Adapter::DebianAdapter

=cut

sub _init
{
    my ( $self ) = @_;

    @{ $self }{qw/
        eventManager repositorySections preRequiredPackages
        need_pbuilder_update
    /} = (
        iMSCP::EventManager->getInstance(),
        [ 'main', 'contrib', 'non-free' ],
        [
            'apt-transport-https', 'binutils', 'ca-certificates',
            'debconf-utils', 'dialog', 'dirmngr', 'dpkg-dev', 'gnupg',
            'libbit-vector-perl', 'libclass-insideout-perl', 'libclone-perl',
            'liblchown-perl', 'liblist-moreutils-perl', 'libscalar-defer-perl',
            'libsort-versions-perl', 'libxml-simple-perl', 'lsb-release',
            'policyrcd-script-zg2', 'wget'
        ],
        TRUE
    );

    delete $ENV{'DEBCONF_FORCE_DIALOG'};
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->noprompt;
    @{ENV}{qw/ DEBFULLNAME DEBEMAIL /} = ( 'i-MSCP Installer', 'team@i-mscp.net' );

    $self->_setupGetAddrInfoPrecedence();
    $self;
}

=item _installPackages( )

 Install Debian packages

 Return int 0 on success, other on failure

=cut

sub _installPackages
{
    my ( $self ) = @_;

    # See https://people.debian.org/~hmh/invokerc.d-policyrc.d-specification.txt
    my $policyrcd = File::Temp->new();

    # Prevents INVOKE-RC.D(8) to start managed services
    print $policyrcd <<"EOF";
#!/bin/sh

initscript=\$1
action=\$2

if [ "\$action" = "start" ] || [ "\$action" = "restart" ]; then
  for i in `cat @{ [ dirname( __FILE__ ) ] }/managed_services.txt`; do
    if [ "\$initscript" = "\$i" ]; then
      exit 101;
    fi
  done
fi
EOF
    $policyrcd->close();
    chmod( 0750, $policyrcd->filename()) or die( sprintf(
        "Couldn't change permissions on %s: %s", $policyrcd->filename(), $!
    ));

    # See ZG-POLICY-RC.D(8)
    local $ENV{'POLICYRCD'} = $policyrcd->filename();

    my $rs = $self->_uninstallPackages(
        $self->{'_dist'}->{'packagesToPreUninstall'}
    );
    $rs ||= $self->{'eventManager'}->trigger(
        'beforeInstallPackages',
        $self->{'_dist'}->{'packagesToInstall'},
        $self->{'_dist'}->{'packagesToInstallDelayed'}
    );
    return $rs if $rs;

    my $nPackages = scalar keys %{ $self->{'_dist'}->{'packagesPreInstallTasks'} };
    my $cPackage = 1;

    startDetail();

    {
        local $CWD = "$FindBin::Bin/autoinstaller/preinstall";

        for my $package ( sort keys %{
            $self->{'_dist'}->{'packagesPreInstallTasks'}
            } ) {
            $rs ||= step(
                sub {
                    my $stdout;
                    $rs = execute(
                        $self->{'_dist'}->{'packagesPreInstallTasks'}->{$package},
                        ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                            ? undef : \$stdout
                        ),
                        \my $stderr
                    );
                    error( $stderr || 'Unknown' ) if $rs;
                    $rs;
                },
                sprintf(
                    "Executing pre-installation tasks for the '%s' package.",
                    $package
                ),
                $nPackages,
                $cPackage
            );
            last if $rs;
            $cPackage++;
        }
    }

    endDetail();
    return $rs if $rs;

    # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
    execute(
        [
            '/usr/bin/apt-mark',
            'unhold',
            @{ $self->{'_dist'}->{'packagesToInstall'} },
            @{ $self->{'_dist'}->{'packagesToInstallDelayed'} }
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stderr ) if $stderr;

    {
        iMSCP::Dialog->getInstance()->endGauge();

        local $ENV{'UCF_FORCE_CONFFNEW'} = TRUE;
        local $ENV{'UCF_FORCE_CONFFMISS'} = TRUE;

        my @cmd = (
            ( !iMSCP::Getopt->noprompt
                ? ( 'debconf-apt-progress', '--logstderr', '--' ) : ()
            ),
            '/usr/bin/apt-get',
            '--assume-yes',
            '--option', 'DPkg::Options::=--force-confnew',
            '--option', 'DPkg::Options::=--force-confmiss',
            '--option', 'Dpkg::Options::=--force-overwrite',
            '--auto-remove',
            '--purge',
            '--no-install-recommends',
            ( version->parse(
                `/usr/bin/apt-get --version` =~ /^apt\s+(\d\.\d)/
            ) < version->parse( '1.1' )
                ? '--force-yes' : '--allow-downgrades'
            ),
            'install'
        );

        for my $packages ( $self->{'_dist'}->{'packagesToInstall'},
            $self->{'_dist'}->{'packagesToInstallDelayed'}
        ) {
            next unless @{ $packages };
            $rs = execute(
                [ @cmd, @{ $packages } ],
                ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                    ? \$stdout : undef
                ),
                \$stderr
            );
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }
    }

    $nPackages = scalar keys %{ $self->{'_dist'}->{'packagesPostInstallTasks'} };
    $cPackage = 1;

    startDetail();

    {
        local $CWD = "$FindBin::Bin/autoinstaller/postinstall";

        for my $package (
            sort keys %{ $self->{'_dist'}->{'packagesPostInstallTasks'} }
        ) {
            $rs ||= step(
                sub {
                    $rs = execute(
                        $self->{'_dist'}->{'packagesPostInstallTasks'}->{$package},
                        ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                            ? undef : \$stdout
                        ),
                        \$stderr
                    );
                    error( $stderr || 'Unknown' ) if $rs;
                    $rs;
                },
                sprintf(
                    "Executing post-installation tasks for the '%s' package.",
                    $package
                ),
                $nPackages,
                $cPackage
            );
            last if $rs;
            $cPackage++;
        }
    }

    endDetail();
    return $rs if $rs;

    while ( my ( $package, $metadata ) = each(
        %{ $self->{'_dist'}->{'packagesToRebuild'} }
    ) ) {
        $rs = $self->_rebuildAndInstallPackage(
            $package,
            $metadata->{'pkg_src_name'},
            $metadata->{'patches_directory'},
            $metadata->{'discard_patches'},
            $metadata->{'patch_sys_type'}
        );
        return $rs if $rs;
    }

    $rs = $self->{'eventManager'}->trigger( 'afterInstallPackages' );
    $rs || $self->_uninstallPackages();
}

=item _uninstallPackages( [ \@packagesToUninstall = $self->{'_dist'}->{'packagesToUninstall'} ] )

 Uninstall Debian packages

 Param array \@packagesToUninstall OPTIONAL List of packages to uninstall
 Return int 0 on success, other on failure

=cut

sub _uninstallPackages
{
    my ( $self, $packagesToUninstall ) = @_;

    $packagesToUninstall ||= $self->{'_dist'}->{'packagesToUninstall'};

    my $rs = $self->{'eventManager'}->trigger(
        'beforeUninstallPackages', $packagesToUninstall
    );
    return $rs if $rs;

    if ( @{ $packagesToUninstall } ) {
        # Clear information about available packages
        $rs = execute( [ '/usr/bin/dpkg', '--clear-avail' ], \my $stdout, \my $stderr );
        debug( $stdout ) if length $stdout;
        error( $stderr ) if $rs && length $stderr;
        return $rs if $rs;

        if ( @{ $packagesToUninstall } ) {
            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute(
                [ '/usr/bin/apt-mark', 'unhold', @{ $packagesToUninstall } ],
                \$stdout,
                \$stderr
            );
            debug( $stderr ) if $stderr;

            iMSCP::Dialog->getInstance()->endGauge();

            $rs = execute(
                [
                    ( !iMSCP::Getopt->noprompt
                        ? ( 'debconf-apt-progress', '--logstderr', '--' ) : ()
                    ),
                    '/usr/bin/apt-get',
                    '--assume-yes',
                    '--auto-remove',
                    '--purge',
                    '--no-install-recommends',
                    'remove',
                    @{ $packagesToUninstall }
                ],
                ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                    ? \$stdout : undef
                ),
                \$stderr
            );
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            # Purge packages that were indirectly removed
            $rs = execute(
                "/usr/bin/apt-get -y purge \$(dpkg -l | grep ^rc | awk '{print \$2}')",
                ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                    ? undef : \$stdout
                ),
                \$stderr
            );
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger(
        'afterUninstallPackages', $packagesToUninstall
    );
}

=item _setupGetAddrInfoPrecedence( )

 Setup getaddrinfo(3) precedence (IPv4) for the setup time being

 Return int 0 on success, other on failure

=cut

sub _setupGetAddrInfoPrecedence
{
    my $file = iMSCP::File->new( filename => '/etc/gai.conf' );
    my $fileC = '';

    if ( -f '/etc/gai.conf' ) {
        return 1 unless defined( $fileC = $file->get());
        return 0 if $fileC =~ m%^precedence\s+::ffff:0:0/96\s+100\n%m;
    }

    # Prefer IPv4
    $fileC .= "precedence ::ffff:0:0/96  100\n";

    $file->set( $fileC );
    $file->save();
}

=item _updateAptSourceList( )

 Add required sections to repositories that support them

 Note: Also enable source repositories for the sections when available.
 TODO: Implement better check by parsing apt-cache policy output

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
    my ( $self ) = @_;

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    my $fileC = $file->get();

    for my $section ( @{ $self->{'repositorySections'} } ) {
        my @seenRepositories = ();
        my $foundSection = 0;

        while ( $fileC =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<dist>[^\s]+)\s+(?<components>.+)$/gm ) {
            my $rf = $&;
            my %rc = %+;
            next if grep ($_ eq "$rc{'uri'} $rc{'dist'}", @seenRepositories);
            push @seenRepositories, "$rc{'uri'} $rc{'dist'}";

            if ( $fileC !~ /^deb\s+$rc{'uri'}\s+$rc{'dist'}\s+.*\b$section\b/m ) {
                my $rs = execute(
                    [
                        '/usr/bin/wget',
                        '--prefer-family=IPv4',
                        '--timeout=30',
                        '--spider',
                        "$rc{'uri'}/dists/$rc{'dist'}/$section/"
                            =~ s{([^:])//}{$1/}gr
                    ],
                    \my $stdout,
                    \my $stderr
                );
                debug( $stdout ) if length $stdout;
                debug( $stderr || 'Unknown error' ) if $rs && $rs != 8;
                next if $rs; # Don't check for source archive when binary archive has not been found
                $foundSection = TRUE;
                $fileC =~ s/^($rf)$/$1 $section/m;
                $rf .= " $section";
            } else {
                $foundSection = 1;
            }

            if ( $foundSection
                && $fileC !~ /^deb-src\s+$rc{'uri'}\s+$rc{'dist'}\s+.*\b$section\b/m
            ) {
                my $rs = execute(
                    [
                        '/usr/bin/wget',
                        '--prefer-family=IPv4',
                        '--timeout=30',
                        '--spider',
                        "$rc{'uri'}/dists/$rc{'dist'}/$section/source/"
                            =~ s{([^:])//}{$1/}gr
                    ],
                    \my $stdout,
                    \my $stderr
                );
                debug( $stdout ) if length $stdout;
                debug( $stderr || 'Unknown error' ) if $rs && $rs != 8;

                unless ( $rs ) {
                    if ( $fileC !~ /^deb-src\s+$rc{'uri'}\s+$rc{'dist'}\s.*/m ) {
                        $fileC =~ s/^($rf)/$1\ndeb-src $rc{'uri'} $rc{'dist'} $section/m;
                    } else {
                        $fileC =~ s/^($&)$/$1 $section/m;
                    }
                }
            }
        }

        unless ( $foundSection ) {
            error( sprintf(
                "Couldn't find any repository supporting %s section",
                $section
            ));
            return 1;
        }
    }

    $file->set( $fileC );
    $file->save();
}

=item _processAptRepositories( )

 Process APT repositories

 Return int 0 on success, other on failure

=cut

sub _processAptRepositories
{
    my ( $self ) = @_;

    return 0 unless @{ $self->{'_dist'}->{'aptRepositoriesToRemove'} }
        || @{ $self->{'_dist'}->{'aptRepositoriesToAdd'} };

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    my $rs = $file->copyFile( '/etc/apt/sources.list.bkp' );
    return $rs if $rs;

    return 1 unless defined( my $fileC = $file->get());

    # Cleanup APT sources.list file
    for my $repository (
        @{ $self->{'_dist'}->{'aptRepositoriesToRemove'} },
        @{ $self->{'_dist'}->{'aptRepositoriesToAdd'} }
    ) {
        my $escapedRepository = ref $repository eq 'HASH'
            ? $repository->{'repository'} : $repository;
        $fileC =~ s/^\n?(?:#\s*)?deb(?:-src)?\s+\Q$escapedRepository\E.*?\n//gm;
    }

    # Add APT repositories
    for my $repository ( @{ $self->{'_dist'}->{'aptRepositoriesToAdd'} } ) {
        next if $fileC =~ /^deb\s+$repository->{'repository'}/m;

        $fileC .= <<"EOF";

deb $repository->{'repository'}
deb-src $repository->{'repository'}
EOF

        # Hide "apt-key output should not be parsed (stdout is not a terminal)"
        # warning that is raised in newest apt-key versions. Our usage of
        # apt-key is not dangerous (not parsing)
        local $ENV{'APT_KEY_DONT_WARN_ON_DANGEROUS_USAGE'} = TRUE;

        if ( $repository->{'repository_key_srv'}
            && $repository->{'repository_key_id'}
        ) {
            # Add the repository key from the given key server
            $rs = execute(
                [
                    '/usr/bin/apt-key',
                    'adv',
                    '--recv-keys',
                    '--keyserver', $repository->{'repository_key_srv'},
                    $repository->{'repository_key_id'}
                ],
                \my $stdout,
                \my $stderr
            );
            debug( $stdout ) if length $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            # Workaround https://bugs.launchpad.net/ubuntu/+source/gnupg2/+bug/1633754
            execute(
                [ '/usr/bin/pkill', '-TERM', 'dirmngr' ],
                \$stdout,
                \$stderr
            );
        } elsif ( $repository->{'repository_key_uri'} ) {
            # Add the repository key by fetching it first from the given URI
            my $keyFile = File::Temp->new();
            $keyFile->close();
            $rs = execute(
                [
                    '/usr/bin/wget',
                    '--prefer-family=IPv4',
                    '--timeout=30',
                    '-O',
                    $keyFile->filename(),
                    $repository->{'repository_key_uri'}
                ],
                \my $stdout,
                \my $stderr
            );
            debug( $stdout ) if length $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            $rs ||= execute(
                [ '/usr/bin/apt-key', 'add', $keyFile ],
                \$stdout,
                \$stderr
            );
            debug( $stdout ) if length $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }
    }

    $file->set( $fileC );
    $file->save();
}

=item _processAptPreferences( )

 Process apt preferences

 Return 0 on success, other on failure

=cut

sub _processAptPreferences
{
    my ( $self ) = @_;

    my $fileC;

    for my $pref ( @{ $self->{'_dist'}->{'aptPreferences'} } ) {
        unless ( $pref->{'pinning_pin'} || $pref->{'pinning_pin_priority'} ) {
            error( 'Missing APT pinning attribute in packages file' );
            return 1;
        }

        $fileC .= <<"EOF";

Package: $pref->{'pinning_package'}
Pin: $pref->{'pinning_pin'}
Pin-Priority: $pref->{'pinning_pin_priority'}
EOF
    }

    my $file = iMSCP::File->new( filename => '/etc/apt/preferences.d/imscp' );

    if ( length $fileC ) {
        $fileC =~ s/^\n//;
        $file->set( $fileC );

        my $rs = $file->save();
        $rs ||= $file->mode( 0644 );
        return $rs;
    }

    -f '/etc/apt/preferences.d/imscp' ? $file->delFile() : 0;
}

=item _updatePackagesIndex( )

 Update Debian packages index

 Return int 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
    iMSCP::Dialog->getInstance()->endGauge();

    my $stdout;
    my $rs = execute(
        [
            ( iMSCP::Getopt->noprompt
                ? () : ( 'debconf-apt-progress', '--logstderr', '--' )
            ),
            '/usr/bin/apt-get',
            'update'
        ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
            ? \$stdout : undef
        ),
        \my $stderr
    );
    error( $stderr || 'Unknown error' ) if $rs;
    $rs
}

=item _getPackagesDialog( $dialog )

 Return dialog for distribution packages

 This method need to be called, even if the '--skip-dist-packages' command line
 option has been passed-in. Not doing this would lead to unset values in the
 %::questions hash.

 Param iMSCP::Dialog $dialog
 Return List of dialog subroutines, die on failure

=cut

sub _getPackagesDialog
{
    my ( $self ) = @_;

    my @dialogStack;

    my $rs = $self->{'eventManager'}->trigger(
        'onLoadPackagesFile', \my $packagesFilePath
    );
    return $rs if $rs;

    require XML::Simple;
    $self->{'_packagesFileData'} = XML::Simple->new( NoEscape => TRUE )->XMLin(
        $packagesFilePath //= "$FindBin::Bin/autoinstaller/Packages/"
            . "$::imscpConfig{'DISTRO_ID'}-$::imscpConfig{'DISTRO_CODENAME'}.xml",
        ForceArray     => [ 'package', 'package_delayed', 'package_conflict' ],
        NormaliseSpace => 2
    );

    my $arch = `/usr/bin/dpkg-architecture -qDEB_HOST_ARCH 2>/dev/null`;
    die "Couldn't determine OS architecture" if $? >> 8 != 0 || !$arch;

    for my $section ( sort %{ $self->{'_packagesFileData'} } ) {
        my $data = $self->{'_packagesFileData'}->{$section};
        next unless $data->{'has_alternatives'};

        # Retrieve selected alternative, either from preseed file, or from
        # the master configuration file
        my $sAlt = length $::questions{ uc( $section ) . '_SERVER' }
            # Covers preseeding case
            ? $::questions{ uc( $section ) . '_SERVER' }
            # Covers update case
            : $::imscpConfig{ uc( $section ) . '_SERVER' };

        # Reset alternative if the current one is not available
        $sAlt = '' if length $sAlt && !grep ( $sAlt eq $_, keys %{ $data } );

        # Filter incompatible alternatives
        my @alts = grep {
            ref $data->{$_} eq 'HASH'
                && ( !defined $data->{$_}->{'required_arch'}
                    || $arch eq $data->{$_}->{'required_arch'}
                )
                && ( $section ne 'sql'
                    || !length $sAlt
                    || $sAlt eq $_
                    || !defined $data->{$_}->{'allow_upgrade_from'}
                    || grep { $sAlt eq $_ } split ',', $data->{$_}->{'allow_upgrade_from'}
                )
        } keys %{ $data };

        # Whether or not user must be asked for alternative
        my $needDialog = !length $sAlt || grep (
            $_ eq iMSCP::Getopt->reconfigure, $section, 'servers', 'all'
        );

        if ( $section eq 'sql' ) {
            $::questions{'KEEP_LOCAL_SQL_SERVER'} //= $::imscpConfig{'KEEP_LOCAL_SQL_SERVER'};

            unless( length $::questions{'KEEP_LOCAL_SQL_SERVER'} || !iMSCP::Getopt->preseed ) {
                $::questions{'KEEP_LOCAL_SQL_SERVER'} = 'yes';
            }

            $needDialog = TRUE unless grep (
                $::questions{'KEEP_LOCAL_SQL_SERVER'}, qw/ yes no /
            );
        }
        
        my %choices;
        for my $alt ( @alts ) {
            $choices{ $data->{$alt}->{'description'} // $alt } = $alt;

            # If no alternative is set, either in the master configuration file
            # or in the preseed file (preseeding feature), and if the current
            # one is set as the default one in the distribution packages file,
            # we set it as default selected alternative.
            if ( !length $sAlt && $data->{$alt}->{'default'} ) {
                $sAlt = $alt;
                # We don't show setup dialog, unless the user asked for
                # reconfiguration
                $needDialog = grep(
                    $_ eq iMSCP::Getopt->reconfigure, 'all', 'servers', $section
                );
            }
        }
        
        # If no alternative is set, that means that the distribution packages
        # file doesn't define one... In such case, we  select the first
        # alternative.
        $sAlt = $alts[0] unless length $sAlt;
            
        @{main::questions}{
            uc( $section ) . '_SERVER', uc( $section ) . '_PACKAGE'
        } = (
            $sAlt, $data->{$sAlt}->{'class'} // $sAlt
        );

        # If a dialog is needed, prepare it, unless there is only one
        # alternative available.
        if ( $needDialog && keys %{choices} > 1) {
            push @dialogStack, sub {
                my ( $ret, $value ) = $_[0]->select(
                    <<"EOF", \%choices, $::questions{ uc( $section ) . '_SERVER' } );
Please make your choice for the @{ [ $data->{'description'} // "$section alternative" ] }:
EOF
                return 30 if $ret == 30;

                @{main::questions}{
                    uc( $section ) . '_SERVER', uc( $section ) . '_PACKAGE'
                } = (
                    $value, $data->{$value}->{'class'} // $value
                );
                0;
            };

            if ( $section eq 'sql' ) {
                push @dialogStack, sub {
                    if ( $::questions{'SQL_SERVER'} ne 'remote_server' ||
                        !iMSCP::ProgramFinder::find( 'mysqld' )
                    ) {
                        $::questions{'KEEP_LOCAL_SQL_SERVER'} = 'yes';
                        return 20;
                    }

                    my $ret = $_[0]->boolean( <<'EOF', $::questions{'KEEP_LOCAL_SQL_SERVER'} eq 'no' );
The installer detected that there is already a local SQL server installed on your system.

Do you want to keep your local SQL server? If yes, the installer will ignore it instead of removing packages.
EOF
                    return 30 if $ret == 30;
                    $::questions{'KEEP_LOCAL_SQL_SERVER'} = $ret ? 'no' : 'yes';
                    0;
                }
            }
        }
    }

    return @dialogStack unless @dialogStack;

    push @dialogStack, sub {
        # Override default button labels
        local @{ $_[0]->{'_opts'} }{
            $_[0]->{'program'} eq 'dialog'
                ? qw/ ok-label extra-label /
                : qw/ yes-button no-button /
        } = qw/ Continue Abort /;

        my $ret = $_[0]->boolean( <<"EOF" );
The following @{ [
    grep( $_ eq iMSCP::Getopt->reconfigure, qw/ none servers all / )
        ? 'alternatives were selected'
        : 'alternative has been selected'
] }:

@{ [
    join "\n", sort map {
        " - $self->{'_packagesFileData'}->{$_}->{ $::questions{ uc( $_ ) . '_SERVER' }}->{'description'}".
        (
            $_ eq 'sql'
            && $::questions{ uc( $_ ) . '_SERVER' } eq 'remote_server'
            && iMSCP::ProgramFinder::find('mysqld')
                ? ( ' (the local SQL server ' . (
                    $::questions{'KEEP_LOCAL_SQL_SERVER'} eq 'yes'
                        ? 'will not be uninstalled)' : 'will be uninstalled)'
                ))
                : ''
        )
    } grep {
        exists $::questions{ uc( $_ ) . '_SERVER' }
        && grep( $_ eq iMSCP::Getopt->reconfigure, 'none', 'servers', 'all', $_ )
    } keys %{ $self->{'_packagesFileData'} }
] }

This is your last chance to abort or go back before processing of distribution packages.
EOF
        return 30 if $ret == 30;
        return 50 if $ret == 1;
        0;
    };

    @dialogStack;
}

=item _processPackagesFile( )

 Process packages file data

 Retur int 0 on success, other on failure
=cut

sub _processPackagesFile
{
    my ( $self ) = @_;

    @{ $self->{'_dist'} }{qw/
        aptRepositoriesToRemove aptRepositoriesToAdd aptPreferences
        packagesToInstall packagesToInstallDelayed packagesToPreUninstall
        packagesToUninstall packagesToRebuild packagesPreInstallTasks
        packagesPostInstallTasks
    /} = (
        [], [], [], [], [], [], [], {}, {}, {}
    );

    my $packagesFileData = delete $self->{'_packagesFileData'};

    while ( my ( $section, $data ) = each( %{ $packagesFileData } ) ) {
        # If the remote SQL server alternative has been selected, and if there
        # is a local server that the user want keep, we simply ignore the SQL
        # section.
        if ( $section eq 'sql'
            && $::questions{'SQL_SERVER'} eq 'remote_server'
            && $::questions{'KEEP_LOCAL_SQL_SERVER'} eq 'yes'
            && iMSCP::ProgramFinder::find( 'mysqld' )
        ) {
            next;
        }

        # Packages to install
        if ( defined $data->{'package'} ) {
            for my $package ( @{ $data->{'package'} } ) {
                $self->_parsePackageNode(
                    $package, $self->{'_dist'}->{'packagesToInstall'}
                );
            }
        }

        # Packages to install (delayed)
        if ( defined $data->{'package_delayed'} ) {
            for my $package ( @{ $data->{'package_delayed'} } ) {
                $self->_parsePackageNode(
                    $package, $self->{'_dist'}->{'packagesToInstallDelayed'}
                );
            }
        }

        # Conflicting packages to pre-remove
        if ( defined $data->{'package_conflict'} ) {
            for my $package ( @{ $data->{'package_conflict'} } ) {
                push @{ $self->{'_dist'}->{'packagesToPreUninstall'} },
                    ref $package eq 'HASH' ? $package->{'content'} : $package;
            }
        }

        # APT repository
        if ( defined $data->{'repository'} ) {
            push @{ $self->{'_dist'}->{'aptRepositoriesToAdd'} }, {
                repository         => $data->{'repository'},
                repository_key_uri => $data->{'repository_key_uri'},
                repository_key_id  => $data->{'repository_key_id'},
                repository_key_srv => $data->{'repository_key_srv'}
            };
        }

        # APT preferences (pinning)
        if ( defined $data->{'pinning_package'} ) {
            push @{ $self->{'_dist'}->{'aptPreferences'} }, {
                pinning_package      => $data->{'pinning_package'},
                pinning_pin          => $data->{'pinning_pin'},
                pinning_pin_priority => $data->{'pinning_pin_priority'},
            };
        }

        # Conflicting APT repositories to remove
        if ( defined $data->{'repository_conflict'} ) {
            push @{ $self->{'_dist'}->{'aptRepositoriesToRemove'} },
                $data->{'repository_conflict'}
        }

        # Delete data already processed
        delete @{ $data }{
            qw/ package package_delayed package_conflict
                pinning_package pinning_pin pinning_pin_priority
                repository repository_key_uri repository_key_id
                repository_key_srv fallback_repository
                fallback_repository_key_uri fallback_repository_key_id
                fallback_repository_key_srv repository_conflict
            /
        };

        # Jump to next section, unless the section defines alternatives
        next unless %{ $data };

        my @selectedAlts = $::questions{ uc( $section ) . '_SERVER' };
        my @unselectedAlts;

        # Adds any alternative that must be always installed into the stack of
        # selected alternatives
        for my $alt ( keys %{ $data } ) {
            next if ref $data->{$alt} ne 'HASH'
                || $alt eq $::questions{ uc( $section ) . '_SERVER' };
            if ( $data->{$alt}->{'always_installed'} ) {
                push @selectedAlts, $alt;
                next;
            }

            push @unselectedAlts, $alt;
        }

        # Process stack of selected alternatives
        for my $selectedAlt ( @selectedAlts ) {
            # Packages to install for the selected alternative
            if ( defined $data->{$selectedAlt}->{'package'} ) {
                for my $package ( @{ $data->{$selectedAlt}->{'package'} } ) {
                    $self->_parsePackageNode(
                        $package, $self->{'_dist'}->{'packagesToInstall'}
                    );
                }
            }

            # Package to install (delayed)
            if ( defined $data->{$selectedAlt}->{'package_delayed'} ) {
                for my $package ( @{ $data->{$selectedAlt}->{'package_delayed'} } ) {
                    $self->_parsePackageNode(
                        $package, $self->{'_dist'}->{'packagesToInstallDelayed'}
                    );
                }
            }

            # Conflicting packages that must be pre-removed
            if ( defined $data->{$selectedAlt}->{'package_conflict'} ) {
                for my $package ( @{ $data->{$selectedAlt}->{'package_conflict'} } ) {
                    push @{ $self->{'_dist'}->{'packagesToPreUninstall'} }, ref $package eq 'HASH'
                        ? $package->{'content'} : $package;
                }
            }

            # APT preferences (pinning)
            if ( defined $data->{$selectedAlt}->{'pinning_package'} ) {
                push @{ $self->{'_dist'}->{'aptPreferences'} }, {
                    pinning_package      => $data->{$selectedAlt}->{'pinning_package'},
                    pinning_pin          => $data->{$selectedAlt}->{'pinning_pin'},
                    pinning_pin_priority => $data->{$selectedAlt}->{'pinning_pin_priority'},
                };
            }

            # APT repository
            if ( defined $data->{$selectedAlt}->{'repository'} ) {
                push @{ $self->{'_dist'}->{'aptRepositoriesToAdd'} }, {
                    repository         => $data->{$selectedAlt}->{'repository'},
                    repository_key_uri => $data->{$selectedAlt}->{'repository_key_uri'},
                    repository_key_id  => $data->{$selectedAlt}->{'repository_key_id'},
                    repository_key_srv => $data->{$selectedAlt}->{'repository_key_srv'}
                };
            }

            # Conflicting APT repositories to remove
            if ( defined $data->{$selectedAlt}->{'repository_conflict'} ) {
                push @{ $self->{'_dist'}->{'aptRepositoriesToRemove'} },
                    $data->{$selectedAlt}->{'repository_conflict'}
            }
        }

        # Schedule removal of APT repositories and packages that belongs to
        # unselected alternatives, unless keep_installed flag is set
        my @packagesToInstall = (
            @{ $self->{'_dist'}->{'packagesToInstall'} },
            @{ $self->{'_dist'}->{'packagesToInstallDelayed'} },
            keys %{ $self->{'_dist'}->{'packagesToRebuild'} }
        );

        for my $unselected ( @unselectedAlts ) {
            next if ref $data->{$unselected} ne 'HASH'
                || $data->{$unselected}->{'keep_installed'};

            # APT repositories to remove
            for my $repository ( qw/ repository repository_conflict / ) {
                next unless defined $data->{$unselected}->{$repository};
                push @{ $self->{'_dist'}->{'aptRepositoriesToRemove'} },
                    $data->{$unselected}->{$repository};
            }

            # Packages to uninstall
            for my $node ( qw/ package package_delayed / ) {
                next unless defined $data->{$unselected}->{$node};
                for my $package ( @{ $data->{$unselected}->{$node} } ) {
                    $package = ref $package eq 'HASH'
                        ? $package->{'content'} : $package;
                    next if grep ($package eq $_, @packagesToInstall);
                    push @{ $self->{'_dist'}->{'packagesToUninstall'} }, $package;
                }
            }
        }
    }

    require List::MoreUtils;
    List::MoreUtils->import( 'uniq' );

    for my $packages ( qw/
        packagesToPreUninstall packagesToUninstall
        packagesToInstall packagesToInstallDelayed
    / ) {
        @{ $self->{'_dist'}->{$packages} } = sort { $a cmp $b } uniq(
            @{ $self->{'_dist'}->{$packages} }
        );
    }

    # Filter packages that are no longer available
    my $rs = execute(
        [ '/usr/bin/apt-cache', '--generate', 'pkgnames' ],
        \my $stdout,
        \my $stderr
    );
    error( $stderr || 'Unknown error' ) if $rs > 2;
    return $rs if $rs;
    my %availablePackages;
    @availablePackages{split /\n/, $stdout} = undef;
    undef $stdout;
    for my $packages (
        $self->{'_dist'}->{'packagesToPreUninstall'},
        $self->{'_dist'}->{'packagesToUninstall'}
    ) {
        @{ $packages } = grep (
            exists $availablePackages{$_}, @{ $packages }
        );
    }

    iMSCP::Dialog->getInstance()->endGauge();
    0;
}

=item _parsePackageNode( \%node|$node, \@target )

 Parse a package or package_delayed node

 param string|hashref $node Package node
 param arrayref \@target Target
 Return void

=cut

sub _parsePackageNode
{
    my ( $self, $node, $target ) = @_;

    unless ( ref $node eq 'HASH' ) {
        # Package without further treatment
        push @{ $target }, $node;
        return;
    }

    # Package to rebuild
    if ( $node->{'rebuild_with_patches'} ) {
        $self->{'_dist'}->{'packagesToRebuild'}->{$node->{'content'}} = {
            pkg_src_name      => $node->{'pkg_src_name'} || $node->{'content'},
            patches_directory => $node->{'rebuild_with_patches'},
            discard_patches   => [ $node->{'discard_patches'}
                ? split ',', $node->{'discard_patches'} : ()
            ],
            patch_sys_type    => $node->{'patch_sys_type'} || 'quilt'
        };
    } else {
        push @{ $target }, $node->{'content'};
    }

    # Package pre-installation tasks
    if ( defined $node->{'pre_install_tasks'} ) {
        $self->{'_dist'}->{'packagesPreInstallTasks'}->{$node->{'content'}}
            = $node->{'pre_install_tasks'}
    }

    # Package post-installation tasks
    if ( defined $node->{'post_install_tasks'} ) {
        $self->{'_dist'}->{'packagesPostInstallTasks'}->{$node->{'content'}}
            = $node->{'post_install_tasks'}
    }

    # Per package APT pinning
    if ( defined $node->{'pinning_package'} ) {
        push @{ $self->{'_dist'}->{'aptPreferences'} }, {
            pinning_package      => $node->{'pinning_package'},
            pinning_pin          => $node->{'pinning_pin'},
            pinning_pin_priority => $node->{'pinning_pin_priority'}
        };
    }
}

=item _prefillDebconfDatabase( )

 Pre-fill debconf database

 Return int 0 on success, other on failure

=cut

sub _prefillDebconfDatabase
{
    my ( $self ) = @_;

    my $fileC;

    # Pre-fill questions for Postfix SMTP server if required
    if ( $::questions{'MTA_PACKAGE'} eq 'Servers::mta::postfix' ) {
        chomp( my $mailname = `/usr/bin/hostname --fqdn 2>/dev/null` || 'localdomain' );
        my $hostname = ( $mailname ne 'localdomain' ) ? $mailname : 'localhost';
        chomp( my $domain = `/usr/bin/hostname --domain 2>/dev/null` || 'localdomain' );

        # From postfix package postfix.config script
        my $destinations = ( $mailname eq $hostname )
            ? join ', ', ( $mailname, 'localhost.' . $domain, ', localhost' )
            : join ', ', ( $mailname, $hostname,
            'localhost.' . $domain . ', localhost'
        );

        $fileC .= <<"EOF";
postfix postfix/main_mailer_type select Internet Site
postfix postfix/mailname string $mailname
postfix postfix/destinations string $destinations
EOF
    }

    # Pre-fill question for Proftpd FTP server if required
    if ( $::questions{'FTPD_PACKAGE'} eq 'Servers::ftpd::proftpd' ) {
        $fileC .= <<'EOF';
proftpd-basic shared/proftpd/inetd_or_standalone select standalone
EOF
    }

    # Pre-fill questions for Courier IMAP/POP server if required
    if ( $::questions{'PO_PACKAGE'} eq 'Servers::po::courier' ) {
        $fileC .= <<'EOF';
courier-base courier-base/courier-user note
courier-base courier-base/webadmin-configmode boolean false
courier-ssl courier-ssl/certnotice note
EOF
    }

    # Pre-fill questions for Dovecot IMAP/POP server if required
    if ( $::questions{'PO_PACKAGE'} eq 'Servers::po::dovecot' ) {
        $fileC .= <<'EOF';
dovecot-core dovecot-core/create-ssl-cert boolean true
dovecot-core dovecot-core/ssl-cert-name string localhost
EOF
    }

    # Pre-fill question for sasl2-bin package if required
    if ( `echo GET cyrus-sasl2/purge-sasldb2 | debconf-communicate sasl2-bin 2>/dev/null` =~ /^0/ ) {
        $fileC .= <<'EOF'
sasl2-bin cyrus-sasl2/purge-sasldb2 boolean true
EOF
    }

    # Pre-fill questions for the QL server (MySQL, MariaDB or Percona) if
    # required
    if ( my ( $sqlServerVendor, $sqlServerVersion )
        = $::questions{'SQL_SERVER'} =~ /^(mysql|mariadb|percona)_(\d+\.\d+)/
    ) {
        if ( $::imscpConfig{'DATABASE_PASSWORD'} ne ''
            && -d $::imscpConfig{'DATABASE_DIR'}
        ) {
            # Only show critical questions
            $ENV{'DEBIAN_PRIORITY'} = 'critical';

            # Allow switching to other vendor (e.g: MariaDB 10.0 to MySQL >= 5.6)
            # unlink glob "$::imscpConfig{'DATABASE_DIR'}/debian-*.flag";

            # Don't show SQL root password dialog from package maintainer script
            # when switching to another vendor or a newest version
            # <DATABASE_DIR>/debian-5.0.flag is the file checked by maintainer script
            my $rs = iMSCP::File->new(
                filename => "$::imscpConfig{'DATABASE_DIR'}/debian-5.0.flag"
            )->save();
            return $rs if $rs;
        }

        my ( $qOwner, $qNamePrefix );
        if ( $sqlServerVendor eq 'mysql' ) {
            if ( grep ($_ eq 'mysql-community-server',
                @{ $self->{'packagesToInstall'} }
            ) ) {
                $qOwner = 'mysql-community-server';
                $qNamePrefix = 'mysql-community-server';
            } else {
                $qOwner = 'mysql-server-' . $sqlServerVersion;
                $qNamePrefix = 'mysql-server';
            }
        } elsif ( $sqlServerVendor eq 'mariadb' ) {
            $qOwner = 'mariadb-server-' . $sqlServerVersion;
            $qNamePrefix = 'mysql-server';
        } else {
            $qOwner = 'percona-server-server-' . $sqlServerVersion;
            $qNamePrefix = 'percona-server-server';
        }

        # We do not want ask user for <DATABASE_DIR> removal (we want avoid
        # mistakes as much as possible)
        $fileC .= <<"EOF";
$qOwner $qNamePrefix/remove-data-dir boolean false
$qOwner $qNamePrefix/postrm_remove_databases boolean false
EOF
        # Preset root SQL password using value from preseed file if required
        if ( iMSCP::Getopt->preseed && length $::questions{'SQL_ROOT_PASSWORD'} ) {
            $fileC .= <<"EOF";
$qOwner $qNamePrefix/root_password password $::questions{'SQL_ROOT_PASSWORD'}
$qOwner $qNamePrefix/root-pass password $::questions{'SQL_ROOT_PASSWORD'}
$qOwner $qNamePrefix/root_password_again password $::questions{'SQL_ROOT_PASSWORD'}
$qOwner $qNamePrefix/re-root-pass password $::questions{'SQL_ROOT_PASSWORD'}
EOF
            # Register an event listener to empty the password fields in
            # Debconf database after package installation
            $self->{'eventManager'}->register(
                'beforeInstall',
                sub {
                    for my $entry ( qw/
                        root_password root-pass root_password_again
                        re-root-pass
                    / ) {
                        my $rs = execute(
                            "echo SET $qNamePrefix/$entry | /usr/bin/debconf-communicate $qOwner",
                            \my $stdout,
                            \my $stderr
                        );
                        debug( $stdout ) if length $stdout;
                        error( $stderr || 'Unknown error' ) if $rs;
                        return $rs if $rs;
                    }

                    0;
                }
            );

        }
    }

    return 0 unless length $fileC;

    my $debconfSelectionsFile = File::Temp->new();
    print $debconfSelectionsFile $fileC;
    $debconfSelectionsFile->close();

    my $rs = execute(
        [
            '/usr/bin/debconf-set-selections',
            $debconfSelectionsFile->filename()
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || "Couldn't pre-fill Debconf database" ) if $rs;
    $rs;
}

=item _rebuildAndInstallPackage( $pkg, $pkgSrc, $patchesDir [, $patchesToDiscard = [] [,  $patchFormat = 'quilt' ] ] )

 Rebuild the given Debian package using patches from given directory and
 install the resulting local Debian package

 Note: It is assumed that the Debian source package is dpatch or quilt
 ready.

 Param string $pkg Name of package to rebuild
 Param string $pkgSrc Name of source package
 Param string $patchDir Directory containing set of patches to apply on Debian
                        package source
 param arrayref $patcheqToDiscad OPTIONAL List of patches to discard
 Param string $patchFormat OPTIONAL Patch format (quilt|dpatch) - Default quilt
 Return 0 on success, other on failure

=cut

sub _rebuildAndInstallPackage
{
    my ( $self, $pkg, $pkgSrc, $patchesDir, $patchesToDiscard, $patchFormat ) = @_;
    $patchesDir ||= "$pkg/patches";
    $patchesToDiscard ||= [];
    $patchFormat ||= 'quilt';

    unless ( defined $pkg ) {
        error( '$pkg parameter is not defined' );
        return 1;
    }
    unless ( defined $pkgSrc ) {
        error( '$pkgSrc parameter is not defined' );
        return 1;
    }
    unless ( $patchFormat =~ /^(?:quilt|dpatch)$/ ) {
        error( 'Unsupported patch format.' );
        return 1;
    }

    $patchesDir = "$FindBin::Bin/configs/$::imscpConfig{'DISTRO_ID'}/$patchesDir";
    unless ( -d $patchesDir ) {
        error( sprintf( '%s is not a valid patches directory', $patchesDir ));
        return 1;
    }

    my $srcDownloadDir = File::Temp->newdir( CLEANUP => TRUE );

    # Fix 'W: Download is performed un-sandboxed as root as file...' warning
    # with newest APT versions
    if ( my $uid = ( getpwnam( '_apt' ) )[2] ) {
        unless ( chown $uid, -1, $srcDownloadDir ) {
            error( sprintf(
                "Couldn't change ownership for the %s directory: %s",
                $srcDownloadDir,
                $!
            ));
            return 1;
        }
    }

    # chdir() into download directory
    local $CWD = $srcDownloadDir;

    # Avoid pbuilder warning due to missing $HOME/.pbuilderrc file
    my $rs = iMSCP::File->new(
        filename => File::HomeDir->my_home . '/.pbuilderrc'
    )->save();
    return $rs if $rs;

    startDetail();

    $rs = step(
        sub {
            if ( $self->{'need_pbuilder_update'} ) {
                $self->{'need_pbuilder_update'} = FALSE;

                my $stderr = '';
                my $cmd = [
                    '/usr/sbin/pbuilder',
                    ( -f '/var/cache/pbuilder/base.tgz' ?
                        ( '--update', '--autocleanaptcache' )
                        : '--create'
                    ),
                    '--distribution', ::imscpConfig { 'DISTRO_CODENAME' },
                    '--configfile', "$FindBin::Bin/configs/$::imscpConfig{'DISTRO_ID'}"
                        . '/pbuilder/pbuilderrc',
                    '--override-config'
                ];
                $rs = executeNoWait(
                    $cmd,
                    ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                        ? undef
                        : sub {
                        chomp( $_[0] );
                        return unless $_[0] =~ /^i:\s*(.*)/i;
                        step( undef, <<"EOF", 5, 1 );
"Creating/Updating pbuilder environment

- @{ [ ucfirst( $1 ) ] }
 
Please be patient. This may take few minutes...
EOF
                    }
                    ),
                    sub { $stderr .= $_[0]; }
                );
                error( $stderr || 'Unknown error' ) if $rs;
                return $rs if $rs;
            }
            0;
        },
        'Creating/Updating pbuilder environment', 5, 1
    );
    $rs ||= step(
        sub {
            my $stderr = '';
            $rs = executeNoWait(
                [ '/usr/bin/apt-get', '-y', 'source', $pkgSrc ],
                ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                    ? undef : sub {
                    chomp( $_[0] );
                    step( undef, <<"EOF", 5, 2 );
Downloading $pkgSrc $::imscpConfig{'DISTRO_ID'} source package

- @{ [ $_[0] =~ s/^\s*//r ] }

Depending on your system this may take few seconds...
EOF
                } ),
                sub { $stderr .= $_[0] }
            );
            error( $stderr || 'Unknown error' ) if $rs;
            $rs;
        },
        sprintf(
            'Downloading %s %s source package',
            $pkgSrc,
            $::imscpConfig{'DISTRO_ID'}
        ),
        5,
        2
    );

    {
        # chdir() into package source directory
        local $CWD = ( <$pkgSrc-*> )[0];

        $rs ||= step(
            sub {
                my $serieFile = iMSCP::File->new(
                    filename => 'debian/patches/'
                        . ( $patchFormat eq 'quilt' ? 'series' : '00list'
                    ));
                return 1 unless defined( my $serieFileC = $serieFile->get());

                for my $patch ( sort { $a cmp $b } iMSCP::Dir->new(
                    dirname => $patchesDir )->getFiles()
                ) {
                    next if grep ($_ eq $patch, @{ $patchesToDiscard });
                    $serieFileC .= "$patch\n";
                    $rs = iMSCP::File->new(
                        filename => "$patchesDir/$patch"
                    )->copyFile(
                        "debian/patches/$patch", { preserve => 'no' }
                    );
                    return $rs if $rs;
                }

                $rs = $serieFile->set( $serieFileC );
                $rs ||= $serieFile->save();
                return $rs if $rs;

                my $stderr;
                $rs = execute(
                    [
                        '/usr/bin/dch',
                        '--local',
                        '~i-mscp-',
                        'Patched by i-MSCP installer for compatibility.'
                    ],
                    ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                        ? undef : \my $stdout
                    ),
                    \$stderr
                );
                debug( $stdout ) if length $stdout;
                error( $stderr || 'Unknown error' ) if $rs;
                return $rs if $rs;
            },
            sprintf(
                'Patching %s %s source package...',
                $pkgSrc,
                $::imscpConfig{'DISTRO_ID'}
            ),
            5,
            3
        );
        $rs ||= step(
            sub {
                my $stderr;
                $rs = executeNoWait(
                    [
                        '/usr/bin/pdebuild',
                        '--use-pdebuild-internal',
                        '--configfile', "$FindBin::Bin/configs/"
                        . "$::imscpConfig{'DISTRO_ID'}/pbuilder/pbuilderrc"
                    ],
                    ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                        ? undef : sub {
                        return unless $_[0] =~ /^i:\s*(.*)/i;
                        chomp( $_[0] );
                        step( undef, <<"EOF", 5, 4 );
Building new $pkg $::imscpConfig{'DISTRO_ID'} package

@{ [ ucfirst( $1 ) ] }

Please be patient. This may take few seconds...
EOF
                    } ),
                    sub { $stderr .= $_[0] }
                );
                error( $stderr || 'Unknown error' ) if $rs;
                $rs;
            },
            sprintf(
                'Building local %s %s package',
                $pkg,
                $::imscpConfig{'DISTRO_ID'}
            ),
            5,
            4
        );
    }

    $rs ||= step(
        sub {
            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute(
                [ '/usr/bin/apt-mark', 'unhold', $pkg ],
                \my $stdout,
                \my $stderr
            );
            debug( $stderr ) if $stderr;

            $stderr = '';
            $rs = executeNoWait(
                '/usr/bin/dpkg --force-confnew -i '
                    . "/var/cache/pbuilder/result/${pkg}_*.deb",
                ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                    ? undef : sub {
                    chomp( $_[0] );
                    step( undef, <<"EOF", 5, 5 );
Installing local $pkg $::imscpConfig{'DISTRO_ID'} package

$_[0]
EOF
                } ),
                sub { $stderr .= $_[0] }
            );
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            # Ignore exit code due to
            # https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958
            execute(
                [ '/usr/bin/apt-mark', 'hold', $pkg ],
                \$stdout,
                \$stderr
            );
            debug( $stdout ) if length $stdout;
            debug( $stderr ) if length $stderr;
            0;
        },
        sprintf(
            'Installing local %s %s package',
            $pkg,
            $::imscpConfig{'DISTRO_ID'}
        ),
        5,
        5
    );
    endDetail();

    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
