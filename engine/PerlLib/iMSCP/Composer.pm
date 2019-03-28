=head1 NAME

 iMSCP::Composer - Perl frontEnd to PHP dependency manager (Composer)

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

package iMSCP::Composer;

use strict;
use warnings;
use Carp 'croak';
use Digest::SHA ();
use File::Basename 'fileparse';
use File::Spec;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::ProgramFinder;
use JSON qw/ from_json to_json /;
use LWP::UserAgent ();
use version;
use fields qw/
    user group composer_home composer_working_dir composer_phar composer_json
    _euid _egid _php_cmd _stdout _stderr
/;

=head1 DESCRIPTION

 Perl frontEnd to PHP dependency manager (Composer).
 
 See https://getcomposer.org/

=head1 PUBLIC METHODS

=over 4

=item new(
   [  user                 => EUID
   [, group                => <user_group>
   [, composer_home        => <user_home>/.composer
   [, composer_working_dir => <user_home> 
   [, composer_phar        => <user_home>/bin/composer.phar
   [, composer_json        => none ] ] ] ] ] ]
)

 Constructor

 Parameters:
  - user                 : OPTIONAL Name of unix user under which composer.phar should be run
  - group                : OPTIONAL Name of unix group under which composer.phar should be run 
  - composer_home        : OPTIONAL Path to composer home directory. If not an absolute path, it will be relative to <user_home>
  - composer_working_dir : OPTIONAL Path to composer working directory. If not an absolute path, it will be relative to <user_home>
  - composer_phar        : OPTIONAL Path to composer.phar. If not an absolute path, it will be relative to <user_home>/bin
  - composer_json        : OPTIONAL Path to composer.json. If not an absolute path, it will be relative to <composer_working_dir>
 Return iMSCP::Composer, die on failure

=cut

sub new
{
    my iMSCP::Composer $self = shift;

    return $self if ref $self;

    $self = fields::new( $self );
    %{ $self } = ref $_[0] eq 'HASH' ? %{ $_[0] } : @_ if @_;

    my ( @pwent ) = ( length $self->{'user'} ? getpwnam( $self->{'user'} ) : getpwuid( $> ) ) or croak(
        ( length $self->{'user'}
            ? sprintf( "Couldn't find %s user in password database", $self->{'user'} )
            : sprintf( "Couldn't find user with ID %d in password database", $> )
        )
    );

    $self->{'user'} //= $pwent[0];
    $self->{'_euid'} = $pwent[2];

    if ( length $self->{'group'} ) {
        $self->{'_egid'} = getgrnam( $self->{'group'} ) or croak( sprintf(
            "Couldn't find %s group in group database", $self->{'group'}
        ));
    } else {
        $self->{'group'} = getgrgid( $pwent[3] ) or croak( sprintf(
            "Couldn't find group with ID %d in group database", $pwent[3]
        ));
        $self->{'_egid'} = $pwent[3];
    }

    my $homeDir = $pwent[7] || '';

    $self->{'composer_home'} = File::Spec->rel2abs(
        length $self->{'composer_home'} ? $self->{'composer_home'} : "$homeDir/.composer",
        $homeDir
    );
    $self->{'composer_working_dir'} = File::Spec->rel2abs(
        length $self->{'composer_working_dir'} ? $self->{'composer_working_dir'} : $homeDir,
        $homeDir
    );
    $self->{'composer_phar'} = File::Spec->rel2abs(
        length $self->{'composer_phar'} ? $self->{'composer_phar'} : "$homeDir/bin/composer.phar",
        $homeDir . '/bin'
    );
    $self->{'_php_cmd'} = [
        ( iMSCP::ProgramFinder::find( 'php' ) or croak( "Couldn't find php executable in \$PATH" ) ),
        '-d',
        'allow_url_fopen=1'
    ];
    $self->loadComposerJson();
    $self->setStdRoutines();
    $self;
}

=item installComposer( [, $version = latest ] )

 Install the given composer version

 Param string $version OPTIONAL Composer version to install
 Return iMSCP::Composer, die on failure

=cut

sub installComposer
{
    my ( $self, $version ) = @_;

    $self->_sandbox( sub {
        if ( length $version && -x $self->{'composer_phar'} && version->parse( $self->getComposerVersion()) == version->parse( $version ) ) {
            $self->{'_stdout'}( sprintf( "PHP dependency manager version is already %s. Installation skipped.", $version ));
            return;
        }

        iMSCP::Dir->new( dirname => $self->{'composer_home'} )->clear( undef, qr/\.(?:phar|pub)$/ ) if -d $self->{'composer_home'};

        my $ua = LWP::UserAgent->new( agent => "iMSCP/$::imscpConfig{'Version'}", timeout => 30, env_proxy => TRUE );
        my $installer = File::Temp->new();
        $installer->close();

        # Download composer installer
        my $response;
        ( $response = $ua->get( 'https://getcomposer.org/installer', ':content_file' => $installer->filename ) )->is_success or die( sprintf(
            "Couldn't download the PHP dependency manager installer: %s", $response->status_line
        ));
        # Download composer installer signature for verification
        ( $response = $ua->get( 'https://composer.github.io/installer.sig' ) )->is_success or die( sprintf(
            "Couldn't download signature for the PHP dependency manager installer: %s", $response->status_line
        ));
        # Verify composer installer signature
        chomp( my $sig = $response->decoded_content );
        $sig eq Digest::SHA->new( 'sha384' )->addfile( $installer->filename )->hexdigest() or die(
            "Couldn't verify signature for the PHP dependency manager installer."
        );
        # Install PHP dependency manager
        my ( $filename, $installDir ) = fileparse( $self->{'composer_phar'} );
        iMSCP::Dir->new( dirname => $installDir )->make() unless -d $installDir;
        executeNoWait(
            $self->_getSuCmd(
                @{ $self->{'_php_cmd'} }, $installer->filename, '--',
                '--no-ansi', "--install-dir=$installDir", "--filename=$filename", ( length $version ? "--version=$version" : () )
            ),
            $self->{'_stdout'},
            $self->{'_stderr'}
        ) == 0 or die( "Couldn't install the PHP dependency manager." );
    } );

    $self;
}

=item require( $package [, $version = 'dev-master' [, $dev = false ] ] )

 Add a package to the requirements

 Param string $package Package name
 Param string $version OPTIONAL Package version
 Param bool $dev OPTIONAL Flag indicating if $package is a dev requirement package
 Return iMSCP::Composer, die on failure

=cut

sub require
{
    my ( $self, $package, $version, $dev ) = @_;

    if ( $dev ) {
        $self->remove( $package, TRUE ); # Make sure to not add the same package twice
        $self->{'composer_json'}->{'require_dev'}->{$package} = $version ||= 'dev-master';
        return;
    }

    $self->remove( $package ); # Make sure to not add the same package twice
    $self->{'composer_json'}->{'require'}->{$package} = $version ||= 'dev-master';
    $self;
}

=item remove( $package [, $dev = false ] ] )

 Remove a package from the requirements

 Param string $package Package name
 Param bool $dev OPTIONAL Flag indicating if $package is a dev requirement package
 Return iMSCP::Composer, die on failure

=cut

sub remove
{
    my ( $self, $package, $dev ) = @_;

    if ( $dev ) {
        delete $self->{'composer_json'}->{'require_dev'}->{$package};
        return;
    }

    delete $self->{'composer_json'}->{'require'}->{$package};
    $self;
}

=item install( [ $nodev = false, [ $noautoloader = false] ])

 Install dependencies

 Param bool $nodev OPTIONAL Flag indicating whether require-dev packages must be discarded
 Param bool $noautoloader OPTIONAL Flag indicating whether or not autoloader generation must be skipped
 Return iMSCP::Composer, die on failure

=cut

sub install
{
    my ( $self, $nodev, $noautoloader ) = @_;

    $self->_sandbox( sub {
        $self->_removeAutoloader() if $noautoloader;
        $self->dumpComposerJson();

        executeNoWait(
            $self->_getSuCmd(
                @{ $self->{'_php_cmd'} },
                $self->{'composer_phar'}, 'install',
                "--working-dir=$self->{'composer_working_dir'}",
                '--no-progress', '--no-ansi', '--no-interaction', '--no-suggest',
                ( $nodev ? '--no-dev' : () ), ( $noautoloader ? '--no-autoloader' : () ),
            ),
            $self->{'_stdout'},
            $self->{'_stderr'}
        ) == 0 or die( "Couldn't install composer packages" );
    } );

    $self;
}

=item update( [ $nodev = false, [ $noautoloader = false] ])

 Update packages

 Param bool $nodev OPTIONAL Flag indicating whether require-dev packages must be discarded
 Param bool $noautoloader OPTIONAL Flag indicating whether or not autoloader generation must be skipped
 Return iMSCP::Composer, die on failure

=cut

sub update
{
    my ( $self, $nodev, $noautoloader ) = @_;

    $self->_sandbox( sub {
        $self->_removeAutoloader() if $noautoloader;
        $self->dumpComposerJson();

        executeNoWait(
            $self->_getSuCmd(
                @{ $self->{'_php_cmd'} },
                $self->{'composer_phar'}, 'update',
                "--working-dir=$self->{'composer_working_dir'}",
                '--no-progress', '--no-ansi', '--no-interaction', '--no-suggest',
                ( $nodev ? '--no-dev' : () ), ( $noautoloader ? '--no-autoloader' : () )
            ),
            $self->{'_stdout'},
            $self->{'_stderr'}
        ) == 0 or die( "Couldn't Update composer packages" );
    } );

    $self;
}

=item clearCache( )

 Clear composer's internal package cache, including vendor directory

 Return iMSCP::Composer, die on failure

=cut

sub clearCache
{
    my ( $self ) = @_;

    $self->_sandbox( sub {
        executeNoWait(
            $self->_getSuCmd(
                @{ $self->{'_php_cmd'} },
                $self->{'composer_phar'}, 'clear-cache',
                "--working-dir=$self->{'composer_working_dir'}",
                '--no-interaction',
                '--no-ansi'
            ),
            $self->{'_stdout'},
            $self->{'_stderr'}
        ) == 0 or die( "Couldn't clear composer's internal package cache" );

        # See https://getcomposer.org/doc/06-config.md#vendor-dir
        my $vendorDir = "$self->{'composer_working_dir'}/vendor";
        my $composerJson = $self->{'composer_json'};
        if ( $composerJson->{'config'}->{'vendor-dir'} ) {
            ( $vendorDir = $composerJson->{'config'}->{'vendor-dir'} ) =~ s%(?:\$HOME|~)%$self->{'composer_home'}%g;
        }
        iMSCP::Dir->new( dirname => $vendorDir )->remove();
    } );

    $self;
}

=item getComposerJson( [ $hashref = FALSE ] )

 Return composer.json file as string

 Param bool $hashref OPTIONAL Whether composer.json must be returned as a hash reference (default: FALSE)
 Return string|hashref, croak on failure

=cut

sub getComposerJson
{
    my ( $self, $hashref ) = @_;

    $hashref ? $self->{'composer_json'} : to_json( $self->{'composer_json'}, {
        utf8      => TRUE,
        indent    => TRUE,
        canonical => TRUE
    } );
}

=item getComposerVersion()

 Get composer version

 Return string version, die on failure

=cut

sub getComposerVersion
{
    my ( $self ) = @_;

    my $rs = execute( $self->_getSuCmd(
        @{ $self->{'_php_cmd'} }, $self->{'composer_phar'}, '--no-interaction', '--no-ansi', '--version' ),
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    $rs == 0 or die( sprintf( "Couldn't get composer (%s) version: %s", $self->{'composer_phar'}, $stderr ));
    ( $stdout =~ /version\s+([\d.]+)/ );
    $1 or die( sprintf( "Couldn't parse composer (%s) version from version string: %s", $self->{'composer_phar'}, $stdout // '' ));
}

=item setStdRoutines( [ $subStdout = sub { print STDOUT @_ } [, $subStderr = sub { print STDERR @_ }  ] ] )

 Set routines for STDOUT/STDERR processing

 Param CODE $subStdout OPTIONAL Routine for processing of command STDOUT
 Param CODE $subStderr OPTIONAL Routine for processing of command STDERR
 Return iMSCP::Composer, croak on invalid arguments

=cut

sub setStdRoutines
{
    my ( $self, $subStdout, $subStderr ) = @_;

    $subStdout ||= sub {
        chomp $_[0];
        return unless length $_[0];
        debug $_[0], ( caller( 3 ) )[3]
    };
    ref $subStdout eq 'CODE' or croak( 'Expects a routine as first parameter for STDOUT processing' );
    $self->{'_stdout'} = $subStdout;

    $subStderr ||= sub {
        chomp $_[0];
        return unless length $_[0];
        debug $_[0], ( caller( 3 ) )[3]
    };
    ref $subStderr eq 'CODE' or croak( 'Expects a routine as second parameter for STDERR processing' );
    $self->{'_stderr'} = $subStderr;
    $self;
}

=item loadComposerJson( [ $composerJson = $self->{'composer_json} ] )

 Load composer.json

 Return iMSCP::Composer, die on failure

=cut

sub loadComposerJson
{
    my ( $self, $composerJson ) = @_;

    $composerJson //= $self->{'composer_json'} // '';

    return unless length $self->{'composer_json'};

    $self->{'composer_json'} = File::Spec->rel2abs( $self->{'composer_json'}, $self->{'composer_working_dir'} );

    defined( $self->{'composer_json'} = iMSCP::File->new( filename => $self->{'composer_json'} )->get()) or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } )
    );

    $self->{'composer_json'} = from_json( $self->{'composer_json'}, { utf8 => TRUE } );
    $self;
}

=item dumpComposerJson( )

 Dump composer.json into composer working directory

 Return iMSCP::Composer, die on failure

=cut

sub dumpComposerJson
{
    my ( $self ) = @_;

    $self->_createWorkingDir();

    return unless ref $self->{'composer_json'} eq 'HASH';

    my $file = iMSCP::File->new( filename => "$self->{'composer_working_dir'}/composer.json" );
    my $rs = $file->set( $self->getComposerJson());
    $rs ||= $file->save();
    $rs ||= $file->owner( $self->{'_euid'}, $self->{'_egid'} );
    $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));

    $self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _createWorkingDir()

 Create composer working directory

 Return void, die on failure

=cut

sub _createWorkingDir( )
{
    my ( $self ) = @_;

    return if -d $self->{'composer_working_dir'};

    iMSCP::Dir->new( dirname => $self->{'composer_working_dir'} )->make( {
        user           => $self->{'_euid'},
        group          => $self->{'_egid'},
        fixpermissions => TRUE
    } );
}

=item _removeAutoloader()

 Remove composer autoloader

 Return void, die on failure

=cut

sub _removeAutoloader( )
{
    my ( $self ) = @_;

    if ( -d "$self->{'composer_working_dir'}/vendor/composer" ) {
        iMSCP::Dir->new( dirname => "$self->{'composer_working_dir'}/vendor/composer" )->clear(
            undef, qr/^(ClassLoader|autoload_.*)\.php$/
        );
    }

    if ( -f "$self->{'composer_working_dir'}/vendor/autoload.php" ) {
        iMSCP::File->new( filename => "$self->{'composer_working_dir'}/vendor/autoload.php" )->delFile() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => TRUE } )
        );
    }
}

=item _getSuCmd( @_ )

 Return SU command

 Param list @_ Command
 Return arrayref command

=cut

sub _getSuCmd
{
    my $self = shift;

    if ( $self->{'_euid'} == 0 ) {
        $ENV{'COMPOSER_ALLOW_SUPERUSER'} = TRUE;
        $ENV{'COMPOSER_HOME'} = $self->{'composer_home'};
        return \@_;
    }

    [ '/bin/su', '-l', $self->{'user'}, '-s', '/bin/sh', '-c', "COMPOSER_HOME=$self->{'composer_home'} @_" ];
}

=item _sandbox( )

 Execute the given subroutine with privileges of provider user/group

 Return mixed, die on failure

=cut

sub _sandbox
{
    my ( $self, $sub ) = @_;

    local $!;
    local $) = $self->{'_egid'};
    die( sprintf( "Couldn't setgid to %s: %s", $self->{'group'}, $! )) if $!;
    local $> = $self->{'_euid'};
    die( sprintf( "Couldn't setuid to %s: %s", $self->{'user'}, $! )) if $!;

    $sub->();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
