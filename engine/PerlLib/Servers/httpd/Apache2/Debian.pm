=head1 NAME

 Servers::httpd::Apache2::Debian - i-MSCP (Debian) Apache2 server implementation

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

package Servers::httpd::Apache2::Debian;

use strict;
use warnings;
use iMSCP::Debug qw/ debug error warning /;
use iMSCP::Execute qw/ execute /;
use iMSCP::Service;
use parent $main::imscpConfig{'HTTPD_PACKAGE'}, 'Servers::httpd::Interface';

=head1 DESCRIPTION

 i-MSCP (Debian) Apache2 server implementation.

=head1 PUBLIC METHODS

=over 4

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2PostInstall' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->enable( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Apache2' ];
            0;
        },
        3
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2PostInstall' );
}

=item enableSites( @sites )

 See Servers::httpd::Interface::enableSites()

=cut

sub enableSites
{
    my ($self, @sites) = @_;

    my $rs = execute( [ '/usr/sbin/a2ensite', @sites ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $self->{'reload'} ||= 1;
    0;
}

=item disableSites( @sites )

 See Servers::httpd::Interface::disableSites()

=cut

sub disableSites
{
    my ($self, @sites) = @_;

    execute( [ '/usr/sbin/a2dissite', @sites ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    $self->{'reload'} ||= 1;
    0;
}

=item enableModules( @modules )

 See Servers::httpd::Interface::enableModules()

=cut

sub enableModules
{
    my ($self, @modules) = @_;

    for ( @modules ) {
        next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";

        my $rs = execute( [ '/usr/sbin/a2enmod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    $self->{'restart'} ||= 1;
    0;
}

=item disableModules( @modules )

 See Servers::httpd::Interface::disableModules()

=cut

sub disableModules
{
    my ($self, @modules) = @_;

    execute( [ '/usr/sbin/a2dismod', @modules ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    $self->{'restart'} ||= 1;
    0;
}

=item enableConfs( @conffiles )

 See Servers::httpd::Interface::enableConfs()

=cut

sub enableConfs
{
    my ($self, @conffiles) = @_;
    
    my $rs = execute( [ '/usr/sbin/a2enconf', @conffiles ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $self->{'reload'} ||= 1;
    0;
}

=item disableConfs( @conffiles )

 See Servers::httpd::Interface::disableConfs()

=cut

sub disableConfs
{
    my ($self, @conffiles) = @_;

    execute( [ '/usr/sbin/a2disconf', @conffiles ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    $self->{'reload'} ||= 1;
    0;
}

=item start( )

 See Servers::httpd::Interface::start()

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Start' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Start' );
}

=item stop( )

 See Servers::httpd::Interface::stop()

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Stop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Stop' );
}

=item restart( )

 See Servers::httpd::Interface::restart()

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Restart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Restart' );
}

=item reload( )

 See Servers::httpd::Interface::reload()

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Reload' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Reload' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
