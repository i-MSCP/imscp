=head1 NAME

 Servers::cron::Interface - i-MSCP cron server interface

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

package Servers::cron::Interface;

use strict;
use warnings;

=head1 DESCRIPTION

 i-MSCP cron server interface.

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

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
