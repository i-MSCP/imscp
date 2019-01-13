=head1 NAME

 Package::AntiRootkits::Rkhunter::Installer - i-MSCP Rkhunter package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::AntiRootkits::Rkhunter::Installer;

use strict;
use warnings;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::ProgramFinder;
use Servers::cron;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Rkhunter package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    $self->_disableDebianConfig();
}

=item postinstall( )

 Process post install tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    my $rs = $self->_addCronTask();
    $rs ||= $self->_scheduleCheck();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _disableDebianConfig( )

 Disable default configuration

 Return int 0 on success, other on failure

=cut

sub _disableDebianConfig
{
    if ( -f '/etc/default/rkhunter' ) {
        my $file = iMSCP::File->new( filename => '/etc/default/rkhunter' );
        my $fileC = $file->getAsRef();
        return 1 unless defined $fileC;

        ${ $fileC } =~ s/(CRON_DAILY_RUN)=".*"/$1="false"/i;
        ${ $fileC } =~ s/(CRON_DB_UPDATE)=".*"/$1="false"/i;

        my $rs = $file->save();
        return $rs if $rs;
    }

    if ( -f '/etc/cron.daily/rkhunter' ) {
        my $rs = iMSCP::File->new( filename => '/etc/cron.daily/rkhunter' )->moveFile( '/etc/cron.daily/rkhunter.disabled' );
        return $rs if $rs;
    }

    if ( -f '/etc/cron.weekly/rkhunter' ) {
        my $rs = iMSCP::File->new( filename => '/etc/cron.weekly/rkhunter' )->moveFile( '/etc/cron.weekly/rkhunter.disabled' );
        return $rs if $rs;
    }

    if ( -f "$::imscpConfig{'LOGROTATE_CONF_DIR'}/rkhunter" ) {
        my $rs = iMSCP::File->new( filename => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/rkhunter" )->moveFile( '/etc/logrotate.d/rkhunter.disabled' );
        return $rs if $rs;
    }

    0;
}

=item _addCronTask( )

 Add cron task

 Return int 0 on success, other on failure

=cut

sub _addCronTask
{
    Servers::cron->factory()->addTask( {
        TASKID  => 'Package::AntiRootkits::Rkhunter',
        MINUTE  => '@weekly',
        HOUR    => '',
        DAY     => '',
        MONTH   => '',
        DWEEK   => '',
        USER    => $::imscpConfig{'ROOT_USER'},
        COMMAND => "nice -n 10 ionice -c2 -n5 perl $::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/bin/imscp-rkhunter > /dev/null 2>&1"
    } );
}

=item _scheduleCheck( )

 Schedule check if log file doesn't exist or is empty

 Return int 0 on success, other on failure

=cut

sub _scheduleCheck
{
    return 0 if -f -s $::imscpConfig{'RKHUNTER_LOG'};

    # Create an empty file to avoid planning multiple check if the installer is
    # run many time
    my $file = iMSCP::File->new( filename => $::imscpConfig{'RKHUNTER_LOG'} );
    $file->set( "Check scheduled...\n" );
    my $rs = $file->save();
    return $rs if $rs;

    $rs = execute(
        "echo 'perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/bin/imscp-rkhunter > /dev/null 2>&1' | at now + 10 minutes",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
