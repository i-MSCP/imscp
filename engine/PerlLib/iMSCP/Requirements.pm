# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Requirements;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::ProgramFinder;
use Module::Load::Conditional 'check_install';
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 Requirement library

=head1 PUBLIC METHODS

=over 4

=item all( )

 Process check for all requirements

 Return undef on success, die on failure

=cut

sub all
{
    my ($self) = @_;

    $self->user();
    $self->_checkPrograms();
    $self->_checkPerlModules();
    $self->_checkPhpModules();
    undef;
}

=item user( )

 Check user under which the script is running

 Return undef on success, die on failure

=cut

sub user
{
    die( 'This script must be run as root user.' ) if $< != 0;
    undef;
}

=item checkVersion( $version, $minVersion [, $maxVersion ] )

 Checks for version

 Param string $version Version to match
 Param string $minVersion Min required version
 Param string $maxVersion Max required version
 Return undef on success, die on failure

=cut

sub checkVersion
{
    my (undef, $version, $minVersion, $maxVersion) = @_;

    if ( version->parse( $version ) < version->parse( $minVersion ) ) {
        die( sprintf( "version %s is too old. Minimum supported version is %s\n", $version, $minVersion ));
    }

    if ( $maxVersion && version->parse( $version ) > version->parse( $maxVersion ) ) {
        die(
            sprintf(
                "version %s is not supported. Supported versions are %s to %s\n", $version, $minVersion,
                $maxVersion
            )
        );
    }

    undef;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Requirements

=cut

sub _init
{
    my ($self) = @_;

    $self->{'programs'} = {
        PHP  => {
            version_command => 'php -nv 2> /dev/null',
            version_regexp  => qr/PHP\s+([\d.]+)/,
            min_version     => '5.6.0',
            max_version     => '7.1.999', # Arbitrary minor version is intentional. We only want reject PHP >= 7.2
            modules         => [
                'apc', 'apcu', 'ctype', 'curl', 'date', 'dom', 'fileinfo', 'filter', 'ftp', 'gd', 'gettext', 'gmp',
                'hash', 'iconv', 'imap', 'intl', 'json', 'libxml', 'mbstring', 'mcrypt', 'mysqlnd', 'mysqli', 'openssl',
                'pcntl', 'pcre', 'PDO', 'pdo_mysql', 'Phar', 'posix', 'pspell', 'Reflection', 'session', 'SimpleXML',
                'sockets', 'SPL', 'xml', 'xmlreader', 'xmlwriter', 'zip', 'zlib', 'Zend OPcache'
            ]
        },
        Perl => {
            version_command => 'perl -V:version 2> /dev/null',
            version_regexp  => qr/version='([\d.]+)'/,
            min_version     => '5.18.2',
            max_version     => '5.999', # Arbitrary minor version is intentional. We only want reject Perl >= 6
            module          => {
                'Bit::Vector'            => undef,
                'Crypt::Blowfish'        => undef,
                'Crypt::CBC'             => undef,
                DBI                      => undef,
                'DBD::mysql'             => undef,
                DateTime                 => undef,
                'Data::Validate::Domain' => undef,
                'Email::Valid'           => undef,
                'File::Basename'         => undef,
                'File::Path'             => undef,
                'MIME::Base64'           => undef,
                'MIME::Entity'           => undef,
                'Net::LibIDN'            => undef,
                'XML::Simple'            => undef
            }
        }
    };
    $self;
}

=item _checkPrograms( )

 Checks program requirements

 Return undef on success, die on failure

=cut

sub _checkPrograms
{
    my ($self) = @_;

    for ( keys %{$self->{'programs'}} ) {
        iMSCP::ProgramFinder::find( lc $_ ) or die(
            sprintf( "Couldn't find the `%s' command in search path", $_ )
        );

        next unless $self->{'programs'}->{$_}->{'version_command'};

        eval {
            $self->_programVersions(
                $self->{'programs'}->{$_}->{'version_command'},
                $self->{'programs'}->{$_}->{'version_regexp'},
                $self->{'programs'}->{$_}->{'min_version'},
                $self->{'programs'}->{$_}->{'max_version'}
            );
        };

        die( sprintf( "%s: %s\n", $_, $@ )) if $@;
    }

    undef;
}

=item _checkPerlModules( )

 Checks Perl modules requirements

 Return undef on success, die on failure

=cut

sub _checkPerlModules
{
    my ($self) = @_;

    my @missingModules = ();
    while ( my ($moduleName, $moduleVersion) = each %{$self->{'programs'}->{'Perl'}->{'modules'}} ) {
        push( @missingModules, $moduleName ) unless check_install( module => $moduleName, version => $moduleVersion );
    }

    return undef unless @missingModules;

    @missingModules < 2 or die(
        sprintf( "The following Perl modules are not installed: %s\n", join ', ', @missingModules )
    );

    die( sprintf( "The `%s' Perl module is not installed\n", pop @missingModules ));
}

=item _checkPhpModules( )

 Checks PHP modules requirements

 Return undef on success, die on failure

=cut

sub _checkPhpModules
{
    my ($self) = @_;

    open my $fh, '-|', 'php', '-d', 'date.timezone=UTC', '-m' or die(
        sprintf( "Couldn't pipe to php command: %s", $! )
    );
    chomp( my @modules = <$fh> );

    my @missingModules = ();
    for my $module( @{$self->{'programs'}->{'PHP'}->{'modules'}} ) {
        push @missingModules, $module unless grep(lc( $_ ) eq lc( $module ), @modules);
    }

    return undef unless @missingModules;

    @missingModules < 2 or die(
        sprintf( "The following PHP modules are not installed or not enabled: %s\n", join ', ', @missingModules )
    );

    die( sprintf( "The `%s' PHP module is not installed or not enabled.\n", pop @missingModules ));
}

=item _programVersions( $versionCommand, $versionRegexp, $minVersion [, $maxVersion ] )

 Check program version

 Param string $versionCommand Command to execute to find program version
 Param regexp $versionRegexp Regexp to find version in command version output string
 Param $minVersion Min required version
 Param $maxVersion Max required version
 Return undef on success, die on failure

=cut

sub _programVersions
{
    my ($self, $versionCommand, $versionRegexp, $minversion, $maxVersion) = @_;

    execute( $versionCommand, \ my $stdout );

    die( "Couldn't find version. No output\n" ) unless $stdout;

    if ( $versionRegexp ) {
        if ( $stdout !~ /$versionRegexp/m ) {
            die( sprintf( "Couldn't find version. Output was: %s\n", $stdout ));
        }

        $stdout = $1;
    }

    $self->checkVersion( $stdout, $minversion, $maxVersion );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
