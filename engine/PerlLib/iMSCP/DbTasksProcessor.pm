=head1 NAME

 iMSCP::DbTasksProcessor - i-MSCP database tasks processor

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

package iMSCP::DbTasksProcessor;

use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Stepper;
use JSON;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP database tasks processor.

=head1 PUBLIC METHODS

=over 4

=item process

 Process all db tasks

 Die on failure

=cut

sub process
{
	my $self = shift;

	# Process plugins tasks
	# Must always be processed first to allow plugins to register their listeners on the event manager
	$self->_process(
		'Plugin',
		"
			SELECT
				plugin_id AS id, plugin_name AS name, plugin_status AS status
			FROM
				plugin
			WHERE
				plugin_status IN ('enabled', 'toinstall', 'toenable', 'toupdate', 'tochange', 'todisable', 'touninstall')
			AND
				plugin_error IS NULL
			AND
				plugin_backend = 'yes'
			ORDER BY
				plugin_priority DESC
		"
	);

	# Process SSL certificate toadd|tochange SSL certificates tasks
	$self->_process(
		'SSLcertificate',
		"
			SELECT
				cert_id AS id, domain_type AS name, status AS status
			FROM
				ssl_certs
			WHERE
				status IN ('toadd', 'tochange', 'todelete')
			ORDER BY
				cert_id ASC
		"
	);

	# Process toadd|tochange users tasks
	$self->_process(
		'User',
		"
			SELECT
				admin_id AS id, admin_name AS name, admin_status AS status
			FROM
				admin
			WHERE
				admin_type = 'user'
			AND
				admin_status IN ('toadd', 'tochange')
			ORDER BY
				admin_id ASC
		"
	);

	my $ipsModule = 0;

	# Process toadd|tochange|torestore|toenable|todisable domain tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'Domain',
		"
			SELECT
				domain_id AS id, domain_name AS name, domain_status AS status
			FROM
				domain
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				domain_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
			AND
				admin_status IN('ok', 'disabled')
			ORDER BY
				domain_id ASC
		",
		\$ipsModule
	);

	# Process toadd|tochange|torestore|toenable|todisable subdomains tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'Subdomain',
		"
			SELECT
				subdomain_id AS id, CONCAT(subdomain_name, '.', domain_name) AS name, subdomain_status AS status
			FROM
				subdomain
			INNER JOIN
				domain USING(domain_id)
			WHERE
				subdomain_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
			AND
				domain_status IN('ok', 'disabled')
			ORDER BY
				subdomain_id ASC
		",
		\$ipsModule
	);

	# Process toadd|tochange|torestore|toenable|todisable domain aliases tasks
	# (for each entitty, process only if the parent entity is in a consistent state)
	$self->_process(
		'Alias',
		 "
			SELECT
				alias_id AS id, alias_name AS name, alias_status AS status
			FROM
				domain_aliasses
			INNER JOIN
				domain USING(domain_id)
			WHERE
				alias_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
			AND
				domain_status IN('ok', 'disabled')
			ORDER BY
				alias_id ASC
		 ",
		 \$ipsModule
	);

	# Process toadd|tochange|torestore|toenable|todisable subdomains of domain aliases tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'SubAlias',
		"
			SELECT
				subdomain_alias_id AS id, CONCAT(subdomain_alias_name, '.', alias_name) AS name,
				subdomain_alias_status AS status
			FROM
				subdomain_alias
			INNER JOIN
				domain_aliasses USING(alias_id)
			WHERE
				subdomain_alias_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
			AND
				alias_status IN('ok', 'disabled')
			ORDER BY
				subdomain_alias_id ASC
		",
		\$ipsModule
	);

	# Process toadd|tochange|toenable||todisable|todelete custom DNS records which belong to domains
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'CustomDNS',
		"
			SELECT
				CONCAT('domain_', domain_id) AS id, domain_name AS name, domain_dns_status AS status
			FROM
				domain_dns
			INNER JOIN
				domain USING(domain_id)
			WHERE
				domain_dns_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
			AND
				alias_id = '0'
			AND
				domain_status IN('ok', 'disabled')
			GROUP BY
				domain_id
		"
	);

	# Process toadd|tochange|toenable|todisable|todelete custom DNS records which belong to domains or domain aliases
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'CustomDNS',
		"
			SELECT
				CONCAT('alias_', alias_id) AS id, alias_name AS name, domain_dns_status AS status
			FROM
				domain_dns
			INNER JOIN
				domain_aliasses USING(alias_id)
			WHERE
				domain_dns_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
			AND
				alias_id <> '0'
			AND
				alias_status IN('ok', 'disabled')
			GROUP BY
				alias_id
		"
	);

	# Process toadd|tochange|toenable|todisable|todelete mail tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'Mail',
		"
			SELECT
				mail_id AS id, mail_addr AS name, status AS status
			FROM
				mail_users
			INNER JOIN
				domain USING(domain_id)
			WHERE
				status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
			AND
				domain_status IN('ok', 'todelete', 'disabled')
			ORDER BY
				mail_id ASC
		"
	);

	# Process toadd|tochange|todelete Htusers tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'Htusers',
		"
			SELECT
				id, CONCAT(uname, ':', id) AS name, status
			FROM
				htaccess_users
			INNER JOIN
				domain ON(domain_id = dmn_id)
			WHERE
				status IN ('toadd', 'tochange', 'todelete')
			AND
				domain_status = 'ok'
			ORDER BY
				id ASC
		"
	);

	# Process toadd|tochange|todelete Htgroups tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'Htgroup',
		"
			SELECT
				id, CONCAT(ugroup, ':', id) AS name, status
			FROM
				htaccess_groups
			INNER JOIN
				domain ON(domain_id = dmn_id)
			WHERE
				status IN ('toadd', 'tochange', 'todelete')
			AND
				domain_status = 'ok'
			ORDER BY
				id ASC
		"
	);

	# Process toadd|tochange|todelete Htaccess tasks
	# For each entitty, process only if the parent entity is in a consistent state
	$self->_process(
		'Htaccess',
		"
			SELECT
				id, CONCAT(auth_name, ':', id) AS name, status
			FROM
				htaccess
			INNER JOIN
				domain ON(domain_id = dmn_id)
			WHERE
				status IN ('toadd', 'tochange', 'todelete')
			AND
				domain_status = 'ok'
			ORDER BY
				id ASC
		"
	);

	# Process todelete subdomain aliases tasks
	$self->_process(
		'SubAlias',
		"
			SELECT
				subdomain_alias_id AS id, concat(subdomain_alias_name, '.', alias_name) AS name,
				subdomain_alias_status AS status
			FROM
				subdomain_alias
			INNER JOIN
				domain_aliasses USING(alias_id)
			WHERE
				subdomain_alias_status = 'todelete'
			ORDER BY
				subdomain_alias_id ASC
		",
		\$ipsModule
	);

	# Process todelete domain aliases tasks
	# For each entity, process only if the entity do not have any direct children
	$self->_process(
		'Alias',
		"
			SELECT DISTINCT
				alias_id AS id, alias_name AS name, alias_status AS status
			FROM
				domain_aliasses
			LEFT JOIN
				subdomain_alias USING(alias_id)
			WHERE
				alias_status = 'todelete'
			AND
				subdomain_alias_id IS NULL
			ORDER BY
				alias_id ASC
		",
		\$ipsModule
	);

	# Process todelete subdomains tasks
	$self->_process(
		'Subdomain',
		"
			SELECT
				subdomain_id AS id, CONCAT(subdomain_name, '.', domain_name) AS name, subdomain_status AS status
			FROM
				subdomain
			INNER JOIN
				domain USING(domain_id)
			WHERE
				subdomain_status = 'todelete'
			ORDER BY
				subdomain_id ASC
		",
		\$ipsModule
	);

	# Process todelete domains tasks
	# For each entity, process only if the entity do not have any direct children
	$self->_process(
		'Domain',
		"
			SELECT DISTINCT
				domain_id AS id, domain_name AS name, domain_status AS status
			FROM
				domain
			LEFT JOIN
				domain_aliasses USING(domain_id)
			LEFT JOIN
				mail_users USING(domain_id)
			WHERE
				domain_status = 'todelete'
			AND
				alias_id IS NULL
			AND
				mail_id IS NULL
			ORDER BY
				domain_id ASC
		",
		\$ipsModule
	);

	# Process todelete users tasks
	# For each entity, process only if the entity do not have any direct children
	$self->_process(
		'User',
		"
			SELECT
				admin_id AS id, admin_name AS name, admin_status AS status
			FROM
				admin
			LEFT JOIN
				domain ON(domain_admin_id = admin_id)
			WHERE
				admin_type = 'user'
			AND
				admin_status = 'todelete'
			AND
				domain_id IS NULL
			ORDER BY
				admin_id ASC
		"
	);

	# Process network cards tasks
	$self->_process(
		'NetCard',
		"
			SELECT
				ip_id AS id, CONCAT(ip_card, ':', ip_number) AS name, ip_status AS status
			FROM
				server_ips
			WHERE
				ip_status <> 'ok'
			LIMIT
				1
		"
	);

	# Process IP addresses tasks
	unless(!$ipsModule && $self->{'mode'} ne 'setup') {
		newDebug('Ips_module.log');

		eval { require Modules::Ips } or die(sprintf('Could not load Module::Ips module: %s', $@));
		Modules::Ips->new()->process() == 0 or die(sprintf('Could not process IP addresses',
			getMessageByType('error', { amount => 1, remove => 1 }) || 'Unknown error'
		));

		endDebug();
	}

	# Software installer

	my $rows = $self->{'db'}->doQuery(
		'software_id',
		"
			SELECT
				domain_id, alias_id, subdomain_id, subdomain_alias_id, software_id, path, software_prefix,
				db, database_user, database_tmp_pwd, install_username, install_password, install_email,
				software_status, software_depot, software_master_id
			FROM
				web_software_inst
			WHERE
				software_status IN ('toadd', 'todelete')
			ORDER BY
				domain_id ASC
		"
	);
	ref $rows eq 'HASH' or die($rows);

	debug(sprintf('Processing %s script tasks...', 'imscp-sw-mngr'));

	if(%{$rows}) {
		newDebug('imscp_sw_mngr_engine');

		while(my ($id, $data) = %{$rows}) {
			my $args = escapeShell(encode_json([
				$data->{'domain_id'}, $id, $data->{'path'}, $data->{'software_prefix'}, $data->{'db'},
				$data->{'database_user'}, $data->{'database_tmp_pwd'}, $data->{'install_username'},
				$data->{'install_password'}, $data->{'install_email'}, $data->{'software_status'},
				$data->{'software_depot'}, $data->{'software_master_id'}, $data->{'alias_id'}, $data->{'subdomain_id'},
				$data->{'subdomain_alias_id'}
			]));

			my($stderr, $stdout);
			execute("perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-sw-mngr $args", \$stdout, \$stderr) == 0 or die(
				$stderr || 'Unknown error'
			);

			iMSCP::Dir->new( dirname => "/tmp/sw-$data->{'domain_id'}-$id" )->remove();
		}

		endDebug();
	} else {
		debug(sprintf('No task to process for the %s script.', 'imscp-sw-mngr'));
	}

	$rows = $self->{'db'}->doQuery(
		'software_id',
		"
			SELECT
				software_id, reseller_id, software_archive, software_status, software_depot
			FROM
				web_software
			WHERE
				software_status = 'toadd'
			ORDER BY
				reseller_id ASC
		"
	);
	ref $rows eq 'HASH' or die($rows);

	debug(sprintf('Processing %s script tasks...', 'imscp-pkt-mngr'));

	if(%{$rows}) {
		newDebug('imscp_pkt_mngr_engine.log');

		while(my ($id, $data) = %{$rows}) {
			my $args = escapeShell(encode_json([
				$id, $data->{'reseller_id'}, $data->{'software_archive'}, $data->{'software_status'},
				$data->{'software_depot'}
			]));

			my($stderr, $stdout);
			execute("perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-pkt-mngr $args",  \$stdout, \$stderr) == 0 or die(
				$stderr || 'Unknown error'
			);

			iMSCP::Dir->new( dirname => "/tmp/sw-$data->{'software_archive'}-$id" )->remove();
		}

		endDebug();
	} else {
		debug(sprintf('No task to process for the %s script.', 'imscp-pkt-mngr'));
	}
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::DbTasksProcessor or die on failure

=cut

sub _init
{
	my $self = shift;

	defined $self->{'mode'} or die('mode attribute is not defined');

	$self->{'db'} = iMSCP::Database->factory();
	$self;
}

=item _process($module, $sql [, $ipModule ])

 Process db tasks from the given module

 Param string $module Module name to process
 Param string $sql SQL statement to retrieve item to process
 Param scalref $ipModule
 Return int 0 on success, die on failure

=cut

sub _process
{
	my ($self, $module, $sql, $ipModule) = @_;

	debug(sprintf('Processing %s module tasks...', $module));

	my $dbh = $self->{'db'}->getRawDb();
	my $rows = $dbh->selectall_arrayref($sql, { Slice => { } });

	defined $rows && !$dbh->err() or die($dbh->errstr());

	unless(@{$rows}) {
		debug(sprintf('No task to process for the %s module.', $module));
		return 0;
	}

	$$ipModule++ if defined $ipModule;

	my ($nStep, $nSteps) = (0, scalar @{$rows});

	for my $row(@{$rows}) {
		my ($id, $name, $status) = ($row->{'id'}, $row->{'name'}, $row->{'status'});

		debug(sprintf('Processing %s (%s) tasks for: %s (ID %s)', $module, $status, $name, $id));
		newDebug("${moduleName}_module_$name.log");

		my $module = "Modules::$module";
		eval "require $module" or die(sprintf('Could not load the %s module: %s', $module, $@));

		if ($self->{'mode'} eq 'setup') {
			step(
				sub { $module->new()->process($id) },
				sprintf('Processing %s (%s) tasks for: %s (ID %s)', $module, $status, $name, $id), $nSteps, ++$nStep
			);
		} else {
			$module->new()->process($id) == 0 or die(
				getMessageByType('error', { amount => 1, remove => 1 }) || 'Unknown error'
			);
		}

		endDebug();
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
