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

    unless (-f "$self->{'pkgDir'}/composer.phar") {
        iMSCP::Dialog->getInstance()->infobox( <<EOF );

Installing/Updating composer.phar from http://getcomposer.org

Please wait, depending on your connection, this may take few seconds...
EOF

        my $rs = execute(
            "su - $main::imscpConfig{'IMSCP_USER'} -s /bin/sh -c "
                .escapeShell( "curl -s http://getcomposer.org/installer | $self->{'phpCmd'}" ),
            \my $stdout, \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        error( $stdout ) if !$stderr && $stdout && $rs;
        error( 'Could not install/update composer.phar from http://getcomposer.org' ) if $rs && !$stdout && !$stderr;
        return $rs if $rs;
    } else {
        iMSCP::Dialog->getInstance()->infobox( <<EOF );

Updating composer.phar from http://getcomposer.org

Please wait, depending on your connection, this may take few seconds...
EOF

        my $rs = execute(
            "su - $main::imscpConfig{'IMSCP_USER'} -s /bin/sh -c "
                .escapeShell( "$self->{'phpCmd'} composer.phar --no-ansi -n -d=$self->{'pkgDir'} self-update" ),
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        error( 'Could not update composer.phar' ) if $rs && !$stderr;
        return $rs if $rs;
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
        "su - $main::imscpConfig{'IMSCP_USER'} -s /bin/sh -c "
            .escapeShell( "$self->{'phpCmd'} composer.phar --no-ansi -n -d=$self->{'pkgDir'} update" ),
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
    "config": {
        "preferred-install": "dist",
        "process-timeout": 2000,
        "discard-changes": true
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

    my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'IMSCP_HOMEDIR'}/.cache" )->remove();
    $rs ||= iMSCP::Dir->new( dirname => "$main::imscpConfig{'IMSCP_HOMEDIR'}/.composer" )->remove();
    $rs ||= iMSCP::Dir->new( dirname => $self->{'pkgDir'} )->remove();
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
            "su - $main::imscpConfig{'IMSCP_USER'} -s /bin/sh -c "
                .escapeShell( "$self->{'phpCmd'} composer.phar --no-ansi -n -d=$self->{'pkgDir'} show --installed $package $version" ),
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
