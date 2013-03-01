#!/usr/bin/perl

=head1 NAME

 Servers::po::courier::installer - i-MSCP Courier IMAP/POP3 Server installer implementation

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

package Servers::po::courier::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Templator;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process installation.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs |= $self->_bkpConfFile($_) for (
		'authdaemonrc', 'userdb', $self::courierConfig{'COURIER_IMAP_SSL'}, $self::courierConfig{'COURIER_POP_SSL'}
	);

	$rs |= $self->_buildAuthdaemonrcFile();
	$rs |= $self->_buildUserdbFile();
	$rs |= $self->_buildSslConfFiles();
	$rs |= $self->_saveConf();
	$rs |= $self->_migrateMailboxes();

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return Servers::po::courier::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforePodInitInstaller', $self, 'courier');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/courier.data";
	my $oldConf = "$self->{'cfgDir'}/courier.old.data";

	tie %self::courierConfig, 'iMSCP::Config','fileName' => $conf, 'noerrors' => 1;

	if(-f $oldConf) {
		tie %self::courierOldConfig, 'iMSCP::Config','fileName' => $oldConf, 'noerrors' => 1;
		%self::courierConfig = (%self::courierConfig, %self::courierOldConfig);
	}

	$self->{'hooksManager'}->trigger('afterPodInitInstaller', $self, 'courier');

	$self;
}

=item _bkpConfFile()

 Backup the given file.

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoBkpConfFile', $cfgFile);

	if(! $rs && -f "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$cfgFile") {
		my $file = iMSCP::File->new('filename' => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$cfgFile");

		if(!-f "$self->{'bkpDir'}/$cfgFile.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.system");
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.$timestamp");
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterPoBkpConfFile', $cfgFile);

	$rs;
}

=item _buildAuthdaemonrcFile()

 Build the authdaemonrc file.

 Return int 0 on success, other on failure

=cut

sub _buildAuthdaemonrcFile
{
	my $self = shift;
	my ($rdata, $file);
	my $rs = 0;

	# Loading the system file from /etc/imscp/backup
	$file = iMSCP::File->new('filename' => "$self->{'bkpDir'}/authdaemonrc.system");
	$rdata = $file->get();

	if (! $rdata) {
		error("Error while reading $self->{'bkpDir'}/authdaemonrc.system file");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforePoBuildAuthdaemonrcFile', \$rdata, 'authdaemonrc');

	# Building the new file (Adding the authuserdb module if needed)
	if(! $rs && $rdata !~ /^\s*authmodulelist="(?:.*)?authuserdb.*"$/gm) {
		$rdata =~ s/(authmodulelist=")/$1authuserdb /gm;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterPoBuildAuthdaemonrcFile', \$rdata, 'authdaemonrc');

	# Storing the new file in the working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/authdaemonrc") if ! $rs;
	$rs |= $file->set($rdata);
	$rs |= $file->save();
	$rs |= $file->mode(0660);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Installing the new file in the production directory
	$rs |= $file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");

	$rs;
}

=item _buildUserdbFile()

 Build the userdb file.

 Return int 0 on success, other on failure

=cut

sub _buildUserdbFile
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoBuildUserdbFile', 'userdb');

	# Storing the new file in the working directory
	$rs |= iMSCP::File->new('filename' => "$self->{'cfgDir'}/userdb")->copyFile("$self->{'wrkDir'}");

	# After build this file is world readable which is is bad
	# Permissions are inherited by production file
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/userdb") if ! $rs;
	$rs |= $file->mode(0600);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Installing the new file in the production directory
	$rs |= $file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");

	$file = iMSCP::File->new('filename' => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/userdb") if ! $rs;
	$rs |= $file->mode(0600);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Creating/Updating userdb.dat file from the contents of the userdb file
	my ($stdout, $stderr);
	$rs |= execute($self::courierConfig{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $self::courierConfig{'CMD_MAKEUSERDB'} returned status $rs") if $rs && ! $stderr;

	$rs |= $self->{'hooksManager'}->trigger('afterPoBuildUserdbFile', 'userdb');

	$rs;
}

=item _buildSslConfFiles()

 Build ssl configuration file.

 Return int 0 on success, other on failure

=cut

sub _buildSslConfFiles
{
	my $self = shift;
	my ($rdata, $file);
	my $rs = 0;

	for ($self::courierConfig{'COURIER_IMAP_SSL'}, $self::courierConfig{'COURIER_POP_SSL'}) {

		# If ssl is not enabled
        last if lc($main::imscpConfig{'SSL_ENABLED'}) ne 'yes';

		$rs = $self->{'hooksManager'}->trigger('beforePoBuildSslConfFiles', $_);

		$file = iMSCP::File->new('filename' => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$_") if ! $rs;

		# read file exit if can not read
		$rdata = $file->get() if ! $rs;

		if (! $rs && ! $rdata){
			$rs |= 1;
			error("Error while reading $self::courierConfig{'AUTHLIB_CONF_DIR'}/$_");
			last;
		}

		# If ssl conf not in place we add if
		if($rdata =~ m/^TLS_CERTFILE=/msg){
			$rdata =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem!mg;
		} else {
			$rdata .= "TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		}

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$_");
		$rs |= $file->set($rdata);
		$rs |= $file->save();
		$rs |= $file->mode(0644);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

		# Installing the new file in the production directory
		$rs |= $file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");

		$rs |= $self->{'hooksManager'}->trigger('afterPoBuildSslConfFiles', $_);
	}

	$rs;
}

=item _saveConf()

 Save Courier configuration.

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/courier.data");
	my $cfg = $file->get() or return 1;

	$rs = $self->{'hooksManager'}->trigger('beforePoSaveConf', \$cfg, 'courier.old.data');

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/courier.old.data") if ! $rs;
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterPoSaveConf', 'courier.old.data');

	$rs;
}

=item _migrateMailboxes()

 Migrate mailboxes.

 Return int 0 on success, other on failure

=cut

sub _migrateMailboxes
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoMigrateMailboxes');

	if(! $rs && defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} ne 'courier') {
		require Servers::mta;

		my $mta	= Servers::mta->factory($main::imscpConfig{'MTA_SERVER'});
		my ($stdout, $stderr);
		my $binPath = "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
		my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

		$rs = execute("$binPath --to-courier --convert --recursive $mailPath", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		warning($stderr) if $stderr && ! $rs;
		error($stderr) if $stderr && $rs;
		error('Error while converting mails') if ! $stderr && $rs;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterPoMigrateMailboxes');

	$rs;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
