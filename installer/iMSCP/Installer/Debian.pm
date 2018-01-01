=head1 NAME

 iMSCP::Installer::Debian - i-MSCP Debian like distribution installer implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Installer::Debian;

use strict;
use warnings;
use Array::Utils qw/ array_minus unique /;
use File::HomeDir;
use Fcntl qw/ :flock /;
use File::Temp;
use FindBin;
use iMSCP::Cwd;
use iMSCP::Debug qw/ debug error getMessageByType output /;
use iMSCP::Dialog;
use iMSCP::Dialog::InputValidation qw/ isOneOfStringsInList /;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use iMSCP::Stepper qw/ startDetail endDetail step /;
use iMSCP::TemplateParser qw/ processByRef /;
use iMSCP::Umask;
use XML::Simple;
use version;
use parent 'iMSCP::Installer::Abstract';

=head1 DESCRIPTION

 i-MSCP installer for Debian like distributions (Debian, Devuan, Ubuntu).

=head1 PUBLIC METHODS

=over 4

=item preBuild( \@steps )

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
            [ sub { $self->_processPackagesFile() }, 'Processing distribution packages file' ],
            [ sub { $self->_installAPTsourcesList(); }, 'Installing new APT sources.list(5) file' ],
            [ sub { $self->_addAPTrepositories() }, 'Adding APT repositories' ],
            [ sub { $self->_processAptPreferences() }, 'Processing APT preferences' ],
            [ sub { $self->_updatePackagesIndex() }, 'Updating packages index' ],
            [ sub { $self->_prefillDebconfDatabase() }, 'Pre-fill Debconf database' ]
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
    # - Prevent "bind() to 0.0.0.0:80 failed (98: Address already in use" failure (Apache2, Nginx)
    # - Prevent start failure when IPv6 stack is not enabled (Dovecot, Nginx)
    # - Prevent failure when resolvconf is not configured yet (bind9)
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
    $policyrcd->close();
    chmod( 0750, $policyrcd->filename ) or die( sprintf( "Couldn't change permissions on %s: %s", $policyrcd->filename, $! ));

    # See ZG-POLICY-RC.D(8)
    local $ENV{'POLICYRCD'} = $policyrcd->filename();

    my $rs = $self->uninstallPackages( $self->{'packagesToPreUninstall'} );
    $rs ||= $self->{'eventManager'}->trigger( 'beforeInstallPackages', $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'} );
    return $rs if $rs;

    {
        startDetail();
        local $CWD = "$FindBin::Bin/installer/scripts";

        for my $subject( keys %{$self->{'packagesPreInstallTasks'}} ) {
            my $subjectH = $subject =~ s/_/ /gr;
            my $nTasks = @{$self->{'packagesPreInstallTasks'}->{$subject}};
            my $cTask = 1;

            for ( @{$self->{'packagesPreInstallTasks'}->{$subject}} ) {
                $rs ||= step(
                    sub {
                        my $stdout;
                        $rs = execute( $_, ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout ), \ my $stderr );
                        error( sprintf( 'Error while executing pre-install tasks for %s: %s', $subjectH, $stderr || 'Unknown error' )) if $rs;
                        $rs;
                    },
                    sprintf( 'Executing pre-install tasks for %s ... Please be patient.', $subjectH ), $nTasks, $cTask
                );
                last if $rs;
                $cTask++;
            }

            last if $rs;
        }

        endDetail();
        return $rs if $rs;
    }

    # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
    execute( [ 'apt-mark', 'unhold', @{$self->{'packagesToInstall'}}, @{$self->{'packagesToInstallDelayed'}} ], \my $stdout, \my $stderr );
    debug( $stderr ) if $stderr;

    {
        local $ENV{'UCF_FORCE_CONFFNEW'} = 1;
        local $ENV{'UCF_FORCE_CONFFMISS'} = 1;

        my @cmd = (
            ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
            'apt-get', '--assume-yes', '--option', 'DPkg::Options::=--force-confnew', '--option',
            'DPkg::Options::=--force-confmiss', '--option', 'Dpkg::Options::=--force-overwrite',
            ( $main::forcereinstall ? '--reinstall' : () ), '--auto-remove', '--purge', '--no-install-recommends',
            ( version->parse( `apt-get --version 2>/dev/null` =~ /^apt\s+(\d\.\d)/ ) < version->parse( '1.1' )
                ? '--force-yes' : '--allow-downgrades' ),
            'install'
        );

        for ( $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'} ) {
            next unless @{$_};
            $rs = execute( [ @cmd, @{$_} ], ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef ), \$stderr );
            error( sprintf( "Couldn't install packages: %s", $stderr || 'Unknown error' )) if $rs;
            return $rs if $rs;
        }
    }

    {
        startDetail();
        local $CWD = "$FindBin::Bin/installer/scripts";

        for my $subject( keys %{$self->{'packagesPostInstallTasks'}} ) {
            my $subjectH = $subject =~ s/_/ /gr;
            my $nTasks = @{$self->{'packagesPostInstallTasks'}->{$subject}};
            my $cTask = 1;

            for ( @{$self->{'packagesPostInstallTasks'}->{$subject}} ) {
                $rs ||= step(
                    sub {
                        $rs = execute( $_, ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout ), \ $stderr );
                        error( sprintf( 'Error while executing post-install tasks for %s: %s', $subjectH, $stderr || 'Unknown error' )) if $rs;
                        $rs;
                    },
                    sprintf( 'Executing post-install tasks for %s ... Please be patient.', $subjectH ), $nTasks, $cTask
                );
                last if $rs;
                $cTask++;
            }

            last if $rs;
        }

        endDetail();
        return $rs if $rs;
    }

    while ( my ($package, $metadata) = each( %{$self->{'packagesToRebuild'}} ) ) {
        $rs = $self->_rebuildAndInstallPackage(
            $package, $metadata->{'pkg_src_name'}, $metadata->{'patches_directory'}, $metadata->{'discard_patches'},
            $metadata->{'patch_sys_type'}
        );
        return $rs if $rs;
    }

    $rs = $self->uninstallPackages( $self->{'packagesToUninstall'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterInstallPackages' );
}

=item uninstallPackages( \@packagesToUninstall )

 Uninstall Debian packages

 Param array \@packagesToUninstall List of packages to uninstall
 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
    my ($self, $packagesToUninstall) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeUninstallPackages', $packagesToUninstall );
    return $rs if $rs;

    if ( @{$packagesToUninstall} ) {
        # Filter packages that are no longer available
        $rs = execute( [ 'apt-cache', '--generate', 'pkgnames' ], \my $stdout, \my $stderr );
        error( $stderr || "Couldn't generate list of available packages" ) if $rs > 2;
        my %apkgs;
        @apkgs{split /\n/, $stdout} = undef;
        undef $stdout;
        @{$packagesToUninstall} = grep(exists $apkgs{$_}, @{$packagesToUninstall});
        undef( %apkgs );

        if ( @{$packagesToUninstall} ) {
            # Filter packages that must be kept or that were already uninstalled
            my @packagesToKeep = (
                @{$self->{'packagesToInstall'}}, @{$self->{'packagesToInstallDelayed'}}, keys %{$self->{'packagesToRebuild'}},
                @{$self->{'packagesToPreUninstall'}}
            );
            @{$packagesToUninstall} = array_minus( @{$packagesToUninstall}, @packagesToKeep );
            undef @packagesToKeep;

            if ( @{$packagesToUninstall} ) {
                # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
                execute( [ 'apt-mark', 'unhold', @{$packagesToUninstall} ], \$stdout, \$stderr );
                debug( $stderr ) if $stderr;

                iMSCP::Dialog->getInstance()->endGauge() unless iMSCP::Getopt->noprompt;

                $rs = execute(
                    [
                        ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
                        'apt-get', '--assume-yes', '--auto-remove', 'purge', @{$packagesToUninstall}
                    ],
                    ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef ),
                    \$stderr
                );
                error( sprintf( "Couldn't uninstall packages: %s", $stderr || 'Unknown error' )) if $rs;
                return $rs if $rs;

                # Purge packages that were indirectly removed
                $rs = execute(
                    "apt-get -y purge \$(dpkg -l | grep ^rc | awk '{print \$2}')",
                    ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \ $stdout ),
                    \$stderr
                );
                error( sprintf( "Couldn't purge packages that are in RC state: %s", $stderr || 'Unknown error' )) if $rs;
                return $rs if $rs;
            }
        }
    }

    $self->{'eventManager'}->trigger( 'afterUninstallPackages', $packagesToUninstall );
}

=back

=head1 PRIVATE METHODS/FUNCTIONS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Installer::Debian

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'aptRepositoriesToAdd'} = [];
    $self->{'aptPreferences'} = [];
    $self->{'packagesToInstall'} = [];
    $self->{'packagesToInstallDelayed'} = [];
    $self->{'packagesToPreUninstall'} = [];
    $self->{'packagesToUninstall'} = [];
    $self->{'packagesToRebuild'} = {};
    $self->{'packagesPreInstallTasks'} = {};
    $self->{'packagesPostInstallTasks'} = {};
    $self->{'need_pbuilder_update'} = 1;

    delete $ENV{'DEBCONF_FORCE_DIALOG'};

    $ENV{'DEBIAN_FRONTEND'} = iMSCP::Getopt->noprompt ? 'noninteractive' : 'dialog';
    $ENV{'DEBFULLNAME'} = 'i-MSCP Installer';
    $ENV{'DEBEMAIL'} = 'team@i-mscp.net';

    $self->_setupGetAddrinfoPrecedence();
    $self;
}

=item _setupGetAddrinfoPrecedence( )

 Setup getaddrinfo(3) precedence (IPv4) for the setup time being

 Return int 0 on success, other on failure

=cut

sub _setupGetAddrinfoPrecedence
{
    my $file = iMSCP::File->new( filename => '/etc/gai.conf' );
    my $fileContent = '';

    if ( -f '/etc/gai.conf' ) {
        $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read the %s file ", $file->{'filename'} ));
            return 1;
        }

        return 0 if $fileContent =~ m%^precedence\s+::ffff:0:0/96\s+100\n%m;
    }

    # Prefer IPv4
    $fileContent .= "precedence ::ffff:0:0/96  100\n";

    $file->set( $fileContent );
    $file->save();
}

=item _parsePackageNode( \%node|$node, \@target )

 Parse a package or package_delayed node

 param string|hashref $node Package node
 param arrayref \@target Target ($self->{'packagesToInstall'}|$self->{'packagesToInstallDelayed'})
 Return void

=cut

sub _parsePackageNode
{
    my ($self, $node, $target) = @_;

    unless ( ref $node eq 'HASH' ) {
        # Package without further treatment
        push @{$target}, $node;
        return;
    }

    # Package to rebuild
    if ( $node->{'rebuild_with_patches'} ) {
        $self->{'packagesToRebuild'}->{$node->{'content'}} = {
            pkg_src_name      => $node->{'pkg_src_name'} || $node->{'content'},
            patches_directory => $node->{'rebuild_with_patches'},
            discard_patches   => [ $node->{'discard_patches'} ? split ',', $node->{'discard_patches'} : () ],
            patch_sys_type    => $node->{'patch_sys_type'} || 'quilt'
        };
    } else {
        push @{$target}, $node->{'content'};
    }

    # Per package pre-install tasks
    if ( defined $node->{'pre_install_task'} ) {
        push @{ $self->{'packagesPreInstallTasks'}->{$node->{'content'}} }, $_ for @{$node->{'pre_install_task'}};
    }

    # Per package post-install tasks
    if ( defined $node->{'post_install_task'} ) {
        push @{$self->{'packagesPostInstallTasks'}->{$node->{'content'}}}, $_ for @{$node->{'post_install_task'}};
    }

    # Per package APT pinning
    if ( defined $node->{'pinning_package'} ) {
        push @{$self->{'aptPreferences'}},
            {
                pinning_package      => $node->{'pinning_package'},
                pinning_pin          => $node->{'pinning_pin'} || undef,
                pinning_pin_priority => $node->{'pinning_pin_priority'} || undef
            };
    }
}

=item _processPackagesFile( )

 Process distribution packages file

 Return int 0 on success, other on failure

=cut

sub _processPackagesFile
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBuildPackageList', \ my $pkgFile );
    return $rs if $rs;

    chomp( my $arch = `dpkg-architecture -qDEB_HOST_ARCH 2>/dev/null` || '' );
    if ( $? >> 8 != 0 || $arch eq '' ) {
        error( "Couldn't determine OS architecture" );
        return 1;
    }

    my $xml = XML::Simple->new( NoEscape => 1 );
    my $pkgData = eval {
        $xml->XMLin(
            $pkgFile || "$FindBin::Bin/installer/Packages/$main::imscpConfig{'DISTRO_ID'}-$main::imscpConfig{'DISTRO_CODENAME'}.xml",
            ForceArray     => [ 'package', 'package_delayed', 'package_conflict', 'pre_install_task', 'post_install_task' ],
            NormaliseSpace => 2
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my $dialog = iMSCP::Dialog->getInstance();

    # Make sure that all expected sections are defined in the packages file
    for ( qw/ frontend cron server httpd php po mta ftpd sqld perl other / ) {
        defined $pkgData->{$_} or die( sprintf( "Missing %s section in the distribution packages file.", $_ ));
    }

    while ( my ($section, $data) = each( %{$pkgData} ) ) {
        # List of packages to install
        if ( defined $data->{'package'} ) {
            for ( @{$data->{'package'}} ) {
                $self->_parsePackageNode( $_, $self->{'packagesToInstall'} );
            }
        }

        # List of packages to install (delayed)
        if ( defined $data->{'package_delayed'} ) {
            $self->_parsePackageNode( $_, $self->{'packagesToInstallDelayed'} ) for @{$data->{'package_delayed'}};
        }

        # List of conflicting packages that must be pre-removed
        if ( defined $data->{'package_conflict'} ) {
            for ( @{$data->{'package_conflict'}} ) {
                push @{$self->{'packagesToPreUninstall'}}, ref $_ eq 'HASH' ? $_->{'content'} : $_;
            }
        }

        # Per package section APT repository
        if ( defined $data->{'repository'} ) {
            push @{$self->{'aptRepositoriesToAdd'}},
                {
                    repository         => $data->{'repository'},
                    repository_key_uri => $data->{'repository_key_uri'} || undef,
                    repository_key_id  => $data->{'repository_key_id'} || undef,
                    repository_key_srv => $data->{'repository_key_srv'} || undef
                };
        }

        # Per package section APT pinning
        if ( defined $data->{'pinning_package'} ) {
            push @{$self->{'aptPreferences'}},
                {
                    pinning_package      => $data->{'pinning_package'},
                    pinning_pin          => $data->{'pinning_pin'} || undef,
                    pinning_pin_priority => $data->{'pinning_pin_priority'} || undef,
                };
        }

        # Per package section pre-install tasks
        if ( defined $data->{'pre_install_task'} ) {
            push @{$self->{'packagesPreInstallTasks'}->{$section}}, $_ for @{$data->{'pre_install_task'}};
        }

        # Per package section post-install tasks
        if ( defined $data->{'post_install_task'} ) {
            push @{$self->{'packagesPostInstallTasks'}->{$section}}, $_ for @{$data->{'post_install_task'}};
        }

        # Delete items that were already processed
        delete @{$data}{qw/ package package_delayed package_conflict pinning_package repository repository_key_uri repository_key_id
            repository_key_srv post_install_task post_install_task provide_alternatives /};

        # Jump in next section, unless the section defines alternatives
        next unless %{$data};

        # Dialog flag indicating whether or not user must be asked for alternative
        my $showDialog = 0;

        my $altDesc = delete $data->{'description'} || $section;
        my $sectionClass = delete $data->{'class'} or die(
            sprintf( "Undefined class for the `%s' section in the %s distribution package file", $section, $pkgFile )
        );

        # Retrieve current alternative
        my $sAlt = $main::questions{ $sectionClass } || $main::imscpConfig{ $sectionClass };

        # Build list of supported alternatives
        # Discard those alternatives for which architecture requirement is not met
        # Discard those alternatives for which init system requirement is not met
        my @supportedAlts = grep {
            ( !$data->{$_}->{'required_arch'} || $data->{$_}->{'required_arch'} eq $arch )
                && ( !$data->{$_}->{'required_init'} || $data->{$_}->{'required_init'} eq $main::imscpConfig{'SYSTEM_INIT'} )
        } keys %{$data};

        if ( $section eq 'sqld' ) {
            # The sqld section need a specific treatment
            eval { processSqldSection( $data, \$sAlt, \@supportedAlts, $dialog, \$showDialog ); };
            if ( $@ ) {
                error( $@ );
                return 1;
            }
        } else {
            if ( $sAlt ne '' && !grep($data->{$_}->{'class'} eq $sAlt, @supportedAlts) ) {
                # The selected alternative isn't longer available (or simply invalid). In such case, we reset it.
                # In preseed mode, we set the dialog flag to raise an error (preseed entry is not valid and user must be informed)
                $showDialog = 1 if iMSCP::Getopt->preseed;
                $sAlt = '';
            }

            if ( $sAlt eq '' ) {
                # There is no alternative selected
                if ( @supportedAlts > 1 ) {
                    # There are many alternatives available, we select the default as defined in the packages file and we set the dialog flag to make
                    # user able to change it, unless we are in preseed mode, in which case the default alternative will be enforced.
                    $showDialog = 1 unless iMSCP::Getopt->preseed;

                    for ( @supportedAlts ) {
                        next unless $data->{$_}->{'default'};
                        $sAlt = $_;
                        last;
                    }

                    # There are no default alternative defined in the packages file. We set it to the first entry.
                    $sAlt = $supportedAlts[0] if $sAlt eq '';
                } else {
                    # There is only one alternative available. We set it wihtout setting the dialog flag
                    $sAlt = $supportedAlts[0] if $sAlt eq '';
                }
            } else {
                # We make use of real alternative name for processing
                ( $sAlt ) = grep($data->{$_}->{'class'} eq $sAlt, @supportedAlts)
            }
        }

        # Set the dialog flag in any case if there are many alternatives available and if user asked for alternative reconfiguration
        $showDialog ||= @supportedAlts > 1 && isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ $section, 'servers', 'all' ] );

        if ( $showDialog ) {
            $dialog->set( 'no-cancel', '' );
            my %choices;
            @choices{ values @supportedAlts } = map { $data->{$_}->{'description'} // $_ } @supportedAlts;

            ( my $ret, $sAlt ) = $dialog->radiolist( <<"EOF", \%choices, $sAlt );
Please make your choice for the $altDesc:
\\Z \\Zn
EOF
            return $ret if $ret; # Handle ESC case
        }

        # Packages to install for the selected alternative
        if ( defined $data->{$sAlt}->{'package'} ) {
            for ( @{$data->{$sAlt}->{'package'}} ) {
                $self->_parsePackageNode( $_, $self->{'packagesToInstall'} );
            }
        }

        # Package to install (delayed) for the selected alternative
        if ( defined $data->{$sAlt}->{'package_delayed'} ) {
            for ( @{$data->{$sAlt}->{'package_delayed'}} ) {
                $self->_parsePackageNode( $_, $self->{'packagesToInstallDelayed'} );
            }
        }

        # Conflicting packages that must be pre-removed for the selected
        # alternative.
        if ( defined $data->{$sAlt}->{'package_conflict'} ) {
            for ( @{$data->{$sAlt}->{'package_conflict'}} ) {
                push @{$self->{'packagesToPreUninstall'}}, ref $_ eq 'HASH' ? $_->{'content'} : $_;
            }
        }

        # APT pinning for the selected alternative
        if ( defined $data->{$sAlt}->{'pinning_package'} ) {
            push @{$self->{'aptPreferences'}},
                {
                    pinning_package      => $data->{$sAlt}->{'pinning_package'},
                    pinning_pin          => $data->{$sAlt}->{'pinning_pin'} || undef,
                    pinning_pin_priority => $data->{$sAlt}->{'pinning_pin_priority'} || undef,
                }
        }

        # APT repository to add for the selected alternative
        if ( defined $data->{$sAlt}->{'repository'} ) {
            push @{$self->{'aptRepositoriesToAdd'}},
                {
                    repository         => $data->{$sAlt}->{'repository'},
                    repository_key_uri => $data->{$sAlt}->{'repository_key_uri'} || undef,
                    repository_key_id  => $data->{$sAlt}->{'repository_key_id'} || undef,
                    repository_key_srv => $data->{$sAlt}->{'repository_key_srv'} || undef
                };
        }

        # Perl alternative pre-install tasks
        if ( defined $data->{$sAlt}->{'pre_install_task'} ) {
            push @{$self->{'packagesPreInstallTasks'}->{$sAlt}}, $_ for @{$data->{$sAlt}->{'pre_install_task'}};
        }

        # Perl alternative post-install tasks
        if ( defined $data->{$sAlt}->{'post_install_task'} ) {
            push @{$self->{'packagesPostInstallTasks'}->{$sAlt}}, $_ for @{$data->{$sAlt}->{'post_install_task'}};
        }

        # Schedule removal of APT repositories and packages that belong to
        # unselected alternatives
        $dialog->endGauge();
        while ( my ($alt, $altData) = each( %{$data} ) ) {
            next if $alt eq $sAlt;

            # Packages to uninstall
            for ( qw / package package_delayed / ) {
                next unless defined $altData->{$_};

                for ( @{$altData->{$_}} ) {
                    my $package = ref $_ eq 'HASH' ? $_->{'content'} : $_;
                    #next if grep( $package eq $_, @{$self->{'packagesToPreUninstall'}} );
                    push @{$self->{'packagesToUninstall'}}, $package;
                }
            }
        }

        # Set server/package class name
        $main::imscpConfig{$sectionClass} = $data->{$sAlt}->{'class'} || 'iMSCP::Servers::Noserver';
        # Set alternative name for processing (volatile data)
        $main::questions{'_' . $section} = $sAlt;
    }

    @{$self->{'packagesToPreUninstall'}} = sort( unique( @{$self->{'packagesToPreUninstall'}} ) );
    @{$self->{'packagesToUninstall'}} = sort( unique( @{$self->{'packagesToUninstall'}} ) );
    @{$self->{'packagesToInstall'}} = sort( unique( @{$self->{'packagesToInstall'}} ) );
    @{$self->{'packagesToInstallDelayed'}} = sort( unique( @{$self->{'packagesToInstallDelayed'}} ) );

    #$dialog->endGauge;
    #use Data::Dumper;
    #print Dumper( \%main::questions );
    #print Dumper( $self );
    #exit;

    $dialog->set( 'no-cancel', undef );
    0;
}

=item _installAPTsourcesList( )

 Installs i-MSCP provided SOURCES.LIST(5) configuration file

 Return int 0 on success, other on failure

=cut

sub _installAPTsourcesList
{
    my ($self) = @_;

    eval {
        $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apt', 'sources.list', \ my $fileContent, {} ) == 0 or die(
            getMessageByType ( 'error', { amount => 1, remove => 1 } )
        );

        unless ( defined $fileContent ) {
            my $file = "$FindBin::Bin/configs/$main::imscpConfig{'DISTRO_ID'}/apt/sources.list";
            $fileContent = iMSCP::File->new( filename => $file )->get() or die( getMessageByType ( 'error', { amount => 1, remove => 1 } ));
        }

        processByRef( { codename => $main::imscpConfig{'DISTRO_CODENAME'} }, \$fileContent );

        local $UMASK = 022;
        my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
        $file->set( $fileContent );
        $file->save() == 0 or die( getMessageByType ( 'error', { amount => 1, remove => 1 } ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _addAPTrepositories( )

 Add required APT repositories

 Return int 0 on success, other on failure

=cut

sub _addAPTrepositories
{
    my ($self) = @_;

    return 0 unless @{$self->{'aptRepositoriesToAdd'}};

    my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );
    my $rs = $file->copyFile( '/etc/apt/sources.list.bkp' );
    return $rs if $rs;

    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( "Couldn't read /etc/apt/sources.list file" );
        return 1;
    }

    # Add APT repositories
    for my $repository( @{$self->{'aptRepositoriesToAdd'}} ) {
        next if $fileContent =~ /^deb\s+$repository->{'repository'}/m;

        $fileContent .= <<"EOF";

deb $repository->{'repository'}
deb-src $repository->{'repository'}
EOF
        # Hide "apt-key output should not be parsed (stdout is not a terminal)" warning that
        # is raised in newest apt-key versions. Our usage of apt-key is not dangerous (not parsing)
        local $ENV{'APT_KEY_DONT_WARN_ON_DANGEROUS_USAGE'} = 1;

        if ( $repository->{'repository_key_srv'} && $repository->{'repository_key_id'} ) {
            # Add the repository key from the given key server
            $rs = execute(
                [ 'apt-key', 'adv', '--recv-keys', '--keyserver', $repository->{'repository_key_srv'}, $repository->{'repository_key_id'} ],
                \ my $stdout,
                \ my $stderr
            );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            # Workaround https://bugs.launchpad.net/ubuntu/+source/gnupg2/+bug/1633754
            $rs = execute( [ '/usr/bin/pkill', '-TERM', 'dirmngr' ], \ $stdout, \ $stderr );
            debug( $stdout ) if $stdout;
            warning( $stderr ) if $rs && $stderr ne '';
        } elsif ( $repository->{'repository_key_uri'} ) {
            # Add the repository key by fetching it first from the given URI
            my $keyFile = File::Temp->new( UNLINK => 1 );
            $rs = execute(
                [ '/usr/bin/wget', '--prefer-family=IPv4', '--timeout=30', '-O', $keyFile->filename, $repository->{'repository_key_uri'} ],
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
    $file->save();
}

=item _processAptPreferences( )

 Process apt preferences

 Return 0 on success, other on failure

=cut

sub _processAptPreferences
{
    my ($self) = @_;

    my $fileContent = '';

    for my $pref ( @{$self->{'aptPreferences'}} ) {
        unless ( $pref->{'pinning_pin'} || $pref->{'pinning_pin_priority'} ) {
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

    if ( $fileContent ) {
        $fileContent =~ s/^\n//;
        $file->set( $fileContent );

        my $rs = $file->save();
        $rs ||= $file->mode( 0644 );
        return $rs;
    }

    ( -f '/etc/apt/preferences.d/imscp' ) ? $file->delFile() : 0;
}

=item _updatePackagesIndex( )

 Update Debian packages index

 Return int 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
    iMSCP::Dialog->getInstance()->endGauge() if !iMSCP::Getopt->noprompt && iMSCP::ProgramFinder::find( 'dialog' );

    local $ENV{'LANG'} = 'C';

    my $stdout;
    my $rs = execute(
        [ ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ), 'apt-get', 'update' ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef ), \ my $stderr
    );
    error( sprintf( "Couldn't update package index from remote repository: %s", $stderr || 'Unknown error' )) if $rs;
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

    if ( $main::questions{'_mta'} eq 'postfix' ) {
        chomp( my $mailname = `hostname --fqdn 2>/dev/null` || 'localdomain' );
        my $hostname = ( $mailname ne 'localdomain' ) ? $mailname : 'localhost';
        chomp( my $domain = `hostname --domain 2>/dev/null` || 'localdomain' );

        # Mimic behavior from the postfix package postfix.config maintainer script
        my $destinations = ( $mailname eq $hostname )
            ? join ', ', ( $mailname, 'localhost.' . $domain, ', localhost' )
            : join ', ', ( $mailname, $hostname, 'localhost.' . $domain . ', localhost' );

        # Pre-fill debconf database for Postfix
        $fileContent .= <<"EOF";
postfix postfix/main_mailer_type select Internet Site
postfix postfix/mailname string $mailname
postfix postfix/destinations string $destinations
EOF
    }

    if ( $main::questions{'_ftpd'} eq 'proftpd' ) {
        # Pre-fill debconf database for Proftpd
        $fileContent .= "proftpd-basic shared/proftpd/inetd_or_standalone select standalone\n";
    }

    if ( $main::questions{'_po'} eq 'courier' ) {
        # Pre-fill debconf database for Courier
        $fileContent .= <<'EOF';
courier-base courier-base/courier-user note
courier-base courier-base/webadmin-configmode boolean false
courier-ssl courier-ssl/certnotice note
EOF
    }

    # Pre-fill debconf database for Dovecot
    if ( $main::questions{'_po'} eq 'dovecot' ) {
        # Pre-fill debconf database for Dovecot
        $fileContent .= <<'EOF';
dovecot-core dovecot-core/ssl-cert-name string localhost
dovecot-core dovecot-core/create-ssl-cert boolean true
EOF
    }

    # Pre-fill question for sasl2-bin package if required
    if ( `echo GET cyrus-sasl2/purge-sasldb2 | debconf-communicate sasl2-bin 2>/dev/null` =~ /^0/ ) {
        $fileContent .= "sasl2-bin cyrus-sasl2/purge-sasldb2 boolean true\n";
    }

    if ( my ($sqldVendor, $sqldVersion) = $main::questions{'_sqld'} =~ /^(mysql|mariadb|percona)_(\d+\.\d+)/ ) {
        my ($package);
        if ( $sqldVendor eq 'mysql' ) {
            $package = grep($_ eq 'mysql-community-server', @{$self->{'packagesToInstall'}}) ? 'mysql-community-server' : "mysql-server-$sqldVersion";
        } else {
            $package = ( $sqldVendor eq 'mariadb' ? 'mariadb-server-' : 'percona-server-server-' ) . $sqldVersion;
        }

        # Only show critical questions if the SQL server has been already installed
        #$ENV{'DEBIAN_PRIORITY'} = 'critical' if -d '/var/lib/mysql';

        READ_DEBCONF_DB:

        my $isManualTplLoading = 0;
        open my $fh, '-|', "debconf-get-selections 2>/dev/null | grep $package";
        unless ( $fh ) {
            error( sprintf( "Couldn't pipe to debconf database: %s", $! || 'Unknown error' ));
            return 1;
        }

        if ( eof $fh ) {
            if ( $isManualTplLoading ) {
                error( "Couldn't pre-fill debconf database for the SQL server. Debconf template not found." );
                return 1;
            }

            # The debconf template is not available (the package has not been installed yet or something went wrong with the debconf database)
            # In such case, we download the package into a temporary directory, we extract the debconf template manually and we load it into the
            # debconf database. Once done, we process as usually. This is lot of work but we have not choice as question names for different SQL
            # servers are not consistent.
            close( $fh );

            my $tmpDir = File::Temp->newdir();

            if ( my $uid = ( getpwnam( '_apt' ) )[2] ) {
                # Prevent Fix `W: Download is performed unsandboxed as root as file...' warning with newest APT versions
                unless ( chown $uid, -1, $tmpDir ) {
                    error( sprintf( "Couldn't change ownership for the %s directory: %s", $tmpDir, $! ));
                    return 1;
                }
            }

            local $ENV{'LANG'} = 'C';
            local $CWD = $tmpDir;

            # Download the package into a temporary directory
            my $rs = execute( [ 'apt-get', '--quiet=1', 'download', $package ], \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;

            # Extract the debconf template into the temporary directory
            $rs ||= execute( [ 'apt-extracttemplates', '-t', $tmpDir, <$tmpDir/*.deb> ], \$stdout, \$stderr );
            $rs || debug( $stdout ) if $stdout;

            # Load the template into the debconf database
            $rs ||= execute( [ 'debconf-loadtemplate', $package, <$tmpDir/$package.template.*> ], \$stdout, \$stderr );
            $rs || debug( $stdout ) if $stdout;

            if ( $rs ) {
                error( $stderr || 'Unknown errror' );
                return $rs;
            }

            $isManualTplLoading++;
            goto READ_DEBCONF_DB;
        }

        # Pre-fill debconf database for the SQL server (mariadb, mysql or percona)
        while ( <$fh> ) {
            if ( my ($qOwner, $qNamePrefix, $qName) = m%(.*?)\s+(.*?)/([^\s]+)% ) {
                if ( grep($qName eq $_, 'remove-data-dir', 'postrm_remove_databases') ) {
                    # We do not want ask user for databases removal (we want avoid mistakes as much as possible)
                    $fileContent .= "$qOwner $qNamePrefix/$qName boolean false\n";
                } elsif ( grep($qName eq $_, 'root_password', 'root-pass', 'root_password_again', 're-root-pass')
                    && iMSCP::Getopt->preseed
                    && $main::questions{'SQL_ROOT_PASSWORD'} ne ''
                ) {
                    # Preset root SQL password using value from preseed file if required
                    $fileContent .= "$qOwner $qNamePrefix/$qName password $main::questions{'SQL_ROOT_PASSWORD'}\n";

                    # Register an event listener to empty the password field in the debconf database after package installation
                    #$self->{'eventManager'}->registerOne(
                    #    'afterInstallPackages',
                    #    sub {
                    #        my $rs = execute( "echo SET $qNamePrefix/$qName | debconf-communicate $qOwner", \ my $stdout, \ my $stderr );
                    #        debug( $stdout ) if $stdout;
                    #        error( $stderr || 'Unknown error' ) if $rs;
                    #        return $rs if $rs;
                    #        0;
                    #    }
                    #);
                }
            }
        }

        close( $fh );
    }

    return 0 if $fileContent eq '';

    my $debconfSelectionsFile = File::Temp->new();
    print $debconfSelectionsFile $fileContent;
    $debconfSelectionsFile->close();

    my $rs = execute( [ 'debconf-set-selections', $debconfSelectionsFile ], \ my $stdout, \ my $stderr );
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

    local $ENV{'LANG'} = 'C';

    $patchesDir = "$FindBin::Bin/configs/$main::imscpConfig{'DISTRO_ID'}/$patchesDir";
    unless ( -d $patchesDir ) {
        error( sprintf( '%s is not a valid patches directory', $patchesDir ));
        return 1;
    }

    my $srcDownloadDir = File::Temp->newdir( CLEANUP => 1 );

    # Fix `W: Download is performed unsandboxed as root as file...' warning with newest APT versions
    if ( ( undef, undef, my $uid ) = getpwnam( '_apt' ) ) {
        unless ( chown $uid, -1, $srcDownloadDir ) {
            error( sprintf( "Couldn't change ownership for the %s directory: %s", $srcDownloadDir, $! ));
            return 1;
        }
    }

    # chdir() into download directory
    local $CWD = $srcDownloadDir;

    # Avoid pbuilder warning due to missing $HOME/.pbuilderrc file
    my $rs = iMSCP::File->new( filename => File::HomeDir->my_home . '/.pbuilderrc' )->save();
    return $rs if $rs;

    startDetail();

    $rs = step(
        sub {
            if ( $self->{'need_pbuilder_update'} ) {
                $self->{'need_pbuilder_update'} = 0;

                my $msgHeader = "Creating/Updating pbuilder environment\n\n - ";
                my $msgFooter = "\n\nPlease be patient. This may take few minutes ...";

                my $stderr = '';
                my $cmd = [
                    'pbuilder',
                    ( -f '/var/cache/pbuilder/base.tgz' ? ( '--update', '--autocleanaptcache' ) : '--create' ),
                    '--distribution', $main::imscpConfig{'DISTRO_CODENAME'},
                    '--configfile', "$FindBin::Bin/configs/$main::imscpConfig{'DISTRO_ID'}/pbuilder/pbuilderrc",
                    '--override-config'
                ];
                $rs = executeNoWait(
                    $cmd,
                    ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                        ? sub {}
                        : sub {
                            return unless ( shift ) =~ /^i:\s*(.*)/i;
                            step( undef, $msgHeader . ucfirst( $1 ) . $msgFooter, 5, 1 );
                        }
                    ),
                    sub { $stderr .= shift; }
                );
                error( sprintf( "Couldn't create/update pbuilder environment: %s", $stderr || 'Unknown error' )) if $rs;
                return $rs if $rs;
            }
            0;
        },
        'Creating/Updating pbuilder environment', 5, 1
    );
    $rs ||= step(
        sub {
            my $msgHeader = sprintf( "Downloading %s %s source package\n\n - ", $pkgSrc, $main::imscpConfig{'DISTRO_ID'} );
            my $msgFooter = "\nDepending on your system this may take few seconds ...";

            my $stderr = '';
            $rs = executeNoWait(
                [ 'apt-get', '-y', 'source', $pkgSrc ],
                ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                    ? sub {} : sub { step( undef, $msgHeader . ( ( shift ) =~ s/^\s*//r ) . $msgFooter, 5, 2 ); }
                ),
                sub { $stderr .= shift }
            );
            error( sprintf( "Couldn't download %s Debian source package: %s", $pkgSrc, $stderr || 'Unknown error' )) if $rs;
            $rs;
        },
        sprintf( 'Downloading %s %s source package', $pkgSrc, $main::imscpConfig{'DISTRO_ID'} ), 5, 2
    );

    {
        # chdir() into package source directory
        local $CWD = ( <$pkgSrc-*> )[0];

        $rs ||= step(
            sub {
                my $serieFile = iMSCP::File->new( filename => "debian/patches/" . ( $patchFormat eq 'quilt' ? 'series' : '00list' ));
                my $serieFileContent = $serieFile->get();
                unless ( defined $serieFileContent ) {
                    error( sprintf( "Couldn't read %s", $serieFile->{'filename'} ));
                    return 1;
                }

                for my $patch( sort { $a cmp $b } iMSCP::Dir->new( dirname => $patchesDir )->getFiles() ) {
                    next if grep($_ eq $patch, @{$patchesToDiscard});
                    $serieFileContent .= "$patch\n";
                    $rs = iMSCP::File->new( filename => "$patchesDir/$patch" )->copyFile( "debian/patches/$patch", { preserve => 'no' } );
                    return $rs if $rs;
                }

                $rs = $serieFile->set( $serieFileContent );
                $rs ||= $serieFile->save();
                return $rs if $rs;

                my $stderr;
                $rs = execute(
                    [ 'dch', '--local', '~i-mscp-', 'Patched by i-MSCP installer for compatibility.' ],
                    ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : \my $stdout ),
                    \$stderr
                );
                debug( $stdout ) if $stdout;
                error( sprintf( "Couldn't add `imscp' local suffix: %s", $stderr || 'Unknown error' )) if $rs;
                return $rs if $rs;
            },
            sprintf( 'Patching %s %s source package ...', $pkgSrc, $main::imscpConfig{'DISTRO_ID'} ), 5, 3
        );
        $rs ||= step(
            sub {
                my $msgHeader = sprintf( "Building new %s %s package\n\n - ", $pkg, $main::imscpConfig{'DISTRO_ID'} );
                my $msgFooter = "\n\nPlease be patient. This may take few seconds ...";
                my $stderr;

                $rs = executeNoWait(
                    [
                        'pdebuild',
                        '--use-pdebuild-internal',
                        '--configfile', "$FindBin::Bin/configs/$main::imscpConfig{'DISTRO_ID'}/pbuilder/pbuilderrc"
                    ],
                    ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                        ? sub {}
                        : sub {
                            return unless ( shift ) =~ /^i:\s*(.*)/i;
                            step( undef, $msgHeader . ucfirst( $1 ) . $msgFooter, 5, 4 );
                        }
                    ),
                    sub { $stderr .= shift }
                );
                error( sprintf( "Couldn't build local %s %s package: %s", $pkg, $main::imscpConfig{'DISTRO_ID'}, $stderr || 'Unknown error' )) if $rs;
                $rs;
            },
            sprintf( 'Building local %s %s package', $pkg, $main::imscpConfig{'DISTRO_ID'} ), 5, 4
        );
    }

    $rs ||= step(
        sub {
            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( [ 'apt-mark', 'unhold', $pkg ], \my $stdout, \my $stderr );
            debug( $stderr ) if $stderr;

            my $msgHeader = sprintf( "Installing local %s %s package\n\n", $pkg, $main::imscpConfig{'DISTRO_ID'} );
            $stderr = '';

            $rs = executeNoWait(
                "dpkg --force-confnew -i /var/cache/pbuilder/result/${pkg}_*.deb",
                ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                    ? sub {} : sub { step( undef, $msgHeader . ( shift ), 5, 5 ) }
                ),
                sub { $stderr .= shift }
            );
            error( sprintf( "Couldn't install local %s %s package: %s", $pkg, $main::imscpConfig{'DISTRO_ID'}, $stderr || 'Unknown error' )) if $rs;
            return $rs if $rs;

            # Ignore exit code due to https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1258958 bug
            execute( [ 'apt-mark', 'hold', $pkg ], \$stdout, \$stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;
            0;
        },
        sprintf( 'Installing local %s %s package', $pkg, $main::imscpConfig{'DISTRO_ID'} ), 5, 5
    );
    endDetail();

    $rs;
}

=item _getSqldInfo

 Get SQL server info (vendor and version)

 Return list List containing SQL server vendor (lowercase) and version, die on failure

=cut

sub _getSqldInfo
{
    if ( my $mysqld = iMSCP::ProgramFinder::find( 'mysqld' ) ) {
        my ($stdout, $stderr);
        execute( [ $mysqld, '--version' ], \$stdout, \$stderr ) == 0 or die(
            sprintf( "Couldn't guess SQL server info: %s", $stderr || 'Unknown error' )
        );

        if ( my ($version, $vendor) = $stdout =~ /^.*?(\d+.\d+).*?-(\w+)-/ ) {
            return ( $vendor, $version );
        }
    }

    ( 'none', 'none' );
}

=item processSqldSection( \%data, \$sAlt, \@supportedAlts, \%dialog, \$showDialog )

 Process sqld section from the distribution packages file

 Param hashref \%data Hash containing sqld section data
 Param scalarref \$sAlt Selected sqld alternative
 Param arrayref \@supportedAlts Array containing list of supported alternatives
 Param iMSCP::Dialog \%dialog Dialog instance
 Param scalarref \$showDialog Boolean indicating whether or not dialog must be shown for sqld section
 return void

=cut

sub processSqldSection
{
    my ($data, $sAlt, $supportedAlts, $dialog, $showDialog) = @_;

    my ( $sqldVendor, $sqldVersion ) = _getSqldInfo();

    $dialog->endGauge;

    if ( $sqldVendor ne 'none' ) {
        # There is an SQL server installed.

        # Discard any SQL server vendor other than current installed, excepted remote
        # Discard any SQL server version older than current installed, excepted remote
        $sqldVersion = version->parse( $sqldVersion );
        my @sqlSupportedAlts = grep {
            $_ eq 'remote_server' || ( index( $_, lc $sqldVendor ) == 0 && version->parse( $_ =~ s/^.*_//r ) >= $sqldVersion )
        } @{$supportedAlts};

        # Ask for confirmation if current SQL server vendor is no longer supported (safety measure)
        unless ( @sqlSupportedAlts ) {
            $dialog->endGauge();
            $dialog->set( 'no-cancel', undef );
            return 50 if $dialog->yesno( <<"EOF", 'abort_by_default' );
\\Zb\\Z1WARNING \\Z0CURRENT SQL SERVER VENDOR IS NOT SUPPORTED \\Z1WARNING\\Zn

The installer detected that your current SQL server ($sqldVendor $sqldVersion) is not supported and that there is no alternative version for that vendor.
If you continue, you'll be asked for another SQL server vendor but bear in mind that the upgrade could fail. You should really considere backuping all your database before continue.
                
Are you sure you want to continue?
EOF
            # No alternative matches with the installed SQL server. User has been warned and want continue upgrade. We show it dialog with all
            # available alternatives, selecting the default as defined in the packages file, or the first alternative if there is not default.
            for ( @{$supportedAlts} ) {
                next unless $data->{$_}->{'default'};
                ${$sAlt} = $_;
                last;
            }
        } else {
            ${$sAlt} = lc( $sqldVendor ) . '_' . $sqldVersion;
            @{$supportedAlts} = @sqlSupportedAlts;

            # Resets alternative if the selected alternative is no longer available
            if ( !grep($_ eq ${$sAlt}, @{$supportedAlts}) ) {
                ${$showDialog} = 1;
                for ( @{$supportedAlts} ) {
                    next unless $data->{$_}->{'default'};
                    ${$sAlt} = $_;
                    last;
                }
            }
        }

        ${$sAlt} = $supportedAlts->[0] if ${$sAlt} eq '';
    } else {
        # There is no SQL server installed.

        if ( ${$sAlt} ne '' && !grep($data->{$_}->{'class'} eq ${$sAlt}, @{$supportedAlts}) ) {
            # The selected alternative isn't longer available (or simply invalid). In such case, we reset it.
            # In preseed mode, we set the dialog flag to raise an error (preseed entry is wrong and user must be informed)
            ${$showDialog} = 1 if iMSCP::Getopt->preseed; # We want raise an error in preseed mode
            ${$sAlt} = '';
        }

        if ( ${$sAlt} eq '' ) {
            # There is no alternative selected
            if ( @{$supportedAlts} > 2 ) {
                # If there are many available, we select the default as defined in the packages file and we force dialog to make user able to
                # change it, unless we are in preseed or noninteractive mode, in which case the default alternative will be enforced.
                ${$showDialog} = 1 unless iMSCP::Getopt->preseed;

                for ( @{$supportedAlts} ) {
                    next unless $data->{$_}->{'default'};
                    ${$sAlt} = $_;
                    last;
                }

                ${$sAlt} = $supportedAlts->[0] if ${$sAlt} eq '';
            } else {
                # There is only one alternative available. We select it wihtout showing dialog
                ${$sAlt} = $supportedAlts->[0] if ${$sAlt} eq '';
            }
        } else {
            # We make use of alternative name for processing
            ( ${$sAlt} ) = grep($data->{$_}->{'class'} eq ${$sAlt}, @{$supportedAlts})
        }
    }
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
