=head1 NAME

 iMSCP::Whiptail Package that wrap Whiptail dialog program.

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Dialog::Whiptail;

use strict;
use warnings;
use iMSCP::Execute;
use parent 'Common::Object';

=head1 DESCRIPTION

 Padckage that wrap Whiptail dialog program.

=head1 PUBLIC METHODS

=over 4

=item yesno($text)

 Display a yesno dialog box

=cut

sub yesno
{
	my ($self, $text) = @_;
}

=item msgbox($text)

 Display a msgbox

=cut

sub msgbox
{
	my ($self, $text) = @_;
}

=item infobox($text)

 Display an infobox

=cut

sub infobox
{
	my ($self, $text) = @_;
}

=item inputbox($text, $init)

 Display an inputbox

=cut

sub inputbox
{
	my ($self, $text, $init) = @_;
}

=item passwordbox($text, $init)

 Display a passwordbox

=cut

sub passwordbox
{
	my ($self, $text, $init) = @_;
}

=item textbox($self, $file)

 Display a textbox

=cut

sub textbox
{
	my ($self, $file) = @_;
}

=item checklist($text [ , @selected ])

 Display a checklist

=cut

sub checklist
{
	my ($self, $text, @selected) = @_;
}

=item radiolist($text [ , @selected ])

 Display a checklist

=cut

sub radiolist
{
	my ($self, $text, @selected) = @_;
}

=item gauge($text, $percent)

 Display a gauge

=cut

sub gauge
{
	my ($self, $text, $percent) = @_;
}

=back

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
