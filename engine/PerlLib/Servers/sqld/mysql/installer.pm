=head1 NAME

 Servers::sqld::mysql::installer - i-MSCP MySQL server installer implementation

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

package Servers::sqld::mysql::installer;

use strict;
use warnings;
use iMSCP::Crypt;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use Servers::sqld::mysql;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	$self->_setVersion();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_buildConf();
	return $rs if $rs;

	$self->_saveConf();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	setRights("$self->{'config'}->{'SQLD_CONF_DIR'}/imscp.cnf", {
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => '0640' }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::sqld::mysql:installer

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'sqld'} = Servers::sqld::mysql->getInstance();

	$self->{'eventManager'}->trigger('beforeSqldInitInstaller', $self, 'mysql') and fatal(
		'mysql - beforeSqldInitInstaller has failed'
	);

	$self->{'cfgDir'} = $self->{'sqld'}->{'cfgDir'};
	$self->{'config'}= $self->{'sqld'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/mysql.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self->{'eventManager'}->trigger('afterSqldInitInstaller', $self, 'mysql') and fatal(
		'mysql - afterSqldInitInstaller has failed'
	);

	$self;
}

=item _setVersion()

 Set SQL server version

 Return 0 on success, other on failure

=cut

sub _setVersion
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldSetVersion');

	my $version = iMSCP::Database->factory()->doQuery(1, 'SELECT VERSION()');
	unless(ref $version eq 'HASH') {
		error($version);
		return 1;
	}

	($version) = ((keys %{$version})[0]) =~ /^([0-9]+(?:\.[0-9]+){1,2})/;

	unless(defined $version) {
		error('Unable to set SQL server version');
		return 1;
	}

	debug("SQL server version set to: $version");
	$self->{'config'}->{'SQLD_VERSION'} = $version;

	$self->{'eventManager'}->trigger('afterSqldSetVersion');
}

=item _buildConf()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeSqldBuildConf');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

	my $cfgTpl;
	$rs = $self->{'eventManager'}->trigger('onLoadTemplate',  'mysql', 'imscp.cnf', \$cfgTpl, {
		USER => $rootUName, GROUP => $rootGName, CONFDIR => $confDir }
	);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp.cnf" )->get();
		unless(defined $cfgTpl) {
			error("Unable to read $self->{'cfgDir'}/imscp.cnf");
			return 1;
		}
	}

	my $variables = {
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_PASSWORD => escapeShell(
			iMSCP::Crypt->getInstance()->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'})
		),
		DATABASE_USER => $main::imscpConfig{'DATABASE_USER'}
	};

	if(version->parse("$self->{'config'}->{'SQLD_VERSION'}") >= version->parse('5.5')) {
		$cfgTpl .= <<EOF;
[mysqld]
innodb_use_native_aio = {INNODB_USE_NATIVE_AIO}
EOF

		$variables->{'INNODB_USE_NATIVE_AIO'} = ($self->_isMysqldInsideCt()) ? 0 : 1;
	}

	$cfgTpl = process($variables, $cfgTpl);

	my $file = iMSCP::File->new( filename => "$confDir/imscp.cnf" );

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterSqldBuildConf');
}

=item _saveConf()

 Save configuration file

 Return in 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'cfgDir'}/mysql.data" )->copyFile("$self->{'cfgDir'}/mysql.old.data");
}

=item _isMysqldInsideCt()

 Does the Mysql server is run inside an unprivileged VE (OpenVZ container)

 Return int 1 if the Mysql server is run inside an OpenVZ container, 0 otherwise

=cut

sub _isMysqldInsideCt
{
	if(-f '/proc/user_beancounters') {
		my ($stdout, $stderr);
		my $rs = execute('cat /proc/1/status | grep --color=never envID', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		warning($stderr) if $rs && $stderr;
		return $rs if $rs;

		if($stdout =~ /envID:\s+(\d+)/) {
			return ($1 > 0) ? 1 : 0;
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
