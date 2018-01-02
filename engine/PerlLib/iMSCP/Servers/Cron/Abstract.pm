=head1 NAME

 iMSCP::Servers::Cron::Abstract - i-MSCP cron server abstract implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Servers::Cron::Abstract;

use strict;
use warnings;
use parent 'iMSCP::Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP cron server abstract implementation.
 
=head1 PUBLIC METHODS

=over 4

=item addTask( \%data [, $filepath = '/path/to/default/cron/file' ] )

 Add a new cron task
 
  The following events *MUST* be triggered:
  - beforeCronAddTask( \$crontab, \%crondata )
  - afterCronAddTask( \$crontab, \%crondata )

 Param hash \%data Cron task data:
  - TASKID :Cron task unique identifier
  - MINUTE  : OPTIONAL Minute or shortcut such as @daily, @monthly... (Default: @daily)
  - HOUR    : OPTIONAL Hour - ignored if the MINUTE field defines a shortcut (Default: *)
  - DAY     : OPTIONAL Day of month - ignored if the MINUTE field defines a shortcut (Default: *)
  - MONTH   : OPTIONAL Month - ignored if the MINUTE field defines a shortcut - Default (Default: *)
  - DWEEK   : OPTIONAL Day of week - ignored if the MINUTE field defines a shortcut - (Default: *)
  - USER    : OPTIONAL Use under which the command must be run (default: root)
  - COMMAND : Command to run
  Param string $filepath OPTIONAL Cron file path (default: imscp cron file)
  Return int 0 on success, other on failure

=cut

sub addTask
{
    my ($self) = @_;

    die ( sprintf( 'The %s package must implement the addTask() method', ref $self ));
}

=item deleteTask( \%data [, $filepath = '/path/to/default/cron/file' ] )

 Delete a cron task
 
  The following events *MUST* be triggered:
  - beforeCronDeleteTask( \$crontab, \%crondata )
  - afterCronDeleteTask( \$crontab, \%crondata )

 Param hash \%data Cron task data:
  - TASKID Cron task unique identifier
 Param string $filepath OPTIONAL Cron file path (default: imscp cron file)
 Return int 0 on success, other on failure

=cut

sub deleteTask
{
    my ($self) = @_;

    die ( sprintf( 'The %s package must implement the deleteTask() method', ref $self ));
}

=item enableSystemCronTask( $cronTask [, $directory = ALL ] )

 Enable a system cron tasks

 Param string $cronTask Cron task name
 Param string $directory OPTIONAL Directory in which cron task must be searched (e.g: cron.d,cron.hourly,cron.daily,cron.weekly,cron.monthly)
 Return int 0 on success, other on failure

=cut

sub enableSystemCronTask
{
    my ($self) = @_;

    die ( sprintf( 'The %s package must implement the enableSystemCronTask() method', ref $self ));
}

=item disableSystemCrontask( $cronTask [, $directory = ALL ] )

 Disable a system cron task
 
 Param string $cronTask Cron task name
 Param string $directory OPTIONAL Directory in which cron task must be searched (e.g: cron.d,cron.hourly,cron.daily,cron.weekly,cron.monthly)
 Return int 0 on success, other on failure

=cut

sub disableSystemCrontask
{
    my ($self) = @_;

    die ( sprintf( 'The %s package must implement the disableSystemCrontask() method', ref $self ));
}

=back

=head1 PRIVATE METHODS

=over 4

=item _validateCronTask( )

 Validate cron task fields

 Return void, die if a field isn't valid

=cut

sub _validateCronTask
{
    my ($self, $data) = @_;

    if ( grep( $data->{'MINUTE'} eq $_, qw/ @reboot @yearly @annually @monthly @weekly @daily @midnight @hourly / ) ) {
        $data->{'HOUR'} = $data->{'DAY'} = $data->{'MONTH'} = $data->{'DWEEK'} = '';
        return;
    }

    for ( qw/ minute hour day month dweek / ) {
        $self->_validateField( $_, $data->{ uc( $_ ) } );
    }
}

=item _validateField( )

 Validate the given cron task field

 Param string $name Fieldname
 Param string $value Fieldvalue
 Retirn void, die if the given field isn't valid

=cut

sub _validateField
{
    my (undef, $name, $value) = @_;

    defined $name or die( '$name is undefined' );
    defined $value or die( '$value is undefined' );
    $value ne '' or die( sprintf( "Value for the '%s' cron task field cannot be empty", $name ));
    return if $value eq '*';

    my $step = '[1-9]?[0-9]';
    my $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
    my $days = 'mon|tue|wed|thu|fri|sat|sun';
    my @namesArr = ();
    my $pattern;

    if ( $name eq 'minute' ) {
        $pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*';
    } elsif ( $name eq 'hour' ) {
        $pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
    } elsif ( $name eq 'day' ) {
        $pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
    } elsif ( $name eq 'month' ) {
        @namesArr = split '|', $months;
        $pattern = "([ ]*(\b[0-1]?[0-9]\b)[ ]*)|([ ]*($months)[ ]*)";
    } elsif ( $name eq 'dweek' ) {
        @namesArr = split '|', $days;
        $pattern = "([ ]*(\b[0]?[0-7]\b)[ ]*)|([ ]*($days)[ ]*)";
    }

    defined $pattern or die( sprintf( "Unknown '%s' cron task field", $name ));

    my $range = "((($pattern)|(\\*\\/$step)?)|((($pattern)-($pattern))(\\/$step)?))";
    my $longPattern = "$range(,$range)*";

    $value =~ /^$longPattern$/i or die(
        sprintf( "Invalid value '%s' given for the '%s' cron task field", $value, $name )
    );

    for ( split ',', $value ) {
        next unless /^(?:(?:(?:$pattern)-(?:$pattern))(?:\/$step)?)+$/;

        my @compare = split '-';
        my @compareSlash = split '/', $compare['1'];

        $compare[1] = $compareSlash[0] if scalar @compareSlash == 2;

        my ($left) = grep { $namesArr[$_] eq lc( $compare[0] ) } 0 .. $#namesArr;
        my ($right) = grep { $namesArr[$_] eq lc( $compare[1] ) } 0 .. $#namesArr;

        $left = $compare[0] unless $left;
        $right = $compare[1] unless $right;

        if ( int( $left ) > int( $right ) ) {
            die( sprintf( "Invalid value '%s' given for the '%s' cron task field", $value, $name ));
        }
    }

    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
