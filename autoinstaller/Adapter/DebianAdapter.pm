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
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Stepper;
use iMSCP::ProgramFinder;
use File::Temp;
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

    $self->{'eventManager'}->trigger( 'beforeInstallPreRequiredPackages', $self->{'preRequiredPackages'} );

    my $command = 'apt-get';
    die( 'Not a Debian like system' ) unless iMSCP::ProgramFinder::find( $command );

    # Ensure packages index is up to date
    my $rs = $self->_updatePackagesIndex();
    return $rs if $rs;

    if (!iMSCP::Getopt->preseed && !iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'debconf-apt-progress' )) {
        $command = 'debconf-apt-progress --logstderr -- '.$command;
    }

    my $stdout;
    $rs = execute(
        "$command -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' --auto-remove --purge ".
            "--no-install-recommends install @{$self->{'preRequiredPackages'}}",
            (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt) ? \$stdout : undef, \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( 'Could not install pre-required packages' ) if $rs && !$stderr;
    return $rs if $rs;

    $self->{'eventManager'}->trigger( 'afterInstallPreRequiredPackages' );
}

=item preBuild()

 Process preBuild tasks

 Return int 0 on success, other on failure

=cut

sub preBuild
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforePreBuild' );
    return $rs if $rs;

    unless ($main::skippackages) {
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
            $rs = step( $_->[0], $_->[1], $nbSteps, $step );
            return $rs if $rs;
            $step++;
        }
    }

    $self->{'eventManager'}->trigger( 'afterPreBuild' );
}

=item installPackages()

 Install Debian packages

 Return int 0 on success, other on failure

=cut

sub installPackages
{
    my $self = shift;

    iMSCP::Dialog->getInstance()->endGauge();

    # Remove packages which must be pre-removed
    my $rs = $self->uninstallPackages( $self->{'packagesToPreUninstall'} );
    return $rs if $rs;

    $rs = $self->{'eventManager'}->trigger(
        'beforeInstallPackages', $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}
    );
    return $rs if $rs;

    for my $packages($self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}) {
        next unless @{$packages};

        my @command = ();
        if (!iMSCP::Getopt->preseed && !iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'debconf-apt-progress' )) {
            push @command, 'debconf-apt-progress --logstderr --';
        }

        unshift @command, 'UCF_FORCE_CONFFMISS=1 '; # Force installation of missing conffiles which are managed by UCF

        if ($main::forcereinstall) {
            push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' ".
                    "-o Dpkg::Options::='--force-overwrite' --reinstall --auto-remove --purge --no-install-recommends ".
                    "--force-yes install @{$packages}";
        } else {
            # -o Dpkg::Options::='--force-overwrite'
            push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' ".
                    "-o Dpkg::Options::='--force-overwrite' --auto-remove --purge --no-install-recommends --force-yes ".
                    "install @{$packages}";
        }

        my $stdout;
        $rs = execute( "@command", (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt) ? \$stdout : undef,
            \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        error( 'Could not install packages' ) if $rs && !$stderr;
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterInstallPackages' );
}

=item uninstallPackages([ \@packages ])

 Uninstall Debian packages

 Param array \@packages OPTIONAL List of packages to uninstall (default is list from the packagesToUninstall attribute)
 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
    my ($self, $packages) = @_;

    $packages ||= $self->{'packagesToUninstall'};

    eval "use List::MoreUtils qw(uniq); 1";
    die( $@ ) if $@;

    # Filter packages that must not be removed
    my @packagesToIgnore = (@{$self->{'packagesToInstall'}}, @{$self->{'packagesToInstallDelayed'}});
    s/=.*$// for @packagesToIgnore; # Remove any package version info (since 1.2.12)
    @{$packages} = grep { my $__ = $_; !grep($_ eq $__, @packagesToIgnore) } uniq( @{$packages} );

    if (@{$packages}) {
        # Do not try to remove packages that are not installed or not available
        my $rs = execute(
            "dpkg-query -W -f='\${Package} \${Version}\n' @{$packages} 2>/dev/null ".
                "| grep '[[:blank:]][[:alnum:]]' | cut -d ' ' -f 1",
            \my $stdout,
            \my $stderr
        );
        error( $stderr ) if $stderr && $rs > 1;
        return $rs if $rs > 1;

        @{$packages} = split /\n/, $stdout;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeUninstallPackages', $packages );
    return $rs if $rs;

    if (@{$packages}) {
        # Ensure that packages are not frozen
        # # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
        execute( "LANG=C apt-mark unhold @{$packages}", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        debug( $stderr ) if $stderr;

        my @command = ();

        if (!iMSCP::Getopt->preseed && !iMSCP::Getopt->noprompt
            && iMSCP::ProgramFinder::find( 'debconf-apt-progress' )
        ) {
            iMSCP::Dialog->getInstance()->endGauge();
            push @command, 'debconf-apt-progress --logstderr --';
        }

        push @command, "apt-get -y --auto-remove --purge --no-install-recommends remove @{$packages}";

        my $rs = execute( "@command", (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt) ? \$stdout : undef,
            \$stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        error( 'Could not uninstall packages' ) if $rs && !$stderr;
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterUninstallPackages' );
}

=item postBuild()

 Process postBuild tasks

 Return int 0 on success, other on failure

=cut

sub postBuild
{
    my $self = shift;

    # Needed to fix #IP-1246
    if (iMSCP::ProgramFinder::find( 'php5dismod' )) {
        for my $module(
            'apc', 'curl', 'gd', 'imap', 'intl', 'json', 'mcrypt', 'mysqlnd', 'mysqli', 'mysql', 'opcache', 'pdo',
            'pdo_mysql'
        ) {
            my $rs = execute( "php5dismod $module", \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            unless (grep($_ eq $rs, ( 0, 2 ))) {
                error( $stderr ) if $stderr;
                return $rs;
            }
        }
    }

    # Enable needed PHP modules (only if they are available)
    if (iMSCP::ProgramFinder::find( 'php5enmod' )) {
        for my $module(
            'apc', 'curl', 'gd', 'imap', 'intl', 'json', 'mcrypt', 'mysqlnd/10', 'mysqli', 'mysql', 'opcache', 'pdo/10',
            'pdo_mysql'
        ) {
            my $rs = execute( "php5enmod $module", \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            unless (grep($_ eq $rs, ( 0, 2 ))) {
                error( $stderr ) if $stderr;
                return $rs;
            }
        }
    }

    $self->_setupInitScriptPolicyLayer( 'disable' );
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
    $self->{'repositorySections'} = [ 'main', 'non-free' ];
    $self->{'preRequiredPackages'} = [
        'debconf-utils', 'binutils', 'dialog', 'libbit-vector-perl', 'libclass-insideout-perl',
        'liblist-moreutils-perl', 'libscalar-defer-perl', 'libsort-versions-perl', 'libxml-simple-perl', 'wget', 'rsync'
    ];
    $self->{'aptRepositoriesToRemove'} = [ ];
    $self->{'aptRepositoriesToAdd'} = [ ];
    $self->{'aptPreferences'} = [ ];
    $self->{'packagesToInstall'} = [ ];
    $self->{'packagesToInstallDelayed'} = [ ];
    $self->{'packagesToPreUninstall'} = [ ];
    $self->{'packagesToUninstall'} = [ ];

    delete $ENV{'DEBCONF_FORCE_DIALOG'};
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt;

    delete $ENV{'UPSTART_SESSION'}; # See IP-1514

    unless ($main::skippackages) {
        $self->_setupInitScriptPolicyLayer( 'enable' ) == 0 or die( 'Could not setup initscript policy layer' );
        $self->_updateAptSourceList() == 0 or die( 'Could not configure APT packages manager' );
    }

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
        # Apache2 and Nginx: This prevents failures such as "bind() to 0.0.0.0:80 failed (98: Address already in use"
        # bind9: This avoid error when resolvconf is not configured yet
        my $file = iMSCP::File->new( filename => '/usr/sbin/policy-rc.d' );
        my $rs = $file->set( <<'EOF' );
#/bin/sh
initscript=$1
action=$2

if [ "$action" = "start" ] || [ "$action" = "restart" ]; then
    for i in apache2 bind9 nginx; do
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

    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    my $dist = lc( $lsbRelease->getId( 1 ) );
    my $codename = lc( $lsbRelease->getCodename( 1 ) );
    my $pkgFile = "$FindBin::Bin/docs/".ucfirst( $dist )."/packages-$codename.xml";

    eval "use XML::Simple; 1";
    die( $@ ) if $@;

    my $xml = XML::Simple->new( NoEscape => 1 );
    my $pkgData = eval { $xml->XMLin( $pkgFile, ForceArray => [ 'package', 'package_delayed', 'package_conflict' ] ) };
    if ($@) {
        error( $@ );
        return 1;
    }

    while(my ($section, $data) = each( %{$pkgData} )) {
        # Simple list of packages to install
        if ($data->{'package'} || $data->{'package_delayed'}) {
            push @{$self->{'packagesToInstall'}}, @{$data->{'package'}} if $data->{'package'};
            push @{$self->{'packagesToInstallDelayed'}}, @{$data->{'package_delayed'}} if $data->{'package_delayed'};
            next;
        }

        # Alternative list of package to install
        my $dAlt = delete $data->{'default'};
        my $sAlt = $main::questions{ uc( $section ).'_SERVER' } || $main::imscpConfig{ uc( $section ).'_SERVER' };
        my $forceDialog = $sAlt eq '' ? 1 : 0;
        $sAlt = $dAlt if $forceDialog;

        my @alts = keys %{$data};
        if (!grep($_ eq $sAlt, @alts)) {
            # Handle wrong or deprecated entry case
            $sAlt = $dAlt;
            $forceDialog = 1;
        }

        if (!$forceDialog && $data->{$sAlt}->{'allow_switch'}) {
            # Filter unallowed alternatives
            @alts = grep { my $__ = $_; grep($_ eq $__, @alts) } split( ',', $data->{$sAlt}->{'allow_switch'} ), $sAlt;
        }

        # Ask user for alternative list of packages to install if any
        if (@alts > 1 && ($forceDialog || grep($_ eq $main::reconfigure, ( $section, 'servers', 'all' )))) {
            iMSCP::Dialog->getInstance()->set( 'no-cancel', '' );
            (my $ret, $sAlt) = iMSCP::Dialog->getInstance()->radiolist( <<EOF, [ sort @alts ], $sAlt );

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
            push @{$self->{'packagesToUninstall'}}, @{$altData->{'package'}} if $altData->{'package'};
            push @{$self->{'packagesToUninstall'}}, @{$altData->{'package_delayed'}} if $altData->{'package_delayed'};
            push @{$self->{'packagesToUninstall'}}, @{$altData->{'package_conflict'}} if $altData->{'package_conflict'};
        }

        # APT preferences to add
        push @{$self->{'aptPreferences'}}, {
                'pinning_package'      => $data->{$sAlt}->{'pinning_package'},
                'pinning_pin'          => $data->{$sAlt}->{'pinning_pin'} || undef,
                'pinning_pin_priority' => $data->{$sAlt}->{'pinning_pin_priority'} || undef,
            } if $data->{$sAlt}->{'pinning_package'};

        # Conflicting APT repository to remove
        push @{$self->{'aptRepositoriesToRemove'}}, $data->{$sAlt}->{'repository_conflict'}
            if $data->{$sAlt}->{'repository_conflict'};

        # APT repository to add
        if ($data->{$sAlt}->{'repository'}) {
            push @{$self->{'aptRepositoriesToAdd'}}, {
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
        push @{$self->{'packagesToInstall'}}, @{$data->{$sAlt}->{'package'}} if $data->{$sAlt}->{'package'};
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
                my $rs = execute( "wget --spider $rc{'uri'}/dists/$rc{'distrib'}/$sec/", \my $stdout, \my $stderr );
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
                my $rs = execute( "wget --spider $rc{'uri'}/dists/$rc{'distrib'}/$sec/source/", \my $stdout,
                    \my $stderr );
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

        $fileContent .= <<EOF;

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
            $rs = execute( "@cmd", \my $stdout, \my $stderr );
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

        $fileContent .= <<EOF;

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

    my $command = 'apt-get';
    if (!iMSCP::Getopt->preseed && !iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'debconf-apt-progress' )) {
        iMSCP::Dialog->getInstance()->endGauge() if iMSCP::ProgramFinder::find( 'dialog' );
        $command = 'debconf-apt-progress --logstderr -- '.$command;
    }

    my ($stdout, $stderr);
    my $rs = execute(
        "$command -y update", (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt) ? \$stdout : undef, \$stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( 'Could not update package index from remote repository' ) if $rs && !$stderr;
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

    my $selectionsFileContent = <<EOF;
postfix postfix/main_mailer_type select Internet Site
postfix postfix/mailname string $mailname
postfix postfix/destinations string $destinations
proftpd-basic shared/proftpd/inetd_or_standalone select standalone
EOF

    if ($poServer eq 'courier') {
        $selectionsFileContent .= <<EOF;
courier-base courier-base/webadmin-configmode boolean false
courier-ssl courier-ssl/certnotice note
EOF
    } elsif ($poServer eq 'dovecot') {
        $selectionsFileContent .= <<EOF;
dovecot-core dovecot-core/create-ssl-cert boolean true
dovecot-core dovecot-core/ssl-cert-name string localhost
EOF
    }

    # Set default answer to yes for purge of sasldb2 database
    $selectionsFileContent .= <<EOF;
sasl2-bin cyrus-sasl2/purge-sasldb2 boolean true
EOF

    # We do not want ask user for /var/lib/mysql removal (we want avoid mistakes as much as possible)
    if ($sqlServer eq 'mariadb') {
        # There is a bug in mariadb-server-* packages (wrong version used for question prefix)
        $selectionsFileContent .= <<EOF;
$sqlServerQuestionOwner $sqlServerQuestionPrefix-5.1/postrm_remove_databases boolean false
$sqlServerQuestionOwner $sqlServerQuestionPrefix-5.1/really_downgrade boolean true
EOF
    } elsif (grep($_ eq 'mysql-community-server', @{$self->{'packagesToInstall'}})) {
        $selectionsFileContent .= <<EOF;
$sqlServerQuestionOwner $sqlServerQuestionOwner/remove-data-dir boolean false
EOF
    } elsif ($sqlServer ne 'remote_server') {
        $selectionsFileContent .= <<EOF;
$sqlServerQuestionOwner $sqlServerQuestionOwner/postrm_remove_databases boolean false
EOF
    }

    if ($sqlServer ne 'remote_server' && iMSCP::Getopt->preseed && $sqlServerQuestionOwner) {
        $selectionsFileContent .= <<EOF;
$sqlServerQuestionOwner $sqlServerQuestionPrefix/root_password password $main::questions{'DATABASE_PASSWORD'}
$sqlServerQuestionOwner $sqlServerQuestionPrefix/root_password_again password $main::questions{'DATABASE_PASSWORD'}
EOF
    }

    my $debconfSelectionsFile = File::Temp->new();
    print $debconfSelectionsFile $selectionsFileContent;
    $debconfSelectionsFile->flush();

    my $rs = execute( "debconf-set-selections $debconfSelectionsFile", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $rs && $stderr;
    error( 'Could not pre-fill Debconf database' ) if $rs && !$stderr;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
