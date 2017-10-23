=head1 NAME

 iMSCP::Composer - Perl frontEnd to PHP dependency manager (Composer)

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
use File::HomeDir;
use File::Spec;
use File::Temp;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::ProgramFinder;
use JSON qw/ from_json to_json /;
use version;
use fields qw/ _php_cmd _stdout _stderr _attrs /;

=head1 DESCRIPTION

 Perl frontEnd to PHP dependency manager (Composer).

=head1 PUBLIC METHODS

=over 4

=item new

 Constructor

 Optional arguments:
    user:           Unix user under which composer should run (default: $main::imscpConfig{'ROOT_USER'})
    group:          Unix group under which composer should run (default: <user> group)
    home_dir:       Unix user homedir (default: <user> homedir)
    working_dir:    Composer working directory (default: <home_dir>)
    composer_path:  Composer path (default: <home_dir>/composer.phar)
    composer_json:  Composer json file (default: self-generated)
 Return iMSCP::Composer, die on failure

=cut

sub new
{
    my iMSCP::Composer $self = shift;

    unless ( ref $self ) {
        $self = fields::new( $self );
        %{$self->{'_attrs'}} = ref $_[0] eq 'HASH' ? %{$_[0]} : @_ if @_;

        $self->{'_attrs'}->{'user'} ||= getpwuid( $< );
        $self->{'_attrs'}->{'group'} ||= getgrgid( ( getpwnam( $self->{'_attrs'}->{'user'} ) )[3] // die(
            sprintf( "Couldn't find `%s` user", $self->{'_attrs'}->{'user'} )
        )) // die( sprintf( "Couldn't find `%s` user group", $self->{'_attrs'}->{'user'} ));
        $self->{'_attrs'}->{'home_dir'} = File::Spec->canonpath(
            $self->{'_attrs'}->{'home_dir'} || File::HomeDir->users_home( $self->{'_attrs'}->{'user'} )
        );
        $self->{'_attrs'}->{'working_dir'} = File::Spec->canonpath(
            $self->{'_attrs'}->{'working_dir'} || $self->{'_attrs'}->{'home_dir'}
        );
        $self->{'_attrs'}->{'composer_path'} ||= File::Spec->canonpath(
            "$self->{'_attrs'}->{'home_dir'}/composer.phar"
        );
        $self->{'_attrs'}->{'composer_json'} = from_json(
            $self->{'_attrs'}->{'composer_json'} || <<"EOT", { utf8 => 1 } );
{
    "config": {
        "cache-files-ttl":0,
        "cafile":"$main::imscpConfig{'DISTRO_CA_BUNDLE'}",
        "capath":"$main::imscpConfig{'DISTRO_CA_PATH'}",
        "discard-changes":true,
        "htaccess-protect":false,
        "preferred-install":"dist",
        "process-timeout":2000
    },
    "minimum-stability":"dev",
    "prefer-stable":true
}
EOT
        $self->{'_php_cmd'} = [
            '/usr/bin/php', '-d', "date.timezone=$main::imscpConfig{'TIMEZONE'}", '-d', 'allow_url_fopen=1',
            '-d suhosin.executor.include.whitelist=phar'
        ];
        # Set default STD routines
        $self->setStdRoutines();
    }

    $self;
}

=item requirePackage( $package [, $packageVersion = 'dev-master' [, $dev = false ] ] )

 Require the given composer package for installation

 Param string $package Package name
 Param string $packageVersion OPTIONAL Package version
 Param bool $dev OPTIONAL Flag indicating if $package is a development package
 Return void

=cut

sub requirePackage
{
    my ($self, $package, $packageVersion, $dev) = @_;

    if ( $dev ) {
        $self->{'_attrs'}->{'composer_json'}->{'require_dev'}->{$package} = $packageVersion ||= 'dev-master';
        return;
    }

    $self->{'_attrs'}->{'composer_json'}->{'require'}->{$package} = $packageVersion ||= 'dev-master';
}

=item installComposer( [ $installDir = <home_dir> [, $filename = 'composer.phar' [, $version = latest ] ] ] )

 Install composer in the given installation directory as the given filename

 Param string $installDir OPTIONAL Installation directory
 Param string $filename OPTIONAL Composer installation filename
 Param string $version OPTIONAL Composer version to install
 Return void, die on failure

=cut

sub installComposer
{
    my ($self, $installDir, $filename, $version) = @_;

    $installDir ||= $self->{'_attrs'}->{'home_dir'};
    $filename ||= 'composer.phar';

    if ( $version
        && -x "$installDir/$filename"
        && version->parse( $self->getComposerVersion( "$installDir/$filename" )) == version->parse( $version )
    ) {
        $self->{'_stdout'}( "Composer version is already $version. Skipping installation...\n" );
        return;
    }

    if ( -d "$self->{'_attrs'}->{'home_dir'}/.composer" ) {
        iMSCP::Dir->new( dirname => "$self->{'_attrs'}->{'home_dir'}/.composer" )->clear( undef, qr/\Q.phar\E$/ );
    }

    # Make sure to create temporary file with expected ownership
    my $installer;
    if ( $self->{'_attrs'}->{'user'} ne $main::imscpConfig{'ROOT_USER'} ) {
        local $) = getgrnam( $self->{'_attrs'}->{'group'} ) or die( "Couldn't setgid: %s", $! );
        local $> = getpwnam( $self->{'_attrs'}->{'user'} ) or die( "Couldn't setuid: %s:", $! );
        $installer = File::Temp->new();
    } else {
        $installer = File::Temp->new();
    }

    my $rs = execute(
        $self->_getSuCmd(
            ( iMSCP::ProgramFinder::find( 'curl' ) or die( 'cURL is either not installed or not executable' ) ),
            '--fail', '--connect-timeout', 10, '-s', '-S', '-o', $installer, 'https://getcomposer.org/installer'
        ),
        undef,
        \ my $stderr,
    );
    $rs == 0 or die( sprintf( "Couldn't download composer: %s", $stderr || 'Unknown error' ));
    $rs = executeNoWait(
        $self->_getSuCmd(
            @{$self->{'_php_cmd'}}, $installer, '--', '--no-ansi', ( $version ? "--version=$version" : () ),
            "--install-dir=$installDir", "--filename=$filename"
        ),
        $self->{'_stdout'},
        $self->{'_stderr'}
    );
    $rs == 0 or die( "Couldn't install composer" );
}

=item installPackages( [ $requireDev = false ] )

 Install or update packages

 Param bool $requireDev OPTIONAL Flag indicating whether or not packages listed
                        in require-dev must be installed
 Return void, die on failure

=cut

sub installPackages
{
    my ($self, $requireDev) = @_;

    if ( $self->{'_attrs'}->{'home_dir'} ne $self->{'_attrs'}->{'working_dir'} ) {
        iMSCP::Dir->new( dirname => $self->{'_attrs'}->{'working_dir'} )->make(
            {
                user           => $self->{'_attrs'}->{'user'},
                group          => $self->{'_attrs'}->{'group'},
                mode           => 0750,
                fixpermissions => 0 # Set permissions only on creation
            }
        );
    }

    my $file = iMSCP::File->new( filename => "$self->{'_attrs'}->{'working_dir'}/composer.json" );
    $file->set( $self->getComposerJson());
    my $rs = $file->save();
    $rs ||= $file->owner( $self->{'_attrs'}->{'user'}, $self->{'_attrs'}->{'group'} );
    $rs ||= $file->mode( 0644 );
    $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
    $rs = executeNoWait(
        $self->_getSuCmd(
            @{$self->{'_php_cmd'}}, $self->{'_attrs'}->{'composer_path'}, 'update', '--no-progress', '--no-ansi',
            '--no-interaction', ( $requireDev ? () : '--no-dev' ), '--no-suggest', '--classmap-authoritative',
            "--working-dir=$self->{'_attrs'}->{'working_dir'}"
        ),
        $self->{'_stdout'},
        $self->{'_stderr'}
    );
    $rs == 0 or die( "Couldn't install/update composer packages" );
}

=item clearPackageCache( )

 Clear composer's internal package cache, including vendor directory

 Return void, die on failure

=cut

sub clearPackageCache
{
    my ($self) = @_;

    my $rs = executeNoWait(
        $self->_getSuCmd( @{$self->{'_php_cmd'}}, $self->{'_attrs'}->{'composer_path'}, '--no-ansi', 'clearcache' ),
        $self->{'_stdout'},
        $self->{'_stderr'}
    );
    $rs == 0 or die( "Couldn't clear composer's internal package cache" );

    # See https://getcomposer.org/doc/06-config.md#vendor-dir
    my $vendorDir = "$self->{'_attrs'}->{'working_dir'}/vendor";
    my $composerJson = $self->{'_attrs'}->{'composer_json'};
    if ( $composerJson->{'config'}->{'vendor-dir'} ) {
        ( $vendorDir = $composerJson->{'config'}->{'vendor-dir'} ) =~ s%(?:\$HOME|~)%$self->{'_attrs'}->{'home_dir'}%g;
    }
    iMSCP::Dir->new( dirname => $vendorDir )->remove();
}

=item checkPackageRequirements( )

 Check package requirements

 Return void, die if package requirements are not met

=cut

sub checkPackageRequirements
{
    my ($self) = @_;

    -d $self->{'_attrs'}->{'working_dir'} or die( "Unmet requirements (all packages)" );

    while ( my ( $package, $version ) = each( %{$self->{'_attrs'}->{'composer_json'}->{'require'}} ) ) {
        $self->{'_stdout'}( sprintf( "Checking requirements for the %s (%s) composer package\n", $package, $version ));
        my $rs = execute(
            $self->_getSuCmd(
                @{$self->{'_php_cmd'}}, $self->{'_attrs'}->{'composer_path'}, 'show', '--no-ansi',
                '--no-interaction',
                "--working-dir=$self->{'_attrs'}->{'working_dir'}", $package, $version
            ),
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        $rs == 0 or die( sprintf( "Unmet requirements (%s %s): %s", $package, $version, $stderr ));
    }
}

=item getComposerJson( $scalar = false )

 Return composer.json file as string

 Param bool $scalar OPTIONAL Whether composer.json must be returned as scalar (default: false)
 Return string|scalar, die on failure

=cut

sub getComposerJson
{
    my (undef, $scalar) = @_;

    $scalar ? $_[0]->{'_attrs'}->{'composer_json'} : to_json(
        $_[0]->{'_attrs'}->{'composer_json'},
        {
            utf8      => 1,
            indent    => 1,
            canonical => 1
        }
    );
}

=item setStdRoutines( [ $subStdout = sub { print STDOUT @_ } [, $subStderr = sub { print STDERR @_ }  ] ] )

 Set routines for STDOUT/STDERR processing

 Param CODE $subStdout OPTIONAL Routine for processing of command STDOUT line by line
 Param CODE $subStderr OPTIONAL Routine for processing of command STDERR line by line
 Return void, die on invalid arguments

=cut

sub setStdRoutines
{
    my ($self, $subStdout, $subStderr) = @_;

    $subStdout ||= sub { print STDOUT @_ };
    ref $subStdout eq 'CODE' or die( 'Expects a routine as first parameter for STDOUT processing' );
    $self->{'_stdout'} = $subStdout;

    $subStderr ||= sub { print STDERR @_ };
    ref $subStderr eq 'CODE' or die( 'Expects a routine as second parameter for STDERR processing' );
    $self->{'_stderr'} = $subStderr;
}

=item getComposerVersion( $composerPath )

 Return composer version

 Param string $composerPath Composer path
 Return string version, die on failure

=cut

sub getComposerVersion
{
    my ($self, $composerPath) = @_;

    my $rs = execute(
        $self->_getSuCmd( @{$self->{'_php_cmd'}}, $composerPath, '--no-ansi', '--version' ), \my $stdout, \my $stderr
    );
    debug( $stdout ) if $stdout;
    $rs == 0 or die( sprintf( "Couldn't get composer (%s) version: %s", $composerPath, $stderr ));
    ( $stdout =~ /version\s+([\d.]+)/ );
    $1 or die( sprintf( "Couldn't parse composer (%s) version from version string: %s", $composerPath, $stdout // '' ));
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getSuCmd( @_ )

 Return SU command

 Param list @_ Command
 Return arrayref command

=cut

sub _getSuCmd
{
    my $self = shift;

    if ( $self->{'_attrs'}->{'user'} eq $main::imscpConfig{'ROOT_USER'} ) {
        $ENV{'COMPOSER_ALLOW_SUPERUSER'} = 1;
        $ENV{'COMPOSER_HOME'} = "$self->{'_attrs'}->{'home_dir'}/.composer";
        return \@_;
    }

    delete $ENV{'COMPOSER_ALLOW_SUPERUSER'};
    [
        '/bin/su', '-l', $self->{'_attrs'}->{'user'}, '-s', '/bin/sh', '-c',
        "COMPOSER_HOME=$self->{'_attrs'}->{'home_dir'}/.composer @_"
    ];
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
