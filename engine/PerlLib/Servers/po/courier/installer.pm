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
use iMSCP::Dir;
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

	for('authdaemonrc', 'userdb', $self::courierConfig{'COURIER_IMAP_SSL'}, $self::courierConfig{'COURIER_POP_SSL'}) {
		$rs = $self->_bkpConfFile($_);
		return $rs if $rs;
	}

	$rs = $self->_buildAuthdaemonrcFile();
	return $rs if $rs;

	$rs = $self->_buildUserdbFile();
	return $rs if $rs;

	$rs = $self->_buildSslConfFiles();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	# Migrate from dovecot if needed
    if(defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'dovecot') {
    	$rs = $self->_migrateFromDovecot();
    	return $rs if $rs;
    }

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

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
	return $rs if $rs;

	if(! $rs && -f "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$cfgFile") {
		my $file = iMSCP::File->new('filename' => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$cfgFile");

		if(!-f "$self->{'bkpDir'}/$cfgFile.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.system");
			return $rs if $rs;
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterPoBkpConfFile', $cfgFile);
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

	unless (defined $rdata) {
		error("Unable to read $self->{'bkpDir'}/authdaemonrc.system file");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforePoBuildAuthdaemonrcFile', \$rdata, 'authdaemonrc');
	return $rs if $rs;

	# Building the new file (Adding the authuserdb module if needed)
	if($rdata !~ /^\s*authmodulelist="(?:.*)?authuserdb.*"$/gm) {
		$rdata =~ s/(authmodulelist=")/$1authuserdb /gm;
	}

	$rs = $self->{'hooksManager'}->trigger('afterPoBuildAuthdaemonrcFile', \$rdata, 'authdaemonrc');
	return $rs if $rs;

	# Storing the new file in the working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/authdaemonrc");

	$rs = $file->set($rdata);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0660);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Installing the new file in the production directory
	$file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");
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
	return $rs if $rs;

	# Storing the new file in the working directory
	$rs = iMSCP::File->new('filename' => "$self->{'cfgDir'}/userdb")->copyFile("$self->{'wrkDir'}");
	return $rs if $rs;

	# After build this file is world readable which is is bad
	# Permissions are inherited by production file
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/userdb");

	$rs = $file->mode(0600);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Installing the new file in the production directory
	$rs = $file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/userdb");

	$rs = $file->mode(0600);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Creating/Updating userdb.dat file from the contents of the userdb file
	my ($stdout, $stderr);
	$rs = execute($self::courierConfig{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $self::courierConfig{'CMD_MAKEUSERDB'} returned status $rs") if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoBuildUserdbFile', 'userdb');
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
		return $rs if $rs;

		$file = iMSCP::File->new('filename' => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$_") if ! $rs;

		# read file exit if can not read
		$rdata = $file->get();

		unless (defined $rdata){
			error("Unable to read $self::courierConfig{'AUTHLIB_CONF_DIR'}/$_");
			return 1;
		}

		# If ssl conf not in place we add if
		if($rdata =~ m/^TLS_CERTFILE=/msg){
			$rdata =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem!mg;
		} else {
			$rdata .= "TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		}

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$_");

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Installing the new file in the production directory
		$rs = $file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");
		return $rs if $rs;

		$rs |= $self->{'hooksManager'}->trigger('afterPoBuildSslConfFiles', $_);
		return $rs if $rs;
	}

	0;
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

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/courier.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforePoSaveConf', \$cfg, 'courier.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/courier.old.data") if ! $rs;

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoSaveConf', 'courier.old.data');
}

=item _migrateFromDovecot()

 Migrate mailboxes from Dovecot.

 Return int 0 on success, other on failure

=cut

sub _migrateFromDovecot
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoMigrateFromDovecot');
	return $rs if $rs;

	# Getting i-MSCP MTA server implementation instance
	require Servers::mta;
	my $mta	= Servers::mta->factory();

	my $binPath = "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
	my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

	# Converting all mailboxes to courier format

	my ($stdout, $stderr);
	$rs = execute("$binPath --to-courier --convert --recursive $mailPath", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while converting mails') if ! $stderr && $rs;
	return $rs if $rs;

	# Converting dovecot subscriptions files to courier format

	my $domainDirs = iMSCP::Dir->new('dirname' => $mailPath);

	for($domainDirs->getDirs()) {

		my $mailboxesDirs = iMSCP::Dir->new('dirname' => "$mailPath/$_");

		for my $mailDir($mailboxesDirs->getDirs()) {

			if(-f "$mailPath/$_/$mailDir/subscriptions") {

				my $subscriptionsFile = iMSCP::File->new('filename' => "$mailPath/$_/$mailDir/subscriptions");

				$rs = $subscriptionsFile->copyFile("$mailPath/$_/$mailDir/courierimapsubscribed");
				return $rs if $rs;

				my $courierimapsubscribedFile = iMSCP::File->new(
					'filename' => "$mailPath/$_/$mailDir/courierimapsubscribed"
				);

				my $courierimapsubscribedFileContent = $courierimapsubscribedFile->get();

				unless(defined $courierimapsubscribedFileContent) {
					error('Unable to read courier courierimapsubscribed file newly created');
					return 1;
				}

				# Converting any subscription entry to courier format
				$courierimapsubscribedFileContent =~ s/^(.*)/INBOX.$1/gm;

				# Writing new courier courierimapsubscribed file
				$rs = $courierimapsubscribedFile->set($courierimapsubscribedFileContent);
				return $rs if $rs;

				$rs = $courierimapsubscribedFile->save();
				return $rs if $rs;

				# Removing no longer needed file
				$rs = $subscriptionsFile->delFile();
				return $rs if $rs;
			}
		}
	}

	$self->{'hooksManager'}->trigger('afterPoMigrateFromDovecot');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
