#!/usr/bin/perl

=head1 NAME

 Modules::Abstract - Base class for i-MSCP modules

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Abstract;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Servers;
use iMSCP::Addons;
use parent 'Common::SimpleClass';

=head1 DESCRIPTION

 Base class for i-MSCP Modules.

=head1 METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

=cut

sub _init
{
	fatal('Developer must define own function for module');
}

=item loadData()

 Load base data for current item.

 return int - 0 on success, other on failure

=cut

sub loadData
{
	fatal('Developer must define own function for module');
}

=item process()

 Process action (add|delete|restore|disable) according item status.

 return int - 0 on success, other on failure

=cut

sub process
{
	fatal('Developer must define own function for module');
}

=item add()

 Add item

 Should be called for database items with a 'toadd|change|toenable|dnschange' status.

 return int - 0 on success, other on failure

=cut

sub add
{
	my $self = shift;

	$self->{'action'} = 'add';
	$self->runAllActions();
}

=item delete()

 Delete item.

 Should be called for database items with a 'delete' status.

 return int - 0 on success, other on failure

=cut

sub delete
{
	my $self = shift;

	$self->{'action'} = 'del';
	$self->runAllActions();
}

=item

 Restore item.

 Should be called for database items with a 'restore' status.

 return int - 0 on success, other on failure

=cut restore()

sub restore
{
	0;
}

=item disable()

 Disable item.

 Should be called for database items with a 'todisable' status.

 return int - 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	$self->{'action'} = 'disable';
	$self->runAllActions();
}

=item runAllActions()

 Trigger actions (preAction, Action, postAction) on each i-MSCP servers and addons.

 return int - 0 on success, other on failure

=cut

sub runAllActions
{
	my $self = shift;
	my $rs = 0;

	@{$self->{'Addons'}} = iMSCP::Addons->new()->get();
	unless(scalar @{$self->{'Addons'}}){
		error('Cannot get addons list');
		return 1;
	}

	@{$self->{'Servers'}} = iMSCP::Servers->new()->get();
	unless(scalar @{$self->{'Servers'}}){
		error('Cannot get servers list');
		return 1;
	}

	# Build service/addon data if provided by the module
	for(@{$self->{'Servers'}}, 'Addon'){
		next if $_ eq 'noserver.pm';
		my $fname = 'build' . uc($_) . 'Data';
		$fname =~ s/\.pm//i;
		$rs = eval "\$self->$fname();";
		error("$@") if($@);
		return 1 if($@)
	}

	$rs |= $self->runAction("pre$self->{action}$self->{type}",	'Servers');
	$rs |= $self->runAction("pre$self->{action}$self->{type}",	'Addons');
	$rs |= $self->runAction("$self->{action}$self->{type}",		'Servers');
	$rs |= $self->runAction("$self->{action}$self->{type}",		'Addons');
	$rs |= $self->runAction("post$self->{action}$self->{type}",	'Servers');
	$rs |= $self->runAction("post$self->{action}$self->{type}",	'Addons');

	$rs;
}

=item runAction()

 Run the given action on each server/addon that implement it.

 return int - 0 on success, other on failure

=cut

sub runAction
{
	my $self = shift;
	my $func = shift;
	my $type = shift;
	my $rs = 0;

	my ($file, $class, $instance);

	for (@{$self->{$type}}) {
		s/\.pm//;
		my $paramName = ($type eq 'Addons') ? 'AddonsData' : $_;

		if(exists $self->{$paramName}) {
			$file = "$type/$_.pm";
			$class = "${type}::$_";
			require $file;
			$instance = $class->factory();

			if ($instance->can($func)) {
				debug("Calling function $func from ${type}::$_");
				$rs = $instance->$func($self->{$paramName});
				last if $rs;
			}
		}
	}

	$rs;
}

=item buildHTTPDData()

 Build HTTPD data.

 This method should be implemented by any module that provides data for HTTPD service.
 Resulting data must be stored in an anonymous array accessible through the 'httpd' attribute.

 return int - 0 on success, other on failure

=cut

sub buildHTTPDData
{
	0;
}

=item buildMTAData()

 Build MTA data.

 This method should be implemented by any module that provides data for MTA service.
 Resulting data must be stored in an anonymous array accessible through the 'mta' attribute.

 return int - 0 on success, other on failure

=cut

sub buildMTAData
{
	0;
}

=item buildPOData()

 Build PO data.

 This method should be implemented by any module that provides data for PO service.
 Resulting data must be stored in an anonymous array accessible through the 'po' attribute.

 return int - 0 on success, other on failure

=cut

sub buildPOData
{
	0;
}

=item buildNAMEDData()

 Build NAMED data.

 This method should be implemented by any module that provides data for NAMED service.
 Resulting data must be stored in an anonymous array accessible through the 'named' attribute.

 return int - 0 on success, other on failure

=cut

sub buildNAMEDData
{
	0;
}

=item buildFTPDData()

 Build FTPD data.

 This method should be implemented by any module that provides data for FTPD service.
 Resulting data must be stored in an anonymous array accessible through the 'ftpd' attribute.

 return int - 0 on success, other on failure

=cut

sub buildFTPDData
{
	0;
}

=item buildCRONData()

 Build CRON data.

 This method should be implemented by any module that provides data for CRON service.
 Resulting data must be stored in an anonymous array accessible through the 'cron' attribute.

 return int - 0 on success, other on failure

=cut

sub buildCRONData
{
	0;
}

=item buildADDONData()

 Build ADDON data.

 This method should be implemented by any module that provides data for i-MSCP Addonss.
 Resulting data must be stored in an anonymous array accessible through the 'AddonsData' attribute.

 return int - 0 on success, other on failure

=cut

sub buildADDONData
{
	0;
}

=back

=head1 AUTHOR

 Daniel Andreca <sci2tech@gmail.com>

=cut

1;
