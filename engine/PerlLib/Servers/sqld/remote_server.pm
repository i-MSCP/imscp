=head1 NAME

 Servers::sqld::remote_server - i-MSCP remote SQL server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::sqld::remote_server;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Execute qw/escapeShell/;
use iMSCP::Crypt qw/decryptBlowfishCBC/;
use iMSCP::TemplateParser;
use version;
use parent 'Servers::sqld::mysql';

=head1 DESCRIPTION

 i-MSCP remote SQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeSqldPreinstall');
	require Servers::sqld::mysql::installer;
	my $installer = Servers::sqld::mysql::installer->getInstance();
	$rs ||= $installer->_setTypeAndVersion();
	$rs ||= $self->_buildConf();
	$rs ||= $installer->_saveConf();
	$rs ||= $self->{'eventManager'}->trigger('afterSqldPreinstall')
}

=item postinstall()

 Process postinstall tasks

 Return int 0

=cut

sub postinstall
{
	0; # Nothing to do there; Only here to prevent parent method to be called
}

=item restart()

 Restart server

 Return int 0

=cut

sub restart
{
	0; # Nothing to do there; Only here to prevent parent method to be called
}

=item createUser($user, $host, $password)

 Create given SQL user

 Param $string $user SQL username
 Param string $host SQL user host
 Param $string $password SQL user password
 Return int 0 on success, die on failure

=cut

sub createUser
{
	my ($self, $user, $host, $password) = @_;

	defined $user or die('$user parameter is not defined');
	defined $host or die('$host parameter is not defined');
	defined $password or die('$password parameter is not defined');

	my $db = iMSCP::Database->factory();
	my $qrs = $db->doQuery(
		'c', 'CREATE USER ?@? IDENTIFIED BY ?' . (
			$self->getType() ne 'mariadb' && version->parse($self->getVersion()) >= version->parse('5.7.6')
				? ' PASSWORD EXPIRE NEVER' : ''
		),
		$user, $host, $password
	);
	ref $qrs eq 'HASH' or die(sprintf('Could not create the %s@%s SQL user: %s', $user, $host, $qrs));
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

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
	my $mysqlGName = $self->{'config'}->{'SQLD_GROUP'};
	my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

	# Make sure that the conf.d directory exists
	$rs = iMSCP::Dir->new( dirname => "$confDir/conf.d" )->make({
		user => $rootUName, group => $rootGName, mode => 0755
	});
	return $rs if $rs;

	# Create the /etc/mysql/my.cnf file if missing
	unless(-f "$confDir/my.cnf") {
		$rs = $self->{'eventManager'}->trigger('onLoadTemplate',  'mysql', 'my.cnf', \my $cfgTpl, { });
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl = "!includedir $confDir/conf.d/\n";
		} elsif($cfgTpl !~ m%^!includedir\s+$confDir/conf.d/\n%m) {
			$cfgTpl .= "!includedir $confDir/conf.d/\n";
		}

		my $file = iMSCP::File->new( filename => "$confDir/my.cnf" );
		$rs = $file->set($cfgTpl);
		$rs ||= $file->save();
		$rs ||= $file->owner($rootUName, $rootGName);
		$rs ||= $file->mode(0644);
		return $rs if $rs;
	}

	$rs ||= $self->{'eventManager'}->trigger('onLoadTemplate',  'mysql', 'imscp.cnf', \my $cfgTpl, { });
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp.cnf" )->get();
		unless(defined $cfgTpl) {
			error(sprintf('Could not read %s', "$self->{'cfgDir'}/imscp.cnf"));
			return 1;
		}
	}

	$cfgTpl = process(
		{
			DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
			DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
			DATABASE_PASSWORD => escapeShell(decryptBlowfishCBC(
				$main::imscpDBKey, $main::imscpDBiv, $main::imscpConfig{'DATABASE_PASSWORD'}
			)),
			DATABASE_USER => $main::imscpConfig{'DATABASE_USER'},
		}
		,
		$cfgTpl
	);

	my $file = iMSCP::File->new( filename => "$confDir/conf.d/imscp.cnf" );
	$rs ||= $file->set($cfgTpl);
	$rs ||= $file->save();
	$rs ||= $file->owner($rootUName, $mysqlGName);
	$rs ||= $file->mode(0640);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterSqldBuildConf');
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
