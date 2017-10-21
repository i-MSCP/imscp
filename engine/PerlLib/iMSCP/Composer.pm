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
use iMSCP::Cwd;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use JSON qw/ from_json to_json /;
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 Perl frontEnd to PHP dependency manager (Composer).

=head1 PUBLIC METHODS

=over 4

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
        $self->{'composer_json'}->{'require_dev'}->{$package} = $packageVersion ||= 'dev-master';
        return;
    }

    $self->{'composer_json'}->{'require'}->{$package} = $packageVersion ||= 'dev-master';
}

=item installComposer( )

 Install composer globally (under /usr/local/bin as composer)

 Return void, die on failure

=cut

sub installComposer
{
    my ($self) = @_;

    local $ENV{'COMPOSER_HOME'} = File::HomeDir->users_home( $main::imscpConfig{'ROOT_USER'} ) . '/.composer';

    if ( -x "/usr/local/bin/composer"
        && version->parse( `/usr/local/bin/composer --no-ansi --version 2>/dev/null` =~ /version\s+([\d.]+)/ )
        == version->parse( $self->{'_composer_version'} )
    ) {
        debug( "Composer version is already $self->{'_composer_version'}. Skipping installation..." );
        return;
    }

    iMSCP::Dir->new( dirname => $ENV{'COMPOSER_HOME'} )->clear( undef, qr/\Q.phar\E$/ ) if -d $ENV{'COMPOSER_HOME'};

    my $composerInstaller = File::Temp->new( UNLINK => 1 );
    my $rs = execute(
        "/usr/bin/curl --fail --connect-timeout 5 -s -S https://getcomposer.org/installer 1> $composerInstaller ",
        undef, \ my $stderr,
    );
    $rs == 0 or die( sprintf( "Couldn't download composer: %s", $stderr || 'Unknown error' ));
    $rs = executeNoWait(
        $self->_getCmd(
            @{$self->{'_php_cmd'}}, $composerInstaller, '--', "--no-ansi", "--version=$self->{'_composer_version'}",
            "--install-dir=/usr/local/bin", "--filename=composer"
        ),
        $self->{'_stdout'},
        $self->{'_stderr'}
    );
    $rs == 0 or die( "Couldn't install composer" );
}

=item installPackages( [ $requireDev = false ] )

 Install or update packages

 Param bool $requireDev Flag indicating whether or not packages listed in
                        require-dev must be installed
 Return void, die on failure

=cut

sub installPackages
{
    my ($self, $requireDev) = @_;

    local $ENV{'COMPOSER_ALLOW_SUPERUSER'} = 1;
    local $ENV{'COMPOSER_HOME'} = "$self->{'home_dir'}/.composer";
    local $CWD = $self->{'home_dir'};

    if ( $self->{'home_dir'} ne $self->{'working_dir'} ) {
        iMSCP::Dir->new( dirname => $self->{'working_dir'} )->make(
            {
                user           => $self->{'user'},
                group          => $self->{'group'},
                mode           => 0750,
                fixpermissions => 0 # Set permissions only on creation
            }
        );
    }

    my $file = iMSCP::File->new( filename => "$self->{'working_dir'}/composer.json" );
    $file->set( $self->getComposerJson());

    my $rs = $file->save();
    $rs ||= $file->owner( $self->{'user'}, $self->{'group'} );
    $rs ||= $file->mode( 0644 );
    $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
    $rs = executeNoWait(
        $self->_getCmd(
            @{$self->{'_php_cmd'}},
            '/usr/local/bin/composer', 'update', '--no-progress', '--no-ansi', '--no-interaction',
            ( $requireDev ? () : '--no-dev' ), '--no-suggest', '--classmap-authoritative',
            "--working-dir=$self->{'working_dir'}"
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

    local $ENV{'COMPOSER_ALLOW_SUPERUSER'} = 1;
    local $ENV{'COMPOSER_HOME'} = "$self->{'home_dir'}/.composer";
    local $CWD = $self->{'home_dir'};

    my $rs = executeNoWait(
        $self->_getCmd( @{$self->{'_php_cmd'}}, '/usr/local/bin/composer', 'clearcache', '--no-ansi' ),
        $self->{'_stderr'},
        $self->{'_stdout'}
    );
    $rs == 0 or die( "Couldn't clear composer's internal package cache" );

    # FIXME: https://getcomposer.org/doc/06-config.md#vendor-dir
    iMSCP::Dir->new( dirname => "$self->{'working_dir'}/vendor" )->remove();
}

=item checkPackageRequirements( )

 Check package requirements

 Return void, die if requirements are not met

=cut

sub checkPackageRequirements
{
    my ($self) = @_;

    -d $self->{'working_dir'} or die( "Unmet requirements (All packages)" );

    local $ENV{'COMPOSER_ALLOW_SUPERUSER'} = 1;
    local $ENV{'COMPOSER_HOME'} = "$self->{'home_dir'}/.composer";
    local $CWD = $self->{'home_dir'};

    while ( ( my $package, my $version ) = each( %{$self->{'composer_json'}->{'require'}} ) ) {
        $self->{'_stdout'} && $self->{'_stdout'}(
            sprintf( "Checking requirements for the %s (%s) composer package", $package, $version )
        );

        my $stderr;
        executeNoWait(
            $self->_getCmd(
                @{$self->{'_php_cmd'}}, '/usr/local/bin/composer', 'show', '--no-ansi', '--no-interaction',
                "--working-dir=$self->{'working_dir'}", $package, $version
            ),
            sub {},
            sub { $stderr .= $_[0] =~ s /^\s+|\s+$//r; }
        ) == 0 or die( sprintf( "Unmet requirements (%s %s): %s", $package, $version, $stderr ));
    }
}

=item getComposerJson( )

 Return composer.json file as string

 Return void, die on failure

=cut

sub getComposerJson
{
    to_json(
        $_[0]->{'composer_json'},
        {
            utf8      => 1,
            indent    => 1,
            canonical => 1,
        }
    );
}

=item setStdRoutines( [ $subStdout = sub { print STDOUT @_ } [, $subStderr = sub { print STDERR @_ }  ] ])

 Set routines for processing of composer command STDOUT/STDERR

 Param CODE $subStdout OPTIONAL Routine for processing of command STDOUT line by line
 Param CODE $subStderr OPTIONAL Routine for processing of command STDERR line by line
 Return void, die on invalid arguments

=cut

sub setStdRoutines
{
    my ($self, $subStdout, $subStderr) = @_;

    $self->{'_stdout'} = $subStdout || sub { print STDOUT @_ };
    ref $self->{'_stdout'} eq 'CODE' or die( 'Expects CODE as first parameter for STDOUT processing' );

    $self->{'_stderr'} = $subStderr || sub { print STDERR @_ };
    ref $self->{'_stderr'} eq 'CODE' or die( 'Expects CODE as second parameter for STDERR processing' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Composer, die on failure

=cut

sub _init
{
    my ($self) = @_;

    # Public attributes
    $self->{'user'} //= $main::imscpConfig{'ROOT_USER'};
    $self->{'group'} //= getgrgid(
        ( getpwnam( $self->{'user'} ) )[3] // die( "Couldn't find user" )
    ) // die( "Couldn't find group" );
    $self->{'home_dir'} = File::Spec->canonpath( $self->{'home_dir'} // File::HomeDir->users_home( $self->{'user'} ));
    $self->{'working_dir'} = File::Spec->canonpath( $self->{'working_dir'} // $self->{'home_dir'} );
    $self->{'composer_json'} = from_json( $self->{'composer_json'} || <<'EOT', { utf8 => 1 } );
{
    "config": {
        "preferred-install":"dist",
        "process-timeout":2000,
        "discard-changes":true
    },
    "prefer-stable":true,
    "minimum-stability":"dev"
}
EOT
    # Private attributes
    $self->{'_composer_version'} = '1.5.2';
    $self->{'_php_cmd'} = [
        '/usr/bin/php', '-d', "date.timezone=$main::imscpConfig{'TIMEZONE'}", '-d', 'allow_url_fopen=1',
        '-d suhosin.executor.include.whitelist=phar'
    ];

    $self;
}

=item _getSuCmd( )

 Return SU command

 Return arrayref command to be executed

=cut

sub _getCmd
{
    my ($self) = shift;

    return \@_ if $self->{'user'} eq $main::imscpConfig{'ROOT_USER'};

    [ '/bin/su', '-l', $self->{'user'}, '-m', '-s', '/bin/sh', '-c', "@_" ];
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
