=head1 NAME

 autoinstaller::Adapter::DebianAdapter - Debian autoinstaller adapter

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Class::Autouse qw/ :nostat File::HomeDir /;
use Fcntl qw/ :flock /;
use File::Temp;
use FindBin;
use iMSCP::Cwd;
use iMSCP::Debug qw/ debug error output /;
use iMSCP::Dialog;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::LsbRelease;
use iMSCP::ProgramFinder;
use version;
use parent 'autoinstaller::Adapter::AbstractAdapter';

=head1 DESCRIPTION

 i-MSCP autoinstaller adapter implementation for Debian.

=head1 PUBLIC METHODS

=over 4

=item installPreRequiredPackages( )

 Install pre-required packages

 Return int 0 on success, other on failure

=cut

sub installPreRequiredPackages
{
    my ($self) = @_;

    print STDOUT output( 'Satisfying prerequisites... Please wait.', 'info' );

    my $rs = $self->_updateAptSourceList( );
    $rs ||= $self->_updatePackagesIndex( );

    local $ENV{'UCF_FORCE_CONFFNEW'} = 1;
    local $ENV{'UCF_FORCE_CONFFMISS'} = 1;
    local $ENV{'LANG'} = 'C';

    my $stdout;
    $rs = execute(
        [
            (!iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'debconf-apt-progress' )
                ? ('debconf-apt-progress', '--logstderr', '--') : ( )
            ),
            'apt-get', '--assume-yes', '--option', 'DPkg::Options::=--force-confnew',
            '--option', 'DPkg::Options::=--force-confmiss', '--auto-remove', '--purge', '--no-install-recommends',
            'install', @{$self->{'preRequiredPackages'}}
        ],
        (iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef),
        \ my $stderr
    );
    error( sprintf( "Couldn't install pre-required packages: %s", $stderr || 'Unknown error' ) ) if $rs;

    $rs ||= $self->{'eventManager'}->trigger( 'afterInstallPreRequiredPackages' );
}

=item preBuild(\@steps)

 Process preBuild tasks

 Param array \@steps List of build steps
 Return int 0 on success, other on failure

=cut

sub preBuild
{
    my ($self, $steps) = @_;

    return 0 if $main::skippackages;

    unshift @{$steps},
        (
            [ sub { $self->_buildPackageList( ) }, 'Building list of packages to install/uninstall' ],
            [ sub { $self->_prefillDebconfDatabase( ) }, 'Pre-fill Debconf database' ],
            [ sub { $self->_processAptRepositories( ) }, 'Processing APT repositories if any' ],
            [ sub { $self->_processAptPreferences( ) }, 'Processing APT preferences if any' ],
            [ sub { $self->_updatePackagesIndex( ) }, 'Updating packages index' ]
        );

    0
}

=item installPackages( )

 Install Debian packages

 Return int 0 on success, other on failure

=cut

sub installPackages
{
    my ($self) = @_;

    # See https://people.debian.org/~hmh/invokerc.d-policyrc.d-specification.txt
    my $policyrcd = File::Temp->new( UNLINK => 1 );

    # Prevents invoke-rc.d (which is invoked by package maintainer scripts) to start some services
    #
    # - Apache2: Prevent "bind() to 0.0.0.0:80 failed (98: Address already in use" failure
    # - Nginx:   Prevent "bind() to 0.0.0.0:80 failed (98: Address already in use" failure
    # - Nginx:   Prevent start failure when IPv6 stack is not enabled
    # - Dovecot: Prevent start failure when IPv6 stack is not enabled
    # - bind9:   Prevent failure when resolvconf is not configured yet
    print $policyrcd <<'EOF';
#!/bin/sh

initscript=$1
action=$2

if [ "$action" = "start" ] || [ "$action" = "restart" ]; then
    for i in apache2 bind9 dovecot nginx; do
        if [ "$initscript" = "$i" ]; then
            exit 101;
        fi
    done
fi
EOF

    $policyrcd->flush( );
    $policyrcd->close( );
    chmod( 0750, $policyrcd->filename ) or die(
        sprintf( "Couldn't change permissions on %s: %s", $policyrcd->filename, $! )
    );

    # See ZG-POLICY-RC.D(8)
    local $ENV{'POLICYRCD'} = $policyrcd->filename( );

    my $rs = $self->uninstallPackages( $self->{'packagesToPreUninstall'} );
    $rs ||= $self->{'eventManager'}->trigger(
        'beforeInstallPackages', $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}
    );
    return $rs if $rs;

    my $nPackages = scalar keys %{$self->{'packagesPreInstallTasks'}};
    my $cPackage = 1;

    local $ENV{'LANG'} = 'C';

    startDetail( );

    {
        local $CWD = "$FindBin::Bin/autoinstaller/preinstall";

        for my $package(sort keys %{$self->{'packagesPreInstallTasks'}}) {
            $rs ||= step(
                sub {
                    my $stdout;
                    $rs = execute(
                        $self->{'packagesPreInstallTasks'}->{$package},
                        (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout), \ my $stderr
                    );
                    error(
                        $stderr || sprintf( "Unknown error while executing preinstall tasks for the `%s' package",
                            $package )
                    ) if $rs;
                    $rs;
                },
                sprintf( "Executing preinstall tasks for the `%s' package... Please be patient.", $package ),
                $nPackages,
                $cPackage
            );
            last if $rs;
            $cPackage++;
        }
    }

    endDetail( );
    return $rs if $rs;

    # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
    execute(
        "apt-mark unhold @{$self->{'packagesToInstall'}} @{$self->{'packagesToInstallDelayed'}}",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    {
        local $ENV{'UCF_FORCE_CONFFNEW'} = 1;
        local $ENV{'UCF_FORCE_CONFFMISS'} = 1;

        my ($aptVersion) = `apt-get --version` =~ /^apt\s+([\d.]+)/;
        my @cmd = (
            (!iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : ( )),
            'apt-get', '--assume-yes', '--option', 'DPkg::Options::=--force-confnew', '--option',
            'DPkg::Options::=--force-confmiss', '--option', 'Dpkg::Options::=--force-overwrite',
            ($main::forcereinstall ? '--reinstall' : ( )), '--auto-remove', '--purge', '--no-install-recommends',
            ((version->parse( $aptVersion ) < version->parse( '1.1.0' )) ? '--force-yes' : '--allow-downgrades'),
            'install'
        );

        for ($self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}) {
            next unless @{$_};
            $rs = execute(
                [ @cmd, @{$_} ], (iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef), \$stderr
            );
            error( sprintf( "Couldn't install packages: %s", $stderr || 'Unknown error' ) ) if $rs;
            return $rs if $rs;
        }
    }

    $nPackages = scalar keys %{$self->{'packagesPostInstallTasks'}};
    $cPackage = 1;

    startDetail( );

    {
        local $CWD = "$FindBin::Bin/autoinstaller/postinstall";

        for my $package(sort keys %{$self->{'packagesPostInstallTasks'}}) {
            $rs ||= step(
                sub {
                    $rs = execute(
                        $self->{'packagesPostInstallTasks'}->{$package},
                        (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout), \ $stderr
                    );
                    error(
                        $stderr
                            || sprintf( "Unknown error while executing postinstall tasks for the `%s' package",
                            $package )
                    ) if $rs;
                    $rs;
                },
                sprintf( "Executing postinstall tasks for the `%s' package... Please be patient.", $package ),
                $nPackages,
                $cPackage
            );
            last if $rs;
            $cPackage++;
        }
    }

    endDetail( );
    return $rs if $rs;

    while(my ($package, $metadata) = each( %{$self->{'packagesToRebuild'}} )) {
        $rs = $self->_rebuildAndInstallPackage(
            $package, $metadata->{'pkg_src_name'}, $metadata->{'patches_directory'}, $metadata->{'discard_patches'},
            $metadata->{'patch_sys_type'}
        );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterInstallPackages' );
}

=item uninstallPackages( [ \@packagesToUninstall = $self->{'packagesToUninstall'} ] )

 Uninstall Debian packages

 Param array \@packagesToUninstall OPTIONAL List of packages to uninstall
 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
    my ($self, $packagesToUninstall) = @_;

    $packagesToUninstall ||= $self->{'packagesToUninstall'};

    local $ENV{'LANG'} = 'C';

    require List::MoreUtils;
    List::MoreUtils->import( 'uniq' );

    @{$packagesToUninstall} = uniq( @{$packagesToUninstall} );
    s/=.*$// for @{$packagesToUninstall}; # Remove package version info (since 1.2.12)

    my $rs = $self->{'eventManager'}->trigger( 'beforeUninstallPackages', $packagesToUninstall );
    return $rs if $rs;

    # Filter packages that must not be removed
    my @packagesToKept = (
        @{$self->{'packagesToInstall'}}, @{$self->{'packagesToInstallDelayed'}}, keys %{$self->{'packagesToRebuild'}}
    );
    @packagesToKept = uniq( @packagesToKept );
    s/=.*$// for @packagesToKept; # Remove any package version info (since 1.2.12)
    @{$packagesToUninstall} = grep {
        my $__ = $_;
        !grep($_ eq $__, @packagesToKept)
    } uniq( @{$packagesToUninstall} );

    if (@{$packagesToUninstall}) {
        # Clear information about available packages
        $rs = execute( 'dpkg --clear-avail', \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $rs && $stderr;
        return $rs if $rs;

        # Get list of available packages
        $rs = execute( [ 'apt-cache', '--generate', 'pkgnames' ], \$stdout, \$stderr );
        error( $stderr || "Couldn't generate list of available packages" ) if $rs > 2;
        my @apkgs = split /\n/, $stdout;
        undef $stdout;

        # Filter packages that are not available.
        my @pkgs = ();
        for my $pkg(@{$packagesToUninstall}) {
            (my $pkgReg = $pkg) =~ s/\*/.*/;
            push @pkgs, $pkg if grep { $_ =~ /^$pkgReg/ } @apkgs;
        }
        @{$packagesToUninstall} = @pkgs;
        undef @apkgs;
        undef @pkgs;

        if (@{$packagesToUninstall}) {
            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( [ 'apt-mark', 'unhold', @{$packagesToUninstall} ], \ $stdout, \ $stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;

            iMSCP::Dialog->getInstance( )->endGauge( ) unless iMSCP::Getopt->noprompt;

            $rs = execute(
                [
                    (!iMSCP::Getopt->noprompt ? ('debconf-apt-progress', '--logstderr', '--') : ( )),
                    'apt-get', '--assume-yes', '--auto-remove', '--purge', '--no-install-recommends', 'remove',
                    @{$packagesToUninstall}
                ],
                (iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef),
                \$stderr
            );
            error( sprintf( "Couldn't uninstall packages: %s", $stderr || 'Unknown error' ) ) if $rs;
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterUninstallPackages' );
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
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance( );
    $self->{'repositorySections'} = [ 'main', 'contrib', 'non-free' ];
    $self->{'preRequiredPackages'} = [
        'binutils', 'debconf-utils', 'dialog', 'dirmngr', 'libbit-vector-perl', 'libclass-insideout-perl',
        'lsb-release', 'liblist-moreutils-perl', 'libscalar-defer-perl', 'libsort-versions-perl', 'libxml-simple-perl',
        'wget', 'liblchown-perl', 'apt-transport-https', 'policyrcd-script-zg2', 'libclone-perl'
    ];
    $self->{'aptRepositoriesToRemove'} = [ ];
    $self->{'aptRepositoriesToAdd'} = [ ];
    $self->{'aptPreferences'} = [ ];
    $self->{'packagesToInstall'} = [ ];
    $self->{'packagesToInstallDelayed'} = [ ];
    $self->{'packagesToPreUninstall'} = [ ];
    $self->{'packagesToUninstall'} = [ ];
    $self->{'packagesToRebuild'} = { };
    $self->{'packagesPreInstallTasks'} = { };
    $self->{'packagesPostInstallTasks'} = { };
    $self->{'need_pbuilder_update'} = 1;
    delete $ENV{'DEBCONF_FORCE_DIALOG'};
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->noprompt;
    $ENV{'DEBFULLNAME'} = 'i-MSCP Installer';
    $ENV{'DEBEMAIL'} = 'team@i-mscp.net';
    $self->_setupGetaddrinfoPrecedence( );
    $self;
}

=item _setupGetaddrinfoPrecedence( )

 Setup getaddrinfo(3) precedence (IPv4) for the setup time being

 Return int 0 on success, other on failure

=cut

sub _setupGetaddrinfoPrecedence
{
    my $file = iMSCP::File->new( filename => '/etc/gai.conf' );
    my $fileContent = '';

    if (-f '/etc/gai.conf') {
        $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file ", $file->{'filename'} ) );
            return 1;
        }

        return 0 if $fileContent =~ m%^precedence\s+::ffff:0:0/96\s+100\n%m;
    }

    # Prefer IPv4
    $fileContent .= "precedence ::ffff:0:0/96  100\n";

    $file->set( $fileContent );
    $file->save( );
}

=item _buildPackageList( )

 Build lists of Debian packages to uninstall and install

 Return int 0 on success, other on failure

=cut

sub _buildPackageList
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBuildPackageList', \ my $packageFilePath );
    return $rs if $rs;

    unless (defined $packageFilePath) {
        my $lsbRelease = iMSCP::LsbRelease->getInstance( );
        my $distroID = $lsbRelease->getId( 'short' );
        my $distroCodename = $lsbRelease->getCodename( 'short' );
        $packageFilePath = "$FindBin::Bin/autoinstaller/Packages/".lc($distroID).'-'.lc($distroCodename).'.xml';
    }

    eval "use XML::Simple; 1" or die( $@ );
    my $xml = XML::Simple->new( NoEscape => 1 );
    my $pkgData = eval {
        $xml->XMLin(
            $packageFilePath,
            ForceArray     => [ 'package', 'package_delayed', 'package_conflict' ],
            NormaliseSpace => 2
        );
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    my $dialog = iMSCP::Dialog->getInstance( );
    $dialog->set( 'no-cancel', '' );

    for my $section (sort keys %{$pkgData}) {
        my $data = $pkgData->{$section};

        # Simple list of packages

        my $spl = 0;

        if ($data->{'package'}) {
            push @{$self->{'packagesToInstall'}}, (ref $_ eq 'HASH' ? $_->{'content'} : $_) for @{$data->{'package'}};
            $spl = 1;
        }

        if ($data->{'package_delayed'}) {
            push @{$self->{'packagesToInstallDelayed'}}, @{$data->{'package_delayed'}};
            $spl = 1;
        }

        if ($data->{'package_conflict'}) {
            push @{$self->{'packagesToPreUninstall'}}, @{$data->{'package_conflict'}};
            $spl = 1;
        }

        next if $spl eq 1;

        # List of alternatives

        # Whether user must be asked for alternative or not
        my $needDialog = 0;

        # Retrieve selected alternative if any
        my $sAlt = $main::questions{ uc( $section ).'_SERVER' } || $main::imscpConfig{ uc( $section ).'_SERVER' };

        # Resets alternative if no longer available
        $sAlt = '' if $sAlt ne '' && !grep($_ eq $sAlt, keys %{$data});

        # Map of alternative descriptions to aternative names
        my %altDescs;
        for(keys %{$data}) {
            $altDescs{$data->{$_}->{'description'} || $_} = $_;

            # If there is no alternative set yet, set selected alternative 
            # to default alternative and force dialog to make user able change it
            if ($sAlt eq '' && $data->{$_}->{'default'}) {
                $sAlt = $_;
                $needDialog = 1;
            }
        }

        # Filter unallowed alternatives
        unless ($needDialog || !$data->{$sAlt}->{'allow_switch'}) {
            my @allowedAlts = (split( ',', $data->{$sAlt}->{'allow_switch'} ), $sAlt);
            while(my ($altDesc, $altName) = each(%altDescs)) {
                delete $altDescs{$altDesc} unless grep( $altName eq $_, @allowedAlts );
            }
        }

        # If there is more than one alternative available and if dialog is forced,
        # or if user explicitely asked for reconfiguration of that alternative,
        # show dialog for alternative selection
        if (keys %altDescs > 1 && ($needDialog || grep( $_ eq $main::reconfigure, ( $section, 'servers', 'all' ) ))) {
            (my $ret, $sAlt) = $dialog->radiolist(
                <<"EOF", [ keys %altDescs ], $data->{$sAlt}->{'description'} || $sAlt);

Please make your choise for the $section service:
EOF
            return $ret if $ret; # Handle ESC case

            # Set real alternative name
            $sAlt = $altDescs{$sAlt};
        }

        while(my ($alt, $altData) = each( %{$data} )) {
            next if $alt eq $sAlt;

            # APT repository to remove
            push @{$self->{'aptRepositoriesToRemove'}}, $altData->{'repository'} if $altData->{'repository'};
            push @{$self->{'aptRepositoriesToRemove'}}, $altData->{'repository_conflict'}
                if $altData->{'repository_conflict'};

            # Packages to uninstall
            if ($altData->{'package'}) {
                for(@{$altData->{'package'}}) {
                    push @{$self->{'packagesToUninstall'}}, ref $_ eq 'HASH' ? $_->{'content'} : $_;
                }
            }
            push @{$self->{'packagesToUninstall'}}, @{$altData->{'package_delayed'}} if $altData->{'package_delayed'};
        }

        # APT preferences to add
        push @{$self->{'aptPreferences'}},
            {
                pinning_package      => $data->{$sAlt}->{'pinning_package'},
                pinning_pin          => $data->{$sAlt}->{'pinning_pin'} || undef,
                pinning_pin_priority => $data->{$sAlt}->{'pinning_pin_priority'} || undef,
            } if $data->{$sAlt}->{'pinning_package'};

        # Conflicting APT repository to remove
        push @{$self->{'aptRepositoriesToRemove'}}, $data->{$sAlt}->{'repository_conflict'}
            if $data->{$sAlt}->{'repository_conflict'};

        # APT repository to add
        if ($data->{$sAlt}->{'repository'}) {
            push @{$self->{'aptRepositoriesToAdd'}},
                {
                    repository         => $data->{$sAlt}->{'repository'},
                    repository_key_uri => $data->{$sAlt}->{'repository_key_uri'} || undef,
                    repository_key_id  => $data->{$sAlt}->{'repository_key_id'} || undef,
                    repository_key_srv => $data->{$sAlt}->{'repository_key_srv'} || undef
                };
        }

        # Conflicting packages that must be pre-removed
        # This can be obsolete packages which would prevent installation of new packages, or packages which were
        # frozen with the 'apt-mark hold <package>' command.
        push @{$self->{'packagesToPreUninstall'}}, @{$data->{$sAlt}->{'package_conflict'}}
            if $data->{$sAlt}->{'package_conflict'};

        # Packages to install
        if ($data->{$sAlt}->{'package'}) {
            for(@{$data->{$sAlt}->{'package'}}) {
                if (ref $_ eq 'HASH') {
                    if ($_->{'rebuild_with_patches'}) {
                        $self->{'packagesToRebuild'}->{$_->{'content'}} = {
                            pkg_src_name      => $_->{'pkg_src_name'} || $_->{'content'},
                            patches_directory => $_->{'rebuild_with_patches'},
                            discard_patches   => [ $_->{'discard_patches'} ? split ',', $_->{'discard_patches'} : () ],
                            patch_sys_type    => $_->{'patch_sys_type'} || 'quilt',
                        };
                    } else {
                        push @{$self->{'packagesToInstall'}}, $_->{'content'};
                    }

                    if ($_->{'pre_install_tasks'}) {
                        $self->{'packagesPreInstallTasks'}->{$_->{'content'}} = $_->{'pre_install_tasks'}
                    }

                    if ($_->{'post_install_tasks'}) {
                        $self->{'packagesPostInstallTasks'}->{$_->{'content'}} = $_->{'post_install_tasks'}
                    }
                } else {
                    push @{$self->{'packagesToInstall'}}, $_;
                }
            }
        }

        # Package to install (delayed)
        push @{$self->{'packagesToInstallDelayed'}}, @{$data->{$sAlt}->{'package_delayed'}}
            if $data->{$sAlt}->{'package_delayed'};

        # Set alternative name
        $main::imscpConfig{uc( $section ).'_SERVER'} = $sAlt;

        # Set package name
        $main::imscpConfig{uc( $section ).'_PACKAGE'} = $data->{$sAlt}->{'class'} || $sAlt;
    }

    $dialog->set( 'no-cancel', '' );
    0;
}

=item _updateAptSourceList( )

 Add required sections to repositories that support them

 Note: Also enable source repositories for the sections when available.
 TODO: Implement better check by parsing apt-cache policy output

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
    my ($self) = @_;

    local $ENV{'LANG'} = 'C';

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    my $fileContent = $file->get( );

    for my $section(@{$self->{'repositorySections'}}) {
        my @seenRepositories = ();
        my $foundSection = 0;

        while($fileContent =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<dist>[^\s]+)\s+(?<components>.+)$/gm) {
            my $rf = $&;
            my %rc = %+;
            next if grep($_ eq "$rc{'uri'} $rc{'dist'}", @seenRepositories);
            push @seenRepositories, "$rc{'uri'} $rc{'dist'}";

            if ($fileContent !~ /^deb\s+$rc{'uri'}\s+$rc{'dist'}\s+.*\b$section\b/m) {
                my $rs = execute(
                    [ 'wget', '--spider', "$rc{'uri'}/dists/$rc{'dist'}/$section/" =~ s{([^:])//}{$1/}gr ],
                    \ my $stdout,
                    \ my $stderr
                );
                debug( $stdout ) if $stdout;
                debug( $stderr || 'Unknown error' ) if $rs && $rs != 8;
                next if $rs; # Don't check for source archive when binary archive has not been found
                $foundSection = 1;
                $fileContent =~ s/^($rf)$/$1 $section/m;
                $rf .= " $section";
            } else {
                $foundSection = 1;
            }

            if ($foundSection && $fileContent !~ /^deb-src\s+$rc{'uri'}\s+$rc{'dist'}\s+.*\b$section\b/m) {
                my $rs = execute(
                    [ 'wget', '--spider', "$rc{'uri'}/dists/$rc{'dist'}/$section/source/" =~ s{([^:])//}{$1/}gr ],
                    \ my $stdout,
                    \ my $stderr
                );
                debug( $stdout ) if $stdout;
                debug( $stderr || 'Unknown error' ) if $rs && $rs != 8;

                unless ($rs) {
                    if ($fileContent !~ /^deb-src\s+$rc{'uri'}\s+$rc{'dist'}\s.*/m) {
                        $fileContent =~ s/^($rf)/$1\ndeb-src $rc{'uri'} $rc{'dist'} $section/m;
                    } else {
                        $fileContent =~ s/^($&)$/$1 $section/m;
                    }
                }
            }
        }

        unless ($foundSection) {
            error( sprintf( "Couldn't find any repository supporting %s section", $section ) );
            return 1;
        }
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _processAptRepositories( )

 Process APT repositories

 Return int 0 on success, other on failure

=cut

sub _processAptRepositories
{
    my ($self) = @_;

    return 0 unless @{$self->{'aptRepositoriesToRemove'}} || @{$self->{'aptRepositoriesToAdd'}};

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    my $rs = $file->copyFile( '/etc/apt/sources.list.bkp' );
    return $rs if $rs;

    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( "Couldn't read /etc/apt/sources.list file" );
        return 1;
    }

    # Cleanup APT sources.list file
    for my $repository(@{$self->{'aptRepositoriesToRemove'}}, @{$self->{'aptRepositoriesToAdd'}}) {
        my $escapedRepository = (ref $repository eq 'HASH') ? $repository->{'repository'} : $repository;
        $fileContent =~ s/^\n?(?:#\s*)?deb(?:-src)?\s+\Q$escapedRepository\E.*?\n//gm;
    }

    # Add APT repositories
    for my $repository(@{$self->{'aptRepositoriesToAdd'}}) {
        next if $fileContent =~ /^deb\s+$repository->{'repository'}/m;

        $fileContent .= <<"EOF";

deb $repository->{'repository'}
deb-src $repository->{'repository'}
EOF

        # Hide "apt-key output should not be parsed (stdout is not a terminal)" warning that
        # is raised in newest apt-key versions. Our usage of apt-key is not dangerous (not parsing)
        local $ENV{'APT_KEY_DONT_WARN_ON_DANGEROUS_USAGE'} = 1;

        if ($repository->{'repository_key_srv'} && $repository->{'repository_key_id'}) {
            # Add the repository key from the given key server
            $rs = execute(
                [
                    'apt-key', 'adv', '--recv-keys', '--keyserver', $repository->{'repository_key_srv'},
                    $repository->{'repository_key_id'}
                ],
                \ my $stdout,
                \ my $stderr
            );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        } elsif ($repository->{'repository_key_uri'}) {
            # Add the repository key by fetching it first from the given URI
            my $keyFile = File::Temp->new( UNLINK => 1 );
            $rs = execute(
                [
                    'wget', '--prefer-family=IPv4', '--timeout=5', '--no-cache', '--no-dns-cache', '-O',
                    $keyFile->filename, $repository->{'repository_key_uri'}
                ],
                \ my $stdout,
                \ my $stderr
            );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            $rs ||= execute( [ 'apt-key', 'add', $keyFile ], \ $stdout, \ $stderr );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _processAptPreferences( )

 Process apt preferences

 Return 0 on success, other on failure

=cut

sub _processAptPreferences
{
    my ($self) = @_;

    my $fileContent = '';

    for my $pref (@{$self->{'aptPreferences'}}) {
        unless ($pref->{'pinning_pin'} || $pref->{'pinning_pin_priority'}) {
            error( 'One of these attributes is missing: pinning_pin or pinning_pin_priority' );
            return 1;
        }

        $fileContent .= <<"EOF";

Package: $pref->{'pinning_package'}
Pin: $pref->{'pinning_pin'}
Pin-Priority: $pref->{'pinning_pin_priority'}
EOF
    }

    my $file = iMSCP::File->new( filename => '/etc/apt/preferences.d/imscp' );

    if ($fileContent) {
        $fileContent =~ s/^\n//;
        $file->set( $fileContent );

        my $rs = $file->save( );
        $rs ||= $file->mode( 0644 );
        return $rs;
    }

    (-f '/etc/apt/preferences.d/imscp') ? $file->delFile( ) : 0;
}

=item _updatePackagesIndex( )

 Update Debian packages index

 Return int 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
    iMSCP::Dialog->getInstance( )->endGauge( ) if !iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'dialog' );

    local $ENV{'LANG'} = 'C';

    my $stdout;
    my $rs = execute(
        [ (!iMSCP::Getopt->noprompt ? ('debconf-apt-progress', '--logstderr', '--') : ( )), 'apt-get', 'update' ],
        (iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef), \ my $stderr
    );
    error( sprintf( "Couldn't update package index from remote repository: %s", $stderr || 'Unknown error' ) ) if $rs;
    debug( $stderr );
    $rs
}

=item _prefillDebconfDatabase( )

 Pre-fill debconf database

 Return int 0 on success, other on failure

=cut

sub _prefillDebconfDatabase
{
    my ($self) = @_;

    my $fileContent = '';

    # Pre-fill questions for Postfix SMTP server if required
    if ($main::imscpConfig{'MTA_PACKAGE'} eq 'Servers::mta::postfix') {
        chomp(my $mailname = `hostname --fqdn 2>/dev/null` || 'localdomain');
        my $hostname = ($mailname ne 'localdomain') ? $mailname : 'localhost';
        chomp(my $domain = `hostname --domain 2>/dev/null` || 'localdomain');

        # From postfix package postfix.config script
        my $destinations = ($mailname eq $hostname)
            ? join ', ', ($mailname, 'localhost.'.$domain, ', localhost')
            : join ', ', ($mailname, $hostname, 'localhost.'.$domain.', localhost');

        $fileContent .= <<"EOF";
postfix postfix/main_mailer_type select Internet Site
postfix postfix/mailname string $mailname
postfix postfix/destinations string $destinations
EOF
    }

    # Pre-fill question for Proftpd FTP server if required
    if ($main::imscpConfig{'FTPD_PACKAGE'} eq 'Servers::ftpd::proftpd') {
        $fileContent .= <<'EOF';
proftpd-basic shared/proftpd/inetd_or_standalone select standalone
EOF
    }

    # Pre-fill questions for Courier IMAP/POP server if required
    if ($main::imscpConfig{'PO_PACKAGE'} eq 'Servers::po::courier') {
        $fileContent .= <<'EOF';
courier-base courier-base/courier-user note
courier-base courier-base/webadmin-configmode boolean false
courier-ssl courier-ssl/certnotice note
EOF
    }

    # Pre-fill questions for Dovecot IMAP/POP server if required
    if ($main::imscpConfig{'PO_PACKAGE'} eq 'Servers::po::dovecot') {
        $fileContent .= <<'EOF';
dovecot-core dovecot-core/create-ssl-cert boolean true
dovecot-core dovecot-core/ssl-cert-name string localhost
EOF
    }

    # Pre-fill question for sasl2-bin package if required
    if (`echo GET cyrus-sasl2/purge-sasldb2 | debconf-communicate sasl2-bin 2>/dev/null` =~ /^0/) {
        $fileContent .= <<'EOF'
sasl2-bin cyrus-sasl2/purge-sasldb2 boolean true
EOF
    }

    # Pre-fill questions for SQL server (MySQL, MariaDB or Percona) if required
    if (my ($sqlServerVendor, $sqlServerVersion) = $main::imscpConfig{'SQL_SERVER'} =~ /^(mysql|mariadb|percona)_(\d+\.\d+)/) {
        if ($main::imscpConfig{'DATABASE_PASSWORD'} ne '' && -d $main::imscpConfig{'DATABASE_DIR'}) {
            # Only show critical questions
            $ENV{'DEBIAN_PRIORITY'} = 'critical';

            # Allow switching to other vendor (e.g: MariaDB 10.0 to MySQL >= 5.6)
            # unlink glob "$main::imscpConfig{'DATABASE_DIR'}/debian-*.flag";

            # Don't show SQL root password dialog from package maintainer script
            # when switching to another vendor or a newest version
            # <DATABASE_DIR>/debian-5.0.flag is the file checked by maintainer script
            my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'DATABASE_DIR'}/debian-5.0.flag" )->save( );
            return $rs if $rs;
        }

        my ($qOwner, $qNamePrefix);
        if ($sqlServerVendor eq 'mysql') {
            if (grep($_ eq 'mysql-community-server', @{$self->{'packagesToInstall'}})) {
                $qOwner = 'mysql-community-server';
                $qNamePrefix = 'mysql-community-server';
            } else {
                $qOwner = 'mysql-server-'.$sqlServerVersion;
                $qNamePrefix = 'mysql-server';
            }
        } elsif ($sqlServerVendor eq 'mariadb') {
            $qOwner = 'mariadb-server-'.$sqlServerVersion;
            $qNamePrefix = 'mysql-server';
        } else {
            $qOwner = 'percona-server-server-'.$sqlServerVersion;
            $qNamePrefix = 'percona-server-server';
        }

        # We do not want ask user for <DATABASE_DIR> removal (we want avoid mistakes as much as possible)
        $fileContent .= <<"EOF";
$qOwner $qNamePrefix/remove-data-dir boolean false
$qOwner $qNamePrefix/postrm_remove_databases boolean false
EOF
        # Preset root SQL password using value from preseed file if required
        if (iMSCP::Getopt->preseed) {
            exit 5 unless exists $main::questions{'SQL_ROOT_PASSWORD'} & $main::questions{'SQL_ROOT_PASSWORD'} ne '';

            $fileContent .= <<"EOF";
$qOwner $qNamePrefix/root_password password $main::questions{'SQL_ROOT_PASSWORD'}
$qOwner $qNamePrefix/root-pass password $main::questions{'SQL_ROOT_PASSWORD'}
$qOwner $qNamePrefix/root_password_again password $main::questions{'SQL_ROOT_PASSWORD'}
$qOwner $qNamePrefix/re-root-pass password $main::questions{'SQL_ROOT_PASSWORD'}
EOF
            # Register an event listener to empty the password fields in Debconf database after package installation
            $self->{'eventManager'}->register(
                'postBuild',
                sub {
                    for('root_password', 'root-pass', 'root_password_again', 're-root-pass') {
                        my $rs = execute(
                            "echo SET $qNamePrefix/$_ | debconf-communicate $qOwner", \ my $stdout, \ my $stderr
                        );
                        debug( $stdout ) if $stdout;
                        error( $stderr || 'Unknown error' ) if $rs;
                        return $rs if $rs;
                    }

                    0;
                }
            );
        }
    }

    return 0 if $fileContent eq '';

    my $debconfSelectionsFile = File::Temp->new( );
    print $debconfSelectionsFile $fileContent;
    $debconfSelectionsFile->flush( );
    $debconfSelectionsFile->close( );

    my $rs = execute( [ 'debconf-set-selections', $debconfSelectionsFile->filename ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || "Couldn't pre-fill Debconf database" ) if $rs;
    $rs;
}

=item _rebuildAndInstallPackage( $pkg, $pkgSrc, $patchesDir [, $patchesToDiscard = [] [,  $patchFormat = 'quilt' ]] )

 Rebuild the given Debian package using patches from given directory and install the resulting local Debian package

 Note: It is assumed here that the Debian source package is dpatch or quilt ready.

 Param string $pkg Name of package to rebuild
 Param string $pkgSrc Name of source package
 Param string $patchDir Directory containing set of patches to apply on Debian package source
 param arrayref $patcheqToDiscad OPTIONAL List of patches to discard
 Param string $patchFormat OPTIONAL Patch format (quilt|dpatch) - Default quilt
 Return 0 on success, other on failure

=cut

sub _rebuildAndInstallPackage
{
    my ($self, $pkg, $pkgSrc, $patchesDir, $patchesToDiscard, $patchFormat) = @_;
    $patchesDir ||= "$pkg/patches";
    $patchesToDiscard ||= [ ];
    $patchFormat ||= 'quilt';

    unless (defined $pkg) {
        error( '$pkg parameter is not defined' );
        return 1;
    }
    unless (defined $pkgSrc) {
        error( '$pkgSrc parameter is not defined' );
        return 1;
    }
    unless ($patchFormat =~ /^(?:quilt|dpatch)$/) {
        error( 'Unsupported patch format.' );
        return 1;
    }

    local $ENV{'LANG'} = 'C';

    my $lsbRelease = iMSCP::LsbRelease->getInstance( );
    $patchesDir = "$FindBin::Bin/configs/".lc( $lsbRelease->getId( 1 ) )."/$patchesDir";
    unless (-d $patchesDir) {
        error( sprintf( '%s is not a valid patches directory', $patchesDir ) );
        return 1;
    }

    my $srcDownloadDir = File::Temp->newdir( CLEANUP => 1 );

    # Fix `W: Download is performed unsandboxed as root as file...' warning with newest APT versions
    if ((undef, undef, my $uid) = getpwnam('_apt')) {
        unless (chown $uid, -1, $srcDownloadDir) {
            error( sprintf( "Couldn't change ownership for the %s directory: %s", $srcDownloadDir, $! ) );
            return 1;
        }
    }

    # chdir() into download directory
    local $CWD = $srcDownloadDir;

    # Avoid pbuilder warning due to missing $HOME/.pbuilderrc file
    my $rs = iMSCP::File->new( filename => File::HomeDir->my_home.'/.pbuilderrc' )->save();
    return $rs if $rs;

    startDetail( );

    $rs = step(
        sub {
            if ($self->{'need_pbuilder_update'}) {
                $self->{'need_pbuilder_update'} = 0;

                my $msgHeader = "Creating/Updating pbuilder environment\n\n - ";
                my $msgFooter = "\n\nPlease be patient. This may take few minutes...";

                my $stderr = '';
                my $cmd = [
                    'pbuilder',
                    ( -f '/var/cache/pbuilder/base.tgz' ? ('--update', '--autocleanaptcache') : '--create'),
                    '--distribution', lc( $lsbRelease->getCodename( 1 ) ),
                    '--configfile', "$FindBin::Bin/configs/".lc( $lsbRelease->getId( 1 ) ).'/pbuilder/pbuilderrc',
                    '--override-config'
                ];
                $rs = executeNoWait(
                    $cmd,
                    (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                        ? undef : sub {
                            return unless (shift) =~ /^i:\s*(.*)/i;
                            step( undef, $msgHeader.ucfirst($1).$msgFooter, 5, 1);
                        }
                    ),
                    sub { $stderr .= shift; }
                );
                error(sprintf( "Couldn't create/update pbuilder environment: %s", $stderr || 'Unknown error' )) if $rs;
                return $rs if $rs;
            }
            0;
        },
        'Creating/Updating pbuilder environment', 5, 1
    );
    $rs ||= step(
        sub {
            my $msgHeader = sprintf( "Downloading %s %s source package\n\n - ", $pkgSrc, $lsbRelease->getId( 1 ) );
            my $msgFooter = "\nDepending on your system this may take few seconds...";

            my $stderr = '';
            $rs = executeNoWait(
                [ 'apt-get', '-y', 'source', $pkgSrc ],
                (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                    ? undef : sub { step( undef, $msgHeader.((shift) =~ s/^\s*//r).$msgFooter, 5, 2); }
                ),
                sub { $stderr .= shift }
            );
            error( sprintf( "Couldn't download %s Debian source package: %s", $pkgSrc,
                $stderr || 'Unknown error' ) ) if $rs;
            $rs;
        },
        sprintf( 'Downloading %s %s source package', $pkgSrc, $lsbRelease->getId( 1 ) ), 5, 2
    );

    {
        # chdir() into package source directory
        local $CWD = (<$pkgSrc-*>)[0];

        $rs ||= step(
            sub {
                my $serieFile = iMSCP::File->new(
                    filename => "debian/patches/".($patchFormat eq 'quilt' ? 'series' : '00list')
                );
                my $serieFileContent = $serieFile->get( );
                unless (defined $serieFileContent) {
                    error( sprintf( "Couldn't read %s", $serieFile->{'filename'} ) );
                    return 1;
                }

                for my $patch(sort { $a cmp $b } iMSCP::Dir->new( dirname => $patchesDir )->getFiles( )) {
                    next if grep($_ eq $patch, @{$patchesToDiscard});
                    $serieFileContent .= "$patch\n";
                    $rs = iMSCP::File->new( filename => "$patchesDir/$patch" )->copyFile(
                        "debian/patches/$patch", { preserve => 'no' }
                    );
                    return $rs if $rs;
                }

                $rs = $serieFile->set( $serieFileContent );
                $rs ||= $serieFile->save( );
                return $rs if $rs;

                my $stderr;
                $rs = execute(
                    [ 'dch', '--local', '~i-mscp-', 'Patched by i-MSCP installer for compatibility.' ],
                    (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \my $stdout),
                    \$stderr
                );
                debug($stdout) if $stdout;
                error( sprintf( "Couldn't add `imscp' local suffix: %s", $stderr || 'Unknown error' ) ) if $rs;
                return $rs if $rs;
            },
            sprintf( 'Patching %s %s source package...', $pkgSrc, $lsbRelease->getId( 1 ) ), 5, 3
        );
        $rs ||= step(
            sub {
                my $msgHeader = sprintf( "Building new %s %s package\n\n - ", $pkg, $lsbRelease->getId( 1 ) );
                my $msgFooter = "\n\nPlease be patient. This may take few seconds...";
                my $stderr;

                $rs = executeNoWait(
                    [
                        'pdebuild',
                        '--use-pdebuild-internal',
                        '--configfile', "$FindBin::Bin/configs/".lc( $lsbRelease->getId( 1 ) ).'/pbuilder/pbuilderrc'
                    ],
                    (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                        ? undef : sub {
                            return unless (shift) =~ /^i:\s*(.*)/i;
                            step( undef, $msgHeader.ucfirst($1).$msgFooter, 5, 4);
                        }
                    ),
                    sub { $stderr .= shift }
                );
                error(sprintf("Couldn't build local %s %s package: %s", $pkg, $lsbRelease->getId( 1 ),
                    $stderr || 'Unknown error')) if $rs;
                $rs;
            },
            sprintf( 'Building local %s %s package', $pkg, $lsbRelease->getId( 1 ) ), 5, 4
        );
    }

    $rs ||= step(
        sub {
            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( [ 'apt-mark', 'unhold', $pkg ], \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;

            my $msgHeader = sprintf( "Installing local %s %s package\n\n", $pkg, $lsbRelease->getId( 1 ) );
            $stderr = '';

            $rs = executeNoWait(
                "dpkg --force-confnew -i /var/cache/pbuilder/result/${pkg}_*.deb",
                (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                    ? undef : sub { step( undef, $msgHeader.(shift), 5, 5 ) }
                ),
                sub { $stderr .= shift }
            );
            error(sprintf("Couldn't install local %s %s package: %s", $pkg, $lsbRelease->getId( 1 ),
                $stderr || 'Unknown error')) if $rs;
            return $rs if $rs;

            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute([ 'apt-mark', 'hold', $pkg ], \$stdout, \$stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;
            0;
        },
        sprintf( 'Installing local %s %s package', $pkg, $lsbRelease->getId( 1 ) ), 5, 5
    );
    endDetail( );

    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
