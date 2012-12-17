#!/usr/bin/perl

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

package Servers::ftpd::proftpd;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::HooksManager;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdInit', $self, 'proftpd');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";
	$self->{'tplDir'} = "$self->{cfgDir}/parts";

	$self->{'commentChar'} = '#';

	tie %self::proftpdConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/proftpd.data";
	$self->{$_} = $self::proftpdConfig{$_} for keys %self::proftpdConfig;

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdInit', $self, 'proftpd');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	use Servers::ftpd::proftpd::installer;
	Servers::ftpd::proftpd::installer->new()->registerSetupHooks($hooksManager);
}

sub install
{
	my $self = shift;
	my $rs = 0;

	use Servers::ftpd::proftpd::installer;

	$rs |= Servers::ftpd::proftpd::installer->new()->install();

	$rs;
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdUninstall', 'proftpd');

	use Servers::ftpd::proftpd::uninstaller;

	$rs |= Servers::ftpd::proftpd::uninstaller->new()->uninstall();

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdUninstall', 'proftpd');

	$rs |= $self->restart();

	$rs;
}

sub postinstall
{
	my $self = shift;

	$self->{'restart'} = 'yes';

	0;
}

sub restart
{
	my $self = shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdRestart');

	# Reload config
	$rs = execute("$self->{CMD_FTPD} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	debug("$stderr") if $stderr && !$rs;
	error("$stderr") if $stderr && $rs;
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdRestart');

	0;
}

sub addDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	use iMSCP::File;
	use iMSCP::Templator;

	my $errmsg = {
		'FILE_NAME'	=> 'You must supply a file name!',
		'PATH' => 'you must supply mount point!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	iMSCP::File->new(
		filename => "$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}"
	)->copyFile( "$self->{bkpDir}/$data->{FILE_NAME}.".time ) and $rs = 1
	if -f "$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}";

	my $template = ($data->{ROOT_DOMAIN} eq 'true') ? 'proftpd_root.conf.tpl' : 'proftpd.conf.tpl';
	my $file = iMSCP::File->new( filename => "$self->{tplDir}/$template");
	my $content	= $file->get();

	if(! $content){
		error("Can not read $self->{tplDir}/$template");
		return 1;
	}

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdAddDmn', \$data);

	$content = process({PATH => $data->{PATH}}, $content);
	$file = iMSCP::File->new( filename => "$self->{wrkDir}/$data->{FILE_NAME}");

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdAddDmn', $data);

	$file->set($content);

	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile("$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}");

	$rs;
}

sub delDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0 ;

	use iMSCP::File;
	use iMSCP::Templator;

	my $errmsg = { 'FILE_NAME'	=> 'You must supply a file name!' };

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdDelDmn', \$data);

	iMSCP::File->new(filename => "$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}")->delFile() and $rs = 1;

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdDelDmn', $data) if $rs != 1;

	$rs;
}

sub addSub
{
	my $self = shift;

	$self->addDmn(@_);
}

sub delSub
{
	my $self = shift;

	$self->delDmn(@_);
}

sub getTraffic
{
	my $self = shift;
	my $who = shift;
	my $trfFile	= "$main::imscpConfig{TRAFF_LOG_DIR}/$self::proftpdConfig{FTP_TRAFF_LOG}";

	use iMSCP::File;

	unless(exists $self->{logDb}){

		$self->{logDb} = {};
		my $rs = iMSCP::File->new(filename => $trfFile)->moveFile("$trfFile.old") if -f $trfFile;

		if($rs){
			delete $self->{logDb};
			return 0;
		}

		if(-f "$trfFile.old"){
			my $content = iMSCP::File->new(filename => "$trfFile.old")->get();
			while($content =~ /^(\d+)\s[^\@]+\@(.*)$/mg){
				$self->{logDb}->{$2} += $1 if (defined $2 && defined $1);
			}
		}
	}

	$self->{'logDb'}->{$who} ? $self->{'logDb'}->{$who} : 0;
}

END {
	use iMSCP::File;

	my $endCode	= $?;
	my $self = Servers::ftpd::proftpd->new();
	my $rs = 0;
	my $trfFile	= "$main::imscpConfig{TRAFF_LOG_DIR}/$self::proftpdConfig{FTP_TRAFF_LOG}";

	$rs = $self->restart() if $self->{'restart'} && $self->{'restart'} eq 'yes';
	$rs |= iMSCP::File->new(filename => "$trfFile.old")->delFile() if -f "$trfFile.old";

	$? = $endCode || $rs;
}

1;
