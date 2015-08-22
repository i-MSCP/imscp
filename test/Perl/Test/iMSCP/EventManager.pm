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

package Test::iMSCP::EventManager;

use strict;
use warnings;
use Test::More;

sub registerCroakOnMissingEventParameter
{
	my $eventManager = iMSCP::EventManager->getInstance();
	local $@;
	eval { $eventManager->register() };
	undef $iMSCP::EventManager::_instance;
	$@ && $@ =~ /The \$event parameter is not defined/;
}

sub registerCroakOnMissingListenerParameter
{
	my $eventManager = iMSCP::EventManager->getInstance();
	local $@;
	eval { $eventManager->register('foo') };
	undef $iMSCP::EventManager::_instance;
	$@ && $@ =~ /The \$listener parameter is not defined/;
}

sub registerCroakOnInvalidListenerParameter
{
	my $eventManager = iMSCP::EventManager->getInstance();
	local $@;
	eval { $eventManager->register('foo', 'bar') };
	undef $iMSCP::EventManager::_instance;
	$@ && $@ =~ /Invalid \$listener parameter. Code reference expected./;
}

sub registerReturnExpectedValue
{
	my $eventManager = iMSCP::EventManager->getInstance();
	my $ret = $eventManager->register('foo', sub { 0 });
	undef $iMSCP::EventManager::_instance;
	$ret == 0;
}

sub triggerCroakOnMissingEventParameter
{
	my $eventManager = iMSCP::EventManager->getInstance();
	local $@;
	eval { $eventManager->register() };
	undef $iMSCP::EventManager::_instance;
	$@ && $@ =~ /The \$event parameter is not defined/;
}

sub triggerCroakOnFailure
{
	my $eventManager = iMSCP::EventManager->getInstance();
	local $@;
	eval {
		$eventManager->register('foo', sub { 1 });
		$eventManager->trigger('foo');
	};
	undef $iMSCP::EventManager::_instance;
	$@ && $@ =~ /A listener registered on the 'foo' event has failed: Unknown error/;
}

sub triggerReturnExpectedValue
{
	my $eventManager = iMSCP::EventManager->getInstance();
	$eventManager->register('foo', sub { 0 });
	my $ret = $eventManager->trigger('foo');
	undef $iMSCP::EventManager::_instance;
	$ret == 0;
}

sub triggerPassesParametersToListeners
{
	my $eventManager = iMSCP::EventManager->getInstance();
	$eventManager->register('foo', sub { my ($p1, $p2) = @_; $$p1 = 'foo', $$p2 = 'bar'; 0 });
	$eventManager->trigger('foo', \my $p1, \my $p2);
	undef $iMSCP::EventManager::_instance;
	defined $p1 && $p1 eq 'foo' && defined $p2 && $p2 eq 'bar';
}

sub _initCanLoadListenerFiles
{
	my $eventManager = iMSCP::EventManager->getInstance();
	$eventManager->trigger('foo', \my $p1);
	undef $iMSCP::EventManager::_instance;
	defined $p1 && $p1 eq 'OK';
}

sub runUnitTests
{
	plan tests => 10;  # Number of tests planned for execution

	if(require_ok('iMSCP::EventManager')) {
		eval {
			$main::imscpConfig{'CONF_DIR'} = '/tmp/foo';

			ok registerCroakOnMissingEventParameter, 'register() croak on missing $event parameter';
			ok registerCroakOnMissingListenerParameter, 'register() croak on missing $listener parameter';
			ok registerCroakOnInvalidListenerParameter, 'register() croak on invalid $listener parameter';
			ok registerReturnExpectedValue, 'register() return expected value';

			ok triggerCroakOnMissingEventParameter, 'trigger() croak on missing event parameter';
			ok triggerCroakOnFailure, 'trigger() croak on failure';
			ok triggerReturnExpectedValue, 'trigger() return expected value';
			ok triggerPassesParametersToListeners, 'trigger() passes parameters to listeners';

			$main::imscpConfig{'CONF_DIR'} = shift;

			ok _initCanLoadListenerFiles, '_init() can load listener files';
		};

		undef $main::imscpConfig{'CONF_DIR'};
		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
	}
}

1;
__END__
