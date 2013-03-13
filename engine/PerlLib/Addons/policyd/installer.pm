#!/usr/bin/perl

=head1 NAME

Addons::policyd::installer - i-MSCP Policyd Weight configurator installer

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::policyd::installer;

use strict;
use warnings;
use iMSCP::Debug;
use File::Basename;
use iMSCP::File;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

This is the installer for the Policyd Weight configurator addon.

See Addons::policyd for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hook functions.

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Add policyd installer dialog at end of list of setup dialogs
	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askRBL(@_) }); 0; }
	);
}

=item install()

 Process policyd addon install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->bkpConfFile($self::policydConfig{'POLICYD_CONF_FILE'});
	return $rs if $rs;

	$rs = $self->buildConf();
	return $rs if $rs;

	$self->saveConf();
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item askRBL()

 Ask user about RBL.

 Return int 0;

=cut

sub askRBL
{
	my ($self, $dialog, $rs) = (shift, shift, 0);
	my $dnsblCheckOnly = $main::preseed{'DNSBL_CHECKS_ONLY'} || $self::policydConfig{'DNSBL_CHECKS_ONLY'} ||
		$self::policydOldConfig{'DNSBL_CHECKS_ONLY'} || '';

	$dnsblCheckOnly = lc($dnsblCheckOnly);

	if($main::reconfigure ~~ ['mailfilters', 'all', 'forced'] || $dnsblCheckOnly !~ /^yes|no$/i) {
		($rs, $dnsblCheckOnly) = $dialog->radiolist(
			"
			\\Z4\\Zb\\Zui-MSCP Policyd Weight Addon\\Zn

			Do you want to disable additional checks for MTA, HELO and domain?\n

			\\Z1Yes\\Zn: (may cause some spam messages to be accepted)
			 \\Z4No\\Zn: (default, messages from misconfigured mail service providers
			      will be treated as spam and rejected)
			",
			['yes', 'no'],
			$dnsblCheckOnly ne 'yes' ? 'no' : 'yes'
		);
	}

	$self::policydConfig{'DNSBL_CHECKS_ONLY'} = $dnsblCheckOnly if $rs != 30;

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize Addons::policyd::installer instance.

 Return Addons::policyd::installer

=cut

sub _init{

	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/policyd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/policyd.data";
	my $oldConf	= "$self->{'cfgDir'}/policyd.old.data";

	tie %self::policydConfig, 'iMSCP::Config','fileName' => $conf, 'noerrors' => 1;

	if(-f $oldConf) {
		tie %self::policydOldConfig, 'iMSCP::Config','fileName' => $oldConf, 'noerrors' => 1;
		%self::policydConfig = (%self::policydConfig, %self::policydOldConfig);
	}

	$self;
}

=item bkpConfFile($cfgFile)

 Backup configuration file.

 Param SCALAR Path of file to backup
 Return int 0 on success, 1 on failure

=cut

sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;

	my ($name, $path, $suffix) = fileparse($cfgFile);

	if(-f $cfgFile){
		my $timestamp = time;
		my $file = iMSCP::File->new('filename' => $cfgFile);
		my $rs = $file->copyFile("$self->{'bkpDir'}/$name$suffix.$timestamp");
		return $rs if $rs;
	}

	0;
}

=item saveConf()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub saveConf
{
	my $self = shift;
	my $rootUsr	= $main::imscpConfig{'ROOT_USER'};
	my $rootGrp	= $main::imscpConfig{'ROOT_GROUP'};
	my $rs = 0;

	my $file = iMSCP::File->new(filename => "$self->{'cfgDir'}/policyd.data");

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/policyd.data");
		return 1;
	}

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/policyd.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$file->mode(0640);
}

=item buildConf()

 Build configuration file.

 Return int 0 on success, other on failure

=cut

sub buildConf
{
	my $self = shift;

	my $rs = 0;
	my $uName = $self::policydConfig{'POLICYD_USER'};
	my $gName = $self::policydConfig{'POLICYD_GROUP'};
	my ($name, $path, $suffix) = fileparse($self::policydConfig{'POLICYD_CONF_FILE'});

	unless (-f $self::policydConfig{'POLICYD_CONF_FILE'}) {
		my ($stdout, $stderr);
		$rs = execute(
			"$self::policydConfig{'POLICYD_BIN_FILE'} defaults > $self::policydConfig{'POLICYD_CONF_FILE'}",
			\$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		warning($stderr) if ! $rs && $stderr;
		error($stderr) if $rs && $stderr;
		error("Unable to create default config file") if $rs && ! $stderr;
		return $rs if $rs;
	}

	my $file = iMSCP::File->new('filename' => $self::policydConfig{POLICYD_CONF_FILE});
	my $cfgTpl = $file->get();
	return 1 if ! defined $cfgTpl;

	my $dnsblChecksOnly = $self::policydConfig{DNSBL_CHECKS_ONLY} =~ /^yes$/i ? 0 : 1;
	$cfgTpl =~ s/^\s{0,}\$dnsbl_checks_only\s{0,}=.*$/\n   \$dnsbl_checks_only = $dnsblChecksOnly;          # 1: ON, 0: OFF (default)/mi;

	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$name$suffix");
	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($uName, $gName);
	return $rs if $rs;

	$file->copyFile($self::policydConfig{'POLICYD_CONF_FILE'});
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
