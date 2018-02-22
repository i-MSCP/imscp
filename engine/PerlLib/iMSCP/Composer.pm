=head1 NAME

 iMSCP::Composer - i-MSCP Composer packages installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::Composer;

use strict;
use warnings;
use iMSCP::Debug / error /;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::Execute qw/ executeNoWait /;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Stepper;
use iMSCP::TemplateParser;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Composer packages installer for i-MSCP.

=head1 PUBLIC METHODS

=over 4

=item registerPackage( $package [, $packageVersion = 'dev-master' ] )

 Register the given composer package for installation

 Param string $package Package name
 Param string $packageVersion OPTIONAL Package version
 Return int 0

=cut

sub registerPackage
{
    my ($self, $package, $packageVersion) = @_;

    $packageVersion ||= 'dev-master';
    push @{$self->{'packages'}}, "        \"$package\": \"$packageVersion\"";
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Composer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'composer_version'} = '1.5.2'; # Make sure to work with a well-known composer version
    $self->{'packages'} = [];
    $self->{'packages_dir'} = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages";
    $self->{'su_cmd'} = [ '/bin/su', '-l', $main::imscpConfig{'IMSCP_USER'}, '-s', '/bin/sh', '-c' ];
    $self->{'php_cmd'} = "/usr/bin/php -d date.timezone=$main::imscpConfig{'TIMEZONE'} -d allow_url_fopen=1 "
        . "-d suhosin.executor.include.whitelist=phar";

    iMSCP::EventManager->getInstance()->register(
        'afterSetupPreInstallPackages',
        sub {
            if ( iMSCP::Getopt->cleanPackageCache ) {
                my $rs = $self->_cleanPackageCache();
                return $rs if $rs;
            }

            eval {
                iMSCP::Dir->new( dirname => $self->{'packages_dir'} )->make(
                    {
                        user  => $main::imscpConfig{'IMSCP_USER'},
                        group => $main::imscpConfig{'IMSCP_GROUP'},
                        mode  => 0755
                    }
                );
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            startDetail;

            my $rs = step(
                sub { $self->_installComposer(); },
                "Installing composer.phar ($self->{'composer_version'}) from https://getcomposer.org", 3, 1
            );
            $rs ||= step(
                sub { iMSCP::Getopt->skipPackageUpdate ? $self->_checkRequirements() : 0; },
                'Checking composer package requirements', 3, 2
            );

            if ( iMSCP::Getopt->skipPackageUpdate ) {
                endDetail;
                return $rs;
            };

            $rs ||= step(
                sub { $self->_installPackages(); },
                'Installing/Updating composer packages from Github', 3, 3
            );

            endDetail;
            $rs;
        }
    );

    $self;
}

=item _installComposer( )

 Install composer

 Return 0 on success, other on failure

=cut

sub _installComposer
{
    my ($self) = @_;

    if ( -x "$main::imscpConfig{'IMSCP_HOMEDIR'}/composer.phar"
        && version->parse( `@{$self->{'su_cmd'}} '$self->{'php_cmd'} composer.phar --no-ansi --version 2>/dev/null'` =~ /version\s+([\d.]+)/ )
        == version->parse( $self->{'composer_version'} )
    ) {
        debug( "composer.phar version is already $self->{'composer_version'}. Skipping installation..." );
        return 0;
    }

    my $msgHeader = "Installing composer.phar from https://getcomposer.org\n\n";
    my $msgFooter = "\nDepending on your connection, this may take few seconds...";
    my ($rs, $stderr) = ( 0, undef );

    if ( -d "$main::imscpConfig{'IMSCP_HOMEDIR'}/.composer" ) {
        eval {
            # Remove old versions if any
            iMSCP::Dir->new( dirname => "$main::imscpConfig{'IMSCP_HOMEDIR'}/.composer" )->clear(
                undef, qr/\Q.phar\E$/
            );
        };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    }

    $rs ||= executeNoWait(
        [
            @{$self->{'su_cmd'}}, "/usr/bin/curl -s https://getcomposer.org/installer | $self->{'php_cmd'} --"
                . " --version=$self->{'composer_version'}"
        ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
            ? sub {}
            : sub { step( undef, $msgHeader . ( shift ) . $msgFooter, 3, 1 ); }
        ),
        sub { $stderr .= shift; }
    );

    error( sprintf( "Couldn't install composer.phar: %s", $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=item _checkRequirements( )

 Check package version requirements

 Return int 0 if all requirements are met, 1 otherwise

=cut

sub _checkRequirements
{
    my ($self) = @_;

    return 0 unless -d $self->{'packages_dir'};

    my $msgHeader = "Checking composer package requirements\n\n";
    my $stderr;

    for( @{$self->{'packages'}} ) {
        my ($package, $version) = $_ =~ /"(.*)":\s*"(.*)"/;
        my $msgShown;
        my $msg = $msgHeader . "Checking package $package ($version)\n\n";
        my $rs = executeNoWait(
            [
                @{$self->{'su_cmd'}}, "$self->{'php_cmd'} composer.phar show --no-ansi --no-interaction "
                    . "--working-dir=$self->{'packages_dir'} $package $version"
            ],
            ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
                ? sub {}
                : sub {
                    return if $msgShown;
                    step( undef, $msg, 3, 2 );
                    $msgShown = 1;
                }
            ),
            sub { $stderr .= shift; }
        );
        if ( $rs ) {
            error( sprintf( "Package %s (%s) not found. Please retry without the '-a' option.", $package, $version ));
            return 1;
        }
    }

    0;
}

=item _installPackages( )

 Install or update packages

 Return 0 on success, other on failure

=cut

sub _installPackages
{
    my ($self) = @_;

    my $rs = $self->_buildComposerFile();
    return $rs if $rs;

    my $msgHeader = "Installing/Updating composer packages from Github\n\n";
    my $msgFooter = "\nDepending on your connection, this may take few seconds...";

    # Note: Any progress/status info goes to stderr (See https://github.com/composer/composer/issues/3795)
    $rs = executeNoWait(
        [
            @{$self->{'su_cmd'}}, "$self->{'php_cmd'} composer.phar update --no-ansi --no-interaction "
                . "--working-dir=$self->{'packages_dir'}"
        ],
        sub {},
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose
            ? sub {}
            : sub { step( undef, $msgHeader . ( shift ) . $msgFooter, 3, 3 ); }
        )
    );

    error( "Couldn't install/update i-MSCP packages from GitHub" ) if $rs;
    $rs;
}

=item _buildComposerFile( )

 Build composer.json file

 Return 0 on success, other on failure

=cut

sub _buildComposerFile
{
    my ($self) = @_;

    my $tpl = <<'TPL';
{
    "name": "imscp/packages",
    "description": "i-MSCP composer packages",
    "licence": "GPL-2.0+",
    "require": {
{PACKAGES}
    },
    "config": {
        "preferred-install": "dist",
        "process-timeout": 2000,
        "discard-changes": true
    },
    "minimum-stability": "dev"
}
TPL

    my $file = iMSCP::File->new( filename => "$self->{'packages_dir'}/composer.json" );
    $file->set( process( { PACKAGES => join ",\n", @{$self->{'packages'}} }, $tpl ));
    $file->save();
}

=item _cleanPackageCache( )

 Clear composer package cache

 Return 0 on success, other on failure

=cut

sub _cleanPackageCache
{
    my ($self) = @_;

    eval {
        for( "$main::imscpConfig{'IMSCP_HOMEDIR'}/.cache",
            "$main::imscpConfig{'IMSCP_HOMEDIR'}/.composer",
            $self->{'packages_dir'}
        ) {
            iMSCP::Dir->new( dirname => $_ )->remove();
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
