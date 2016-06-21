=head1 NAME

 autoinstaller::Adapter::DebianAdapter - Debian autoinstaller adapter class

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Cwd;
use FindBin;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::LsbRelease;
use iMSCP::ProgramFinder;
use File::Temp;
use version;
use parent 'autoinstaller::Adapter::AbstractAdapter';

=head1 DESCRIPTION

 i-MSCP autoinstaller adapter implementation for Debian.

=head1 PUBLIC METHODS

=over 4

=item installPreRequiredPackages()

 Install pre-required packages

 Return int 0 on success, other on failure

=cut

sub installPreRequiredPackages
{
    my $self = shift;

    print STDOUT output( 'Satisfying prerequisites Please wait...', 'info' );

    my $rs = $self->_updateAptSourceList();
    $rs ||= $self->_updatePackagesIndex();

    $rs ||= $self->{'eventManager'}->trigger( 'beforeInstallPreRequiredPackages', $self->{'preRequiredPackages'} );
    return $rs if $rs;

    my $cmd = 'apt-get';
    die( 'Not a Debian like system' ) unless iMSCP::ProgramFinder::find( $cmd );

    if (!iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'debconf-apt-progress' )) {
        $cmd = "debconf-apt-progress --logstderr -- $cmd";
    }

    my $stdout;
    $rs = execute(
        "$cmd -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' --auto-remove --purge"
            ." --no-install-recommends install @{$self->{'preRequiredPackages'}}",
            iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef, \ my $stderr
    );
    error( sprintf( 'Could not install pre-required packages: %s', $stderr || 'Unknown error' ) ) if $rs;

    $rs ||= $self->{'eventManager'}->trigger( 'afterInstallPreRequiredPackages' );
}

=item preBuild()

 Process preBuild tasks

 Return int 0 on success, other on failure

=cut

sub preBuild
{
    my $self = shift;

    return 0 if $main::skippackages;

    my @steps = (
        [ sub { $self->_buildPackageList() }, 'Building list of packages to install/uninstall' ],
        [ sub { $self->_prefillDebconfDatabase() }, 'Pre-fill Debconf database' ],
        [ sub { $self->_processAptRepositories() }, 'Processing APT repositories if any' ],
        [ sub { $self->_processAptPreferences() }, 'Processing APT preferences if any' ],
        [ sub { $self->_updatePackagesIndex() }, 'Updating packages index' ]
    );

    my $step = 1;
    my $nbSteps = scalar @steps;
    for (@steps) {
        my $rs = step( $_->[0], $_->[1], $nbSteps, $step );
        return $rs if $rs;
        $step++;
    }

    0
}

=item installPackages()

 Install Debian packages

 Return int 0 on success, other on failure

=cut

sub installPackages
{
    my $self = shift;

    my $rs = $self->_setupInitScriptPolicyLayer( 'enable' );
    $rs ||= $self->uninstallPackages( $self->{'packagesToPreUninstall'} );
    $rs ||= $self->{'eventManager'}->trigger(
        'beforeInstallPackages', $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}
    );
    return $rs if $rs;

    iMSCP::Dialog->getInstance->endGauge();

    for my $packages($self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}) {
        next unless @{$packages};

        my $cmd = 'UCF_FORCE_CONFFMISS=1'; # Force installation of missing conffiles which are managed by UCF
        $cmd .= !iMSCP::Getopt->noprompt ? ' debconf-apt-progress --logstderr --' : '';

        if ($main::forcereinstall) {
            $cmd .= " apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss'".
                " -o Dpkg::Options::='--force-overwrite' --reinstall --auto-remove --purge --no-install-recommends".
                " --force-yes install @{$packages}";
        } else {
            $cmd .= " apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss'".
                " -o Dpkg::Options::='--force-overwrite' --auto-remove --purge --no-install-recommends --force-yes".
                " install @{$packages}";
        }

        my $stdout;
        $rs = execute( $cmd, iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef, \ my $stderr );
        error( sprintf( 'Could not install packages: %s', $stderr || 'Unknown error' ) ) if $rs;
        return $rs if $rs;
    }

    while(my ($package, $metadata) = each( %{$self->{'packagesToRebuild'}} )) {
        $rs = $self->_rebuildAndInstallPackage(
            $package, $metadata->{'pkg_src_name'}, $metadata->{'patches_directory'}, $metadata->{'discard_patches'},
            $metadata->{'patch_sys_type'}
        );
        return $rs if $rs;
    }

    $rs ||= $self->_setupInitScriptPolicyLayer( 'disable' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterInstallPackages' );
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

    eval "use List::MoreUtils qw/ uniq /; 1" or die( $@ );

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

        # Filter list of packages that are not not available
        execute( "dpkg-query -W -f='\${Package}\\n' @{$packagesToUninstall} 2>/dev/null", \ $stdout );
        @{$packagesToUninstall} = split /\n/, $stdout;

        if (@{$packagesToUninstall}) {
            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( "apt-mark unhold @{$packagesToUninstall}", \ $stdout, \ $stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;

            iMSCP::Dialog->getInstance->endGauge();

            my $cmd = !iMSCP::Getopt->noprompt ? 'debconf-apt-progress --logstderr -- ' : '';
            $cmd .= "apt-get -y --auto-remove --purge --no-install-recommends remove @{$packagesToUninstall}";
            my $rs = execute( $cmd, iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef, \ $stderr );
            error( sprintf( 'Could not uninstall packages: %s', $stderr || 'Unknown error' ) ) if $rs;
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterUninstallPackages' );
}

=item postBuild()

 Process postBuild tasks

 Return int 0 on success, other on failure

=cut

sub postBuild
{
    0
}

=back

=head1 PRIVATE METHODS/FUNCTIONS

=over 4

=item _init()

 Initialize instance

 Return autoinstaller::Adapter::DebianAdapter

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'repositorySections'} = [ 'main', 'contrib', 'non-free' ];
    $self->{'preRequiredPackages'} = [
        'binutils', 'debconf-utils', 'dialog', 'libbit-vector-perl', 'libclass-insideout-perl',
        'liblist-moreutils-perl', 'libscalar-defer-perl', 'libsort-versions-perl', 'libxml-simple-perl', 'wget', 'rsync'
    ];
    $self->{'aptRepositoriesToRemove'} = [ ];
    $self->{'aptRepositoriesToAdd'} = [ ];
    $self->{'aptPreferences'} = [ ];
    $self->{'packagesToInstall'} = [ ];
    $self->{'packagesToInstallDelayed'} = [ ];
    $self->{'packagesToPreUninstall'} = [ ];
    $self->{'packagesToUninstall'} = [ ];
    $self->{'packagesToRebuild'} = { };
    $self->{'need_pbuilder_update'} = 1;
    delete $ENV{'DEBCONF_FORCE_DIALOG'};
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->noprompt;
    delete $ENV{'UPSTART_SESSION'}; # See IP-1514
    $ENV{'DEBFULLNAME'} = 'i-MSCP Installer';
    $ENV{'DEBEMAIL'} = 'team@i-mscp.net';
    $self;
}

=item _setupInitScriptPolicyLayer($action)

 Enable or disable initscript policy layer

 See https://people.debian.org/~hmh/invokerc.d-policyrc.d-specification.txt
 See man invoke-rc.d

 Param string $action Action ( enable|disable )
 Return int 0 on success, other on failure

=cut

sub _setupInitScriptPolicyLayer
{
    my ($self, $action) = @_;

    if ($action eq 'enable') {
        # Prevents invoke-rc.d (which is invoked by package maintainer scripts) to start some services
        # Apache2: Prevent "bind() to 0.0.0.0:80 failed (98: Address already in use" failure
        # Nginx:   Prevent "bind() to 0.0.0.0:80 failed (98: Address already in use" failure
        # Nginx:   Prevent start failure when IPv6 stack is not enabled
        # Dovecot: Prevent start failure when IPv6 stack is not enabled
        # bind9:   Prevent failure when resolvconf is not configured yet
        my $file = iMSCP::File->new( filename => '/usr/sbin/policy-rc.d' );
        my $rs = $file->set( <<'EOF' );
#/bin/sh
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

        $rs ||= $file->save();
        $rs ||= $file->mode( 0755 );
        return $rs;
    }

    if ($action eq 'disable' && -f '/usr/sbin/policy-rc.d') {
        return iMSCP::File->new( filename => '/usr/sbin/policy-rc.d' )->delFile();
    }

    0;
}

=item _buildPackageList()

 Build lists of Debian packages to uninstall and install

 Return int 0 on success, other on failure

=cut

sub _buildPackageList
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'onBuildPackageList', \ my $pkgFile );
    return $rs if $rs;

    unless (defined $pkgFile) {
        my $lsbRelease = iMSCP::LsbRelease->getInstance();
        my $dist = lc( $lsbRelease->getId( 'short' ) );
        my $codename = lc( $lsbRelease->getCodename( 'short' ) );
        $pkgFile = "$FindBin::Bin/docs/".ucfirst( $dist )."/packages-$codename.xml";
    }

    eval "use XML::Simple; 1" or die( $@ );
    my $xml = XML::Simple->new( NoEscape => 1 );
    my $pkgData = eval { $xml->XMLin( $pkgFile, ForceArray => [ 'package', 'package_delayed', 'package_conflict' ] ) };
    if ($@) {
        error( $@ );
        return 1;
    }

    while(my ($section, $data) = each( %{$pkgData} )) {
        # Simple list of packages to install
        if ($data->{'package'}) {
            for(@{$data->{'package'}}) {
                push @{$self->{'packagesToInstall'}}, ref $_ eq 'HASH' ? $_->{'content'} : $_;
            }
            next;
        }
        if ($data->{'package_delayed'}) {
            push @{$self->{'packagesToInstallDelayed'}}, @{$data->{'package_delayed'}};
            next;
        }

        # Alternative list of package to install
        my $dAlt = delete $data->{'default'};
        my $sAlt = $main::questions{ uc( $section ).'_SERVER' } || $main::imscpConfig{ uc( $section ).'_SERVER' };
        my $forceDialog = $sAlt eq '';
        $sAlt = $dAlt if $forceDialog;

        my @alts = keys %{$data};
        if (!$forceDialog && !grep($_ eq $sAlt, @alts)) {
            # Handle wrong or deprecated entry case
            $sAlt = $dAlt;
            $forceDialog = 1;
        }

        if (!$forceDialog && $data->{$sAlt}->{'allow_switch'}) {
            # Filter unallowed alternatives
            @alts = grep {
                my $__ = $_;
                grep($_ eq $__, @alts)
            } split( ',', $data->{$sAlt}->{'allow_switch'} ), $sAlt;
        }

        # Ask user for alternative list of packages to install if any
        if (@alts > 1 && ($forceDialog || grep($_ eq $main::reconfigure, ( $section, 'servers', 'all' )))) {
            iMSCP::Dialog->getInstance()->set( 'no-cancel', '' );
            (my $ret, $sAlt) = iMSCP::Dialog->getInstance()->radiolist( <<"EOF", [ sort @alts ], $sAlt );

Please, choose the server you want use for the $section service:
EOF
            return $ret if $ret; # Handle ESC case
            iMSCP::Dialog->getInstance()->set( 'no-cancel' );
        }

        while(my ($alt, $altData) = each( %{$data} )) {
            # We cannot use filtered @alts variable
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
                'pinning_package'      => $data->{$sAlt}->{'pinning_package'},
                'pinning_pin'          => $data->{$sAlt}->{'pinning_pin'} || undef,
                'pinning_pin_priority' => $data->{$sAlt}->{'pinning_pin_priority'} || undef,
            } if $data->{$sAlt}->{'pinning_package'};

        # Conflicting APT repository to remove
        push @{$self->{'aptRepositoriesToRemove'}}, $data->{$sAlt}->{'repository_conflict'}
            if $data->{$sAlt}->{'repository_conflict'};

        # APT repository to add
        if ($data->{$sAlt}->{'repository'}) {
            push @{$self->{'aptRepositoriesToAdd'}},
                {
                    'repository'         => $data->{$sAlt}->{'repository'},
                    'repository_key_uri' => $data->{$sAlt}->{'repository_key_uri'} || undef,
                    'repository_key_id'  => $data->{$sAlt}->{'repository_key_id'} || undef,
                    'repository_key_srv' => $data->{$sAlt}->{'repository_key_srv'} || undef
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
                        $self->{packagesToRebuild}->{$_->{'content'}} = {
                            pkg_src_name      => $_->{'pkg_src_name'} || $_->{'content'},
                            patches_directory => $_->{'rebuild_with_patches'},
                            discard_patches   => [ $_->{'discard_patches'} ? split ',', $_->{'discard_patches'} : () ],
                            patch_sys_type    => $_->{'patch_sys_type'} || 'quilt',
                        };
                    } else {
                        push @{$self->{'packagesToInstall'}}, $_->{'content'};
                    }
                } else {
                    push @{$self->{'packagesToInstall'}}, $_;
                }
            }
        }

        # Package to install (delayed)
        push @{$self->{'packagesToInstallDelayed'}}, @{$data->{$sAlt}->{'package_delayed'}}
            if $data->{$sAlt}->{'package_delayed'};

        # Set server implementation to use
        $main::imscpConfig{uc( $section ).'_SERVER'} = $sAlt;
    }

    0;
}

=item _updateAptSourceList()

 Add required sections to repositories that support them

 Note: Also enable source repositories for the sections when available.

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
    my $self = shift;

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    unless (-f '/etc/apt/sources.list.bkp') {
        my $rs = $file->copyFile( '/etc/apt/sources.list.bkp' );
        return $rs;
    }

    my $fileContent = $file->get();
    my $fsec = 0;

    for my $sec(@{$self->{'repositorySections'}}) {
        my @seen = ();

        while($fileContent =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/gm) {
            my $rf = $&;
            my %rc = %+;
            next if grep($_ eq "$rc{'uri'} $rc{'distrib'}", @seen);

            if ($fileContent !~ /^deb\s+$rc{'uri'}\s+\b$rc{'distrib'}\b\s+.*\b$sec\b/m) {
                my $rs = execute( "wget --spider $rc{'uri'}/dists/$rc{'distrib'}/$sec/", \ my $stdout, \ my $stderr );
                debug( $stderr ) if $rs && $stderr;
                unless ($rs) {
                    $fsec = 1;
                    $fileContent =~ s/^($rf)$/$1 $sec/m;
                    $rf .= " $sec";
                }
            } else {
                $fsec = 1;
            }

            if ($fsec && $fileContent !~ /^deb-src\s+$rc{'uri'}\s+\b$rc{'distrib'}\b\s+.*\b$sec\b/m) {
                my $rs = execute(
                    "wget --spider $rc{'uri'}/dists/$rc{'distrib'}/$sec/source/", \ my $stdout, \ my $stderr );
                debug( $stderr ) if $rs && $stderr;

                unless ($rs) {
                    if ($fileContent !~ /^deb-src\s+$rc{'uri'}\s+$rc{'distrib'}\s.*/m) {
                        $fileContent =~ s/^($rf)/$1\ndeb-src $rc{'uri'} $rc{'distrib'} $sec/m;
                    } else {
                        $fileContent =~ s/^($&)$/$1 $sec/m;
                    }
                }
            }

            push @seen, "$rc{'uri'} $rc{'distrib'}";
        }

        unless ($fsec) {
            error( sprintf( 'Could not find repository supporting %s section', $sec ) );
            return 1;
        }
    }

    $file->set( $fileContent );
    $file->save();
}

=item _processAptRepositories()

 Process APT repositories

 Return int 0 on success, other on failure

=cut

sub _processAptRepositories
{
    my $self = shift;

    return 0 unless @{$self->{'aptRepositoriesToRemove'}} || @{$self->{'aptRepositoriesToAdd'}};

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    my $rs = $file->copyFile( '/etc/apt/sources.list.bkp' );
    return $rs if $rs;

    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( 'Could not read /etc/apt/sources.list file' );
        return 1;
    }

    # Cleanup APT sources.list file
    for my $repository(@{$self->{'aptRepositoriesToRemove'}}, @{$self->{'aptRepositoriesToAdd'}}) {
        my $escapedRepository = quotemeta( ref $repository eq 'HASH' ? $repository->{'repository'} : $repository );
        $fileContent =~ s/^\n?(?:#\s*)?deb(?:-src)?\s+$escapedRepository.*?\n//gm;
    }

    # Add APT repositories
    for my $repository(@{$self->{'aptRepositoriesToAdd'}}) {
        next if $fileContent =~ /^deb\s+$repository->{'repository'}/m;

        $fileContent .= <<"EOF";

deb $repository->{'repository'}
deb-src $repository->{'repository'}
EOF

        # Only process if we have a repository key identifier and if gpg key is not already present
        next unless $repository->{'repository_key_id'} &&
            execute( "apt-key adv -k $repository->{'repository_key_id'} >/dev/null 2>&1" ) != 0;

        my @cmd = ();
        if ($repository->{'repository_key_srv'}) { # Add the repository key from the given key server
            @cmd = (
                'apt-key adv --recv-keys --keyserver',
                escapeShell( $repository->{'repository_key_srv'} ),
                escapeShell( $repository->{'repository_key_id'} )
            );
        } elsif ($repository->{'repository_key_uri'}) { # Add the repository key by fetching it from the given URI
            @cmd = ('wget -qO-', escapeShell( $repository->{'repository_key_uri'} ), '| apt-key add -');
        }

        if (@cmd) {
            $rs = execute( "@cmd", \ my $stdout, \ my $stderr );
            debug( $stdout ) if $stdout;
            error( $stderr ) if $stderr && $rs;
            return $rs if $rs;
        }
    }

    # Save new sources.list file
    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=item _processAptPreferences()

 Process apt preferences

 Return 0 on success, other on failure

=cut

sub _processAptPreferences
{
    my $self = shift;

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
        my $rs = $file->set( $fileContent );
        $rs ||= $file->save();
        $rs ||= $file->mode( 0644 );
        return $rs;
    }

    if (-f '/etc/apt/preferences.d/imscp') {
        return $file->delFile();
    }

    0;
}

=item _updatePackagesIndex()

 Update Debian packages index

 Return int 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
    my $self = shift;

    my $cmd = 'apt-get';
    unless (iMSCP::Getopt->noprompt) {
        iMSCP::Dialog->getInstance->endGauge() if iMSCP::ProgramFinder::find( 'dialog' );
        $cmd = "debconf-apt-progress --logstderr -- $cmd";
    }

    my $stdout;
    my $rs = execute(
        "$cmd -y update", iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef, \ my $stderr
    );
    error( sprintf( 'Could not update package index from remote repository: %s', $stderr || 'Unknown error' ) ) if $rs;
    debug( $stderr );
    $rs
}

=item _prefillDebconfDatabase()

 Pre-fill debconf database

 Return int 0 on success, other on failure

=cut

sub _prefillDebconfDatabase
{
    my $self = shift;

    if ($main::imscpConfig{'DATABASE_PASSWORD'} && -d '/var/lib/mysql') {
        # Only show critical questions
        $ENV{'DEBIAN_PRIORITY'} = 'critical';

        # Allow switching to other vendor (e.g: MariaDB 10.0 to MySQL >= 5.6)
        unlink glob '/var/lib/mysql/debian-*.flag';

        # Don't show SQL root password dialog from package maintainer script
        # when switching to another vendor or a newest version
        # /var/lib/mysql/debian-5.0.flag is the file checked by maintainer script (even for newest versions...)
        my $rs = iMSCP::File->new( filename => '/var/lib/mysql/debian-5.0.flag' )->save();
        return $rs if $rs;
    }

    my $poServer = $main::imscpConfig{'PO_SERVER'};

    my ($sqlServer, $sqlServerVersion) = ('remote_server', undef);
    my ($sqlServerQuestionOwner, $sqlServerQuestionPrefix);

    if ($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
        ($sqlServer, $sqlServerVersion) = $main::imscpConfig{'SQL_SERVER'} =~ /^(mysql|mariadb|percona)_(\d+\.\d+)$/;

        if ($sqlServer eq 'mysql') {
            if (grep($_ eq 'mysql-community-server', @{$self->{'packagesToInstall'}})) {
                $sqlServerQuestionOwner = 'mysql-community-server';
                $sqlServerQuestionPrefix = 'mysql-community-server';
            } else {
                $sqlServerQuestionOwner = 'mysql-server-'.$sqlServerVersion;
                $sqlServerQuestionPrefix = 'mysql-server';
            }
        } elsif ($sqlServer eq 'mariadb') {
            $sqlServerQuestionOwner = 'mariadb-server-'.$sqlServerVersion;
            $sqlServerQuestionPrefix = 'mysql-server';
        } else {
            $sqlServerQuestionOwner = 'percona-server-server-'.$sqlServerVersion;
            $sqlServerQuestionPrefix = 'percona-server-server';
        }
    }

    # Most values below are not really important because i-MSCP will override them after package installation
    my $mailname = `hostname --fqdn 2>/dev/null` || 'localdomain';
    chomp $mailname;
    my $hostname = ($mailname ne 'localdomain') ? $mailname : 'localhost';
    my $domain = `hostname --domain 2>/dev/null` || 'localdomain';
    chomp $domain;

    # From postfix package postfix.config script
    my $destinations;
    if ($mailname eq $hostname) {
        $destinations = join ', ', ($mailname, 'localhost.'.$domain, ', localhost');
    } else {
        $destinations = join ', ', ($mailname, $hostname, 'localhost.'.$domain.', localhost');
    }

    my $selectionsFileContent = <<"EOF";
postfix postfix/main_mailer_type select Internet Site
postfix postfix/mailname string $mailname
postfix postfix/destinations string $destinations
proftpd-basic shared/proftpd/inetd_or_standalone select standalone
EOF

    if ($poServer eq 'courier') {
        $selectionsFileContent .= <<"EOF";
courier-base courier-base/webadmin-configmode boolean false
courier-ssl courier-ssl/certnotice note
EOF
    } elsif ($poServer eq 'dovecot') {
        $selectionsFileContent .= <<"EOF";
dovecot-core dovecot-core/create-ssl-cert boolean true
dovecot-core dovecot-core/ssl-cert-name string localhost
EOF
    }

    # Set default answer to yes for purge of sasldb2 database
    $selectionsFileContent .= <<"EOF";
sasl2-bin cyrus-sasl2/purge-sasldb2 boolean true
EOF

    # We do not want ask user for /var/lib/mysql removal (we want avoid mistakes as much as possible)
    if ($sqlServer eq 'mariadb') {
        # There is a bug in mariadb-server-* packages (wrong version used for question prefix)
        $selectionsFileContent .= <<"EOF";
$sqlServerQuestionOwner $sqlServerQuestionPrefix-5.1/postrm_remove_databases boolean false
$sqlServerQuestionOwner $sqlServerQuestionPrefix-5.1/really_downgrade boolean true
EOF
    } elsif (grep($_ eq 'mysql-community-server', @{$self->{'packagesToInstall'}})) {
        $selectionsFileContent .= <<"EOF";
$sqlServerQuestionOwner $sqlServerQuestionOwner/remove-data-dir boolean false
EOF
    } elsif ($sqlServer ne 'remote_server') {
        $selectionsFileContent .= <<"EOF";
$sqlServerQuestionOwner $sqlServerQuestionOwner/postrm_remove_databases boolean false
EOF
    }

    if ($sqlServer ne 'remote_server' && iMSCP::Getopt->preseed && $sqlServerQuestionOwner) {
        $selectionsFileContent .= <<"EOF";
$sqlServerQuestionOwner $sqlServerQuestionPrefix/root_password password $main::questions{'SQL_ROOT_USER'}
$sqlServerQuestionOwner $sqlServerQuestionPrefix/root_password_again password $main::questions{'SQL_ROOT_PASSWORD'};
EOF
    }

    my $debconfSelectionsFile = File::Temp->new();
    print $debconfSelectionsFile $selectionsFileContent;
    $debconfSelectionsFile->flush();

    my $rs = execute( "debconf-set-selections $debconfSelectionsFile", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $rs && $stderr;
    error( 'Could not pre-fill Debconf database' ) if $rs && !$stderr;
    $rs;
}

=item _rebuildAndInstallPackage( $pkg, $pkgSrc, $patchesDir [, $patchesToDiscard = [], [ $patchSysType = 'quilt' ]] )

 Rebuild the given Debian package using patches from given directory and install the resulting local Debian package

 Note: It is assumed here that the Debian source package is dpatch or quilt ready.

 Param string $pkg Name of package to rebuild
 Param string $pkgSrc Name of source package
 Param string $patchDir Directory containing set of patches to apply on Debian package source
 param arrayref $patcheqToDiscad List of patches to discard
 Param string $patchSysType Patch system type (quilt|dpatch)
 Return 0 on success, other on failure

=cut

sub _rebuildAndInstallPackage
{
    my ($self, $pkg, $pkgSrc, $patchesDir, $patchesToDiscard, $patchSysType) = @_;
    $patchesDir ||= "$pkg/patches";
    $patchesToDiscard ||= [ ];
    $patchSysType ||= 'quilt';

    unless (defined $pkg) {
        error( '$pkg parameter is not defined' );
        return 1;
    }
    unless (defined $pkgSrc) {
        error( '$pkgSrc parameter is not defined' );
        return 1;
    }
    unless ($patchSysType =~ /^(?:quilt|dpatch)$/) {
        error( 'Unsupported patch system.' );
        return 1;
    }

    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    $patchesDir = "$FindBin::Bin/configs/".lc( $lsbRelease->getId( 1 ) )."/$patchesDir";
    unless (-d $patchesDir) {
        error( sprintf( '%s is not a valid patches directory', $patchesDir ) );
        return 1;
    }

    my $oldDir = cwd();
    my $srcDir = File::Temp->newdir( CLEANUP => 1 );
    unless (chdir $srcDir) {
        error( sprintf( 'Could not change current directory to: %s', $srcDir, $! ) );
        return 1;
    }

    startDetail();

    my $rs = step(
        sub {
            if ($self->{'need_pbuilder_update'}) {
                $self->{'need_pbuilder_update'} = 0;
                my $dialog = iMSCP::Dialog->getInstance();
                my $msgHeader = "Creating/Updating pbuilder environment\n\n";
                my $msgFooter = "\nPlease wait, depending on your connection, this may take few minutes...";
                my $cmd = [
                    'pbuilder',
                    ( -f '/var/cache/pbuilder/base.tgz' ? ('--update', '--autocleanaptcache') : '--create'),
                    '--distribution', lc( $lsbRelease->getCodename( 1 ) ),
                    '--configfile', "$FindBin::Bin/configs/".lc( $lsbRelease->getId( 1 ) ).'/pbuilder/pbuilderrc',
                    '--override-config'
                ];
                my $stderr;
                my $rs = executeNoWait(
                    $cmd,
                    (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : sub {
                            my $lines = shift;
                            open( my $fh, '<', \$lines ) or die ( $! );
                            step( undef, $msgHeader.ucfirst( s/^I:\s+(.*)/$1/r ).$msgFooter, 5, 1 ) while <$fh>;
                            close( $fh );
                        }
                    ),
                    sub { $stderr .= shift; }
                );
                error(
                    sprintf( 'Could not create/update pbuilder environment: %s', $stderr || 'Unknown error' )
                ) if $rs;
                return $rs if $rs;
            }
            0;
        },
        "Creating/Updating pbuilder environment", 5, 1
    );
    $rs ||= step(
        sub {
            my $rs = execute(
                "apt-get -y source $pkgSrc",
                (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ my $stdout),
                \ my $stderr
            );
            error( sprintf( 'Could not get %s Debian source package: %s', $pkgSrc,
                    $stderr || 'Unknown error' ) ) if $rs;
            $rs;
        },
        sprintf( 'Downloading %s %s source package...', $pkgSrc, $lsbRelease->getId( 1 ) ), 5, 2
    );
    $rs ||= step(
        sub {
            (my $pkgSrcDir) = <$pkgSrc-*>;
            unless (chdir $pkgSrcDir) {
                error( sprintf( 'Could not change current directory to %s: %s', $pkgSrcDir, $! ) );
                return 1;
            }

            my $serieFile = $patchSysType eq 'quilt' ? 'series' : '00list';
            my $file = iMSCP::File->new( filename => "debian/patches/$serieFile" );
            my $fileContent = $file->get();
            unless (defined $fileContent) {
                error( sprintf( 'Could not read %s', $file->{'filename'} ) );
                return 1;
            }

            for my $patch(sort { $a cmp $b } iMSCP::Dir->new( dirname => $patchesDir )->getFiles()) {
                next if grep($_ eq $patch, @{$patchesToDiscard});
                $fileContent .= "$patch\n";
                $rs = iMSCP::File->new( filename => "$patchesDir/$patch" )->copyFile( "debian/patches/$patch" );
                return $rs if $rs;
            }

            $rs = $file->set( $fileContent );
            $rs ||= $file->save();
        },
        sprintf( 'Copying i-MSCP patches into %s %s source package...', $pkgSrc, $lsbRelease->getId( 1 ) ), 5, 3
    );
    $rs ||= step(
        sub {
            my $rs = execute(
                "dch --local '~i-mscp-' 'Automatically patched by i-MSCP installer for compatibility.'",
                \ my $stdout,
                \ my $stderr
            );
            error( sprintf( 'Could not add `imscp` local suffix: %s', $stderr || 'Unknown error' ) ) if $rs;
            return $rs if $rs;

            $rs = execute(
                [
                    'pdebuild',
                    '--use-pdebuild-internal',
                    '--debbuildopts', '-b',
                    '--',
                    '--debemail', 'i-MSCP Installer <team@i-mscp.net>'
                ],
                (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout),
                \ $stderr
            );
            error(
                sprintf(
                    'Could not build local %s %s package: %s', $pkg, $lsbRelease->getId( 1 ), $stderr || 'Unknown error'
                )
            ) if $rs;
            $rs;
        },
        sprintf( 'Building local %s %s package...', $pkg, $lsbRelease->getId( 1 ) ), 5, 4
    );
    $rs ||= step(
        sub {
            unless (chdir '..') {
                error( sprintf( 'Could not change directory: %s', $! ) );
                return 1;
            }

            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( "LANG=C apt-mark unhold $pkg", \ my $stdout, \ my $stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;

            my $rs = execute(
                "dpkg --force-confnew -i /var/cache/pbuilder/result/${pkg}_*.deb",
                (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout),
                \ $stderr
            );
            error(
                sprintf(
                    'Could not install local %s %s package: %s', $pkg, $lsbRelease->getId( 1 ),
                    $stderr || 'Unknown error'
                )
            ) if $rs;
            return $rs if $rs;

            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( "LANG=C apt-mark hold $pkg", \ $stdout, \ $stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;
            0;
        },
        sprintf( 'Installing local %s %s package...', $pkg, $lsbRelease->getId( 1 ) ), 5, 5
    );
    endDetail();

    unless (chdir $oldDir) {
        error( sprintf( 'Could not change current directory to %s: %s', $oldDir, $! ) );
        return 1;
    }

    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
