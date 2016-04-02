=head1 NAME

 iMSCP::Composer - i-MSCP Composer packages installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use Cwd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Composer packages installer for iMSCP.

=head1 PUBLIC METHODS

=over 4

=item registerPackage($package [, $packageVersion = 'dev-master' ])

 Register the given composer package for installation

 Param string $package Package name
 Param string $packageVersion OPTIONAL Package version
 Return int 0

=cut

sub registerPackage
{
    my ($self, $package, $packageVersion) = @_;

    $packageVersion ||= 'dev-master';
    push @{$self->{'toInstall'}}, "        \"$package\": \"$packageVersion\"";
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Composer

=cut

sub _init
{
    my $self = shift;

    $self->{'toInstall'} = [ ];
    $self->{'pkgDir'} = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages";

    # Increase composer process timeout for slow connections
    $ENV{'COMPOSER_PROCESS_TIMEOUT'} = 2000;

    # We do not want any user interaction
    $ENV{'COMPOSER_NO_INTERACTION'} = '1';

    # We discard any change made in vendor
    $ENV{'COMPOSER_DISCARD_CHANGES'} = 'true';

    $self->{'phpCmd'} = 'php -d date.timezone=UTC -d allow_url_fopen=1 -d suhosin.executor.include.whitelist=phar';

    iMSCP::EventManager->getInstance()->register(
        'afterSetupPreInstallPackages', sub {
            iMSCP::Dialog->getInstance()->endGauge();

            my $rs = $self->_cleanPackageCache() if iMSCP::Getopt->cleanPackageCache;
            return $rs if $rs;

            $rs = iMSCP::Dir->new( dirname => $self->{'pkgDir'} )->make(
                {
                    user  => $main::imscpConfig{'IMSCP_USER'},
                    group => $main::imscpConfig{'IMSCP_GROUP'},
                    mode  => 0755
                }
            );
            $rs ||= setRights( $self->{'pkgDir'}, { user => 'imscp', group => 'imscp' } );
            return $rs if $rs;

            unless (iMSCP::Getopt->skipPackageUpdate && -x "$self->{'pkgDir'}/composer.phar") {
                $rs = $self->_getComposer();
                return $rs if $rs;
            }

            unless (iMSCP::Getopt->skipPackageUpdate && $self->_checkRequirements()) {
                $rs = $self->_installPackages();
            }

            $rs;
        }
    );

    $self;
}

=item _getComposer()

 Get composer.phar

 Return 0 on success, other on failure

=cut

sub _getComposer
{
    my $self = shift;

    my $curDir = getcwd();

    unless (-f "$self->{'pkgDir'}/composer.phar") {
        unless (chdir( $self->{'pkgDir'} )) {
            error( sprintf( 'Could not change current directory to %s: %s', $self->{'pkgDir'}, $! ) );
            return 1;
        }

        iMSCP::Dialog->getInstance()->infobox( <<EOF );

Installing/Updating composer.phar from http://getcomposer.org

Please wait, depending on your connection, this may take few seconds...
EOF

        my $rs = execute(
            "runuser -u $main::imscpConfig{'IMSCP_USER'} -- "
                . "curl -s http://getcomposer.org/installer | $self->{'phpCmd'}",
            \my $stdout, \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        error( $stdout ) if !$stderr && $stdout && $rs;
        error( 'Could not install/update composer.phar from http://getcomposer.org' ) if $rs && !$stdout && !$stderr;
        return $rs if $rs;

        unless (chdir( $curDir )) {
            error( sprinf( 'Could not change current directory to %s: %s', $curDir, $! ) );
            return 1;
        }
    } else {
        unless (chdir( $self->{'pkgDir'} )) {
            error( sprintf( 'Could not change current directory to %s: %s', $self->{'pkgDir'}, $! ) );
            return 1;
        }

        iMSCP::Dialog->getInstance()->infobox( <<EOF );

Updating composer.phar from http://getcomposer.org

Please wait, depending on your connection, this may take few seconds...
EOF

        my $rs = execute(
            "runuser -u $main::imscpConfig{'IMSCP_USER'} -- "
                ."$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} self-update",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        error( 'Could not update composer.phar' ) if $rs && !$stderr;
        return $rs if $rs;

        unless (chdir( $curDir )) {
            error( sprintf( 'Could not change current directory to %s: %s', $curDir, $! ) );
            return 1;
        }
    }

    0;
}

=item _installPackages()

 Install or update packages

 Return 0 on success, other on failure

=cut

sub _installPackages
{
    my $self = shift;

    my $rs = $self->_buildComposerFile();
    return $rs if $rs;

    my $stderr;
    my $dialog = iMSCP::Dialog->getInstance();
    my $msgHeader = <<EOF;

Installing/Updating i-MSCP packages from Github

EOF
    my $msgFooter = <<EOF;

Please wait, depending on your connection, this may take few seconds...
EOF

    # The update option is used here but composer will automatically fallback to install mode when needed
    # Note: Any progress/status info goes to stderr (See https://github.com/composer/composer/issues/3795)
    $rs = executeNoWait(
        "runuser -u $main::imscpConfig{'IMSCP_USER'} -- "
            ."$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} update --prefer-dist",
        sub {
            my $str = shift;
            $$str = '';
        },
        sub {
            my $str = shift;

            if ($$str =~ /^$/m) {
                $$str = '';
            } else {
                my ($strBkp, $buff) = ($$str, '');
                $buff .= $1 while ($$str =~ s/^(.*\n)//);

                if ($buff ne '') {
                    debug( $buff );
                    $dialog->infobox( "$msgHeader$buff$msgFooter" );
                    $$str = $strBkp unless $strBkp =~ /^Updating dependencies.*\n/m;
                }
            }
        }
    );

    error( sprintf( 'Could not install/update i-MSCP packages from GitHub: %s', $stderr ) ) if $stderr && $rs;
    error( 'Could not install/update i-MSCP packages from GitHub: Unknown error' ) if $rs && !$stderr;
    $rs;
}

=item _buildComposerFile()

 Build composer.json file

 Return 0 on success, other on failure

=cut

sub _buildComposerFile
{
    my $self = shift;

    my $tpl = <<TPL;
{
    "name": "imscp/packages",
    "description": "i-MSCP composer packages",
    "licence": "GPL-2.0+",
    "require": {
{PACKAGES}
    },
    "minimum-stability": "dev"
}
TPL

    my $file = iMSCP::File->new( filename => "$self->{'pkgDir'}/composer.json" );
    my $rs ||= $file->set( process( { PACKAGES => join ",\n", @{$self->{'toInstall'}} }, $tpl ) );
    $rs ||= $file->save();
}

=item _cleanPackageCache()

 Clear composer package cache

 Return 0 on success, other on failure

=cut

sub _cleanPackageCache
{
    my $self = shift;

    iMSCP::Dir->new( dirname => $self->{'pkgDir'} )->remove();
}

=item _checkRequirements()

 Check package version requirements

 Return bool TRUE if all requirements are meets, FALSE otherwise

=cut

sub _checkRequirements
{
    my $self = shift;

    return 0 unless -d $self->{'pkgDir'};

    for(@{$self->{'toInstall'}}) {
        my ($package, $version) = $_ =~ /"(.*)":\s*"(.*)"/;
        my $rs = execute(
            "runuser -u $main::imscpConfig{'IMSCP_USER'} -- "
                ."$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} show --installed ".
                escapeShell( $package ).' '.escapeShell( $version ),
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;

        if ($rs) {
            debug( sprintf( "Version %s of package %s not found in composer package cache.", $package, $version ) );
            return 0;
        }
    }

    1;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
