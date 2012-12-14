#!/usr/bin/perl

=head1 NAME

Addons::policyd::installer - i-MSCP Policyd Weight configurator installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::policyd::installer;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

This is the installer for the Policyd Weight configurator addon.

See Addons::policyd for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hook functions.

 Param iMSCP::HooksManager instance
 Return int - 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Add policyd installer dialog at end of list of setup dialogs
	$hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askRBL(@_) }); 0; }
	);
}

=item install()

 Process policyd addon install tasks.

 Return int -  0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs |= $self->bkpConfFile($self::policydConfig{'POLICYD_CONF_FILE'});
	$rs |= $self->buildConf();
	$rs |= $self->saveConf();

	$rs;
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item askRBL()

 Ask user about RBL.

 Return int - 0;

=cut

sub askRBL
{
	my ($self, $dialog, $rs) = (shift, shift, 0);
	my $dnsblCheckOnly = $main::preseed{'DNSBL_CHECKS_ONLY'} || $self::policydConfig{'DNSBL_CHECKS_ONLY'} ||
		$self::policydOldConfig{'DNSBL_CHECKS_ONLY'} || '';

	$dnsblCheckOnly = lc($dnsblCheckOnly);

	if($main::reconfigure || $dnsblCheckOnly !~ /^yes|no$/i) {
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

 Called by new(). Initialize Addons::policyd::installer instance.

 Return Addons::policyd::installer
=cut

sub _init{

	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/policyd";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";

	my $conf = "$self->{cfgDir}/policyd.data";
	my $oldConf	= "$self->{cfgDir}/policyd.old.data";

	tie %self::policydConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if($oldConf) {
		tie %self::policydOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::policydConfig = (%self::policydConfig, %self::policydOldConfig);
	}

	$self;
}

=item bkpConfFile($cfgFile)

 Backup configuration file.

 Param SCALAR Path of file to backup
 Return int -  0 on success, 1 on failure

=cut

sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;

	use File::Basename;

	my ($name, $path, $suffix) = fileparse($cfgFile);

	if(-f $cfgFile){
		my $file = iMSCP::File->new(filename => $cfgFile);
		$file->copyFile("$self->{bkpDir}/$name$suffix.$timestamp") and return 1;
	}

	0;
}

=item saveConf()

 Save configuration

 Return int - 0 on success, other on failure

=cut

sub saveConf
{
	my $self = shift;
	my $rootUsr	= $main::imscpConfig{'ROOT_USER'};
	my $rootGrp	= $main::imscpConfig{'ROOT_GROUP'};
	my $rs = 0;

	use iMSCP::File;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/policyd.data");
	my $cfg = $file->get();
	return 1 unless $cfg;
	$rs	|= $file->mode(0640);
	$rs	|= $file->owner($rootUsr, $rootGrp);

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/policyd.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($rootUsr, $rootGrp);

	$rs;
}

=item buildConf()

 Build configuration file.

 Return int -  0 on success, other on failure

=cut

sub buildConf
{
	my $self = shift;

	use iMSCP::Execute;
    use File::Basename;

	my $rs = 0;
	my $uName = $self::policydConfig{'POLICYD_USER'};
	my $gName = $self::policydConfig{'POLICYD_GROUP'};
	my ($name, $path, $suffix) = fileparse($self::policydConfig{'POLICYD_CONF_FILE'});

	unless (-f $self::policydConfig{'POLICYD_CONF_FILE'}){
		my ($stdout, $stderr);
		$rs |= execute(
			"$self::policydConfig{POLICYD_BIN_FILE} defaults > $self::policydConfig{POLICYD_CONF_FILE}",
			\$stdout,
			\$stderr
		);
		debug("$stdout") if $stdout;
		warning("$stderr") if !$rs && $stderr;
		error("$stderr") if $rs && $stderr;
		error("Can not create default config file") if $rs && !$stderr;
		return $rs if $rs;
	}

	my $file	= iMSCP::File->new(filename => $self::policydConfig{POLICYD_CONF_FILE});
	my $cfgTpl	= $file->get();
	return 1 unless $cfgTpl;

	my $dnsblChecksOnly = $self::policydConfig{DNSBL_CHECKS_ONLY} =~ /^yes$/i ? 0 : 1;
	$cfgTpl =~ s/^\s{0,}\$dnsbl_checks_only\s{0,}=.*$/\n   \$dnsbl_checks_only = $dnsblChecksOnly;          # 1: ON, 0: OFF (default)/mi;

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/$name$suffix");
	$rs |= $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($uName, $gName);
	$rs |= $file->copyFile($self::policydConfig{POLICYD_CONF_FILE});

	$rs;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>

=cut

1;
