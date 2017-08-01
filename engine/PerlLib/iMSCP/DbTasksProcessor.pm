=head1 NAME

 iMSCP::DbTasksProcessor - i-MSCP database tasks processor

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use strict;
use warnings;
use Encode qw / encode_utf8 /;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Stepper;
use JSON;
use MIME::Base64 qw/ encode_base64 /;
use parent 'Common::SingletonClass';

# Ensure backward compatibility with plugins
BEGIN { *process = \&processDbTasks; }

=head1 DESCRIPTION

 i-MSCP database tasks processor.

=head1 PUBLIC METHODS

=over 4

=item processDbTasks

 Process all db tasks

 Die on failure

=cut

sub processDbTasks
{
    my ($self) = @_;

    # Process plugins tasks
    # Must always be processed first to allow the plugins registering their listeners on the event manager
    $self->_processModuleDbTasks(
        'Modules::Plugin',
        "
            SELECT plugin_id AS id, plugin_name AS name
            FROM plugin
            WHERE plugin_status IN ('enabled', 'toinstall', 'toenable', 'toupdate', 'tochange', 'todisable', 'touninstall')
            AND plugin_error IS NULL AND plugin_backend = 'yes'
            ORDER BY plugin_priority DESC
        ",
        'per_item_log_file'
    );

    # Process server IP addresses
    $self->_processModuleDbTasks(
        'Modules::ServerIP',
        "
            SELECT ip_id AS id, ip_number AS name
            FROM server_ips
            WHERE ip_status IN( 'toadd', 'tochange', 'todelete' )
        "
    );

    # Process SSL certificate toadd|tochange SSL certificates tasks
    $self->_processModuleDbTasks(
        'Modules::SSLcertificate',
        "
            SELECT cert_id AS id, domain_type AS name
            FROM ssl_certs
            WHERE status IN ('toadd', 'tochange', 'todelete') ORDER BY cert_id ASC
        "
    );

    # Process toadd|tochange users tasks
    $self->_processModuleDbTasks(
        'Modules::User',
        "
            SELECT admin_id AS id, admin_name AS name
            FROM admin
            WHERE admin_type = 'user'
            AND admin_status IN ('toadd', 'tochange', 'tochangepwd')
            ORDER BY admin_id ASC
        "
    );

    # Process toadd|tochange|torestore|toenable|todisable domain tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::Domain',
        "
            SELECT domain_id AS id, domain_name AS name
            FROM domain
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE domain_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
            AND admin_status IN('ok', 'disabled')
            ORDER BY domain_id ASC
        "
    );

    # Process toadd|tochange|torestore|toenable|todisable subdomains tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::Subdomain',
        "
            SELECT subdomain_id AS id, CONCAT(subdomain_name, '.', domain_name) AS name
            FROM subdomain
            JOIN domain USING(domain_id)
            WHERE subdomain_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
            AND domain_status IN('ok', 'disabled')
            ORDER BY subdomain_id ASC
        "
    );

    # Process toadd|tochange|torestore|toenable|todisable domain aliases tasks
    # (for each entitty, process only if the parent entity is in a consistent state)
    $self->_processModuleDbTasks(
        'Modules::Alias',
        "
           SELECT alias_id AS id, alias_name AS name
           FROM domain_aliasses
           JOIN domain USING(domain_id)
           WHERE alias_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
           AND domain_status IN('ok', 'disabled')
           ORDER BY alias_id ASC
        "
    );

    # Process toadd|tochange|torestore|toenable|todisable subdomains of domain aliases tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::SubAlias',
        "
            SELECT subdomain_alias_id AS id, CONCAT(subdomain_alias_name, '.', alias_name) AS name
            FROM subdomain_alias
            JOIN domain_aliasses USING(alias_id)
            WHERE subdomain_alias_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
            AND alias_status IN('ok', 'disabled')
            ORDER BY subdomain_alias_id ASC
        "
    );

    # Process toadd|tochange|toenable||todisable|todelete custom DNS records which belong to domains
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::CustomDNS',
        "
            SELECT DISTINCT CONCAT('domain_', domain_id) AS id, domain_name AS name
            FROM domain_dns
            JOIN domain USING(domain_id)
            WHERE domain_dns_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
            AND alias_id = '0'
            AND domain_status IN('ok', 'disabled')
        "
    );

    # Process toadd|tochange|toenable|todisable|todelete custom DNS records which belong to domain aliases
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::CustomDNS',
        "
            SELECT DISTINCT CONCAT('alias_', alias_id) AS id, alias_name AS name
            FROM domain_dns
            JOIN domain_aliasses USING(alias_id)
            WHERE domain_dns_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
            AND alias_id <> '0'
            AND alias_status IN('ok', 'disabled')
        "
    );

    # Process toadd|tochange|toenable|todisable|todelete ftp users tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::FtpUser',
        "
            SELECT userid AS id, userid AS name
            FROM ftp_users
            JOIN domain ON(domain_admin_id = admin_id)
            WHERE status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY userid ASC
        "
    );

    # Process toadd|tochange|toenable|todisable|todelete mail tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::Mail',
        "
            SELECT mail_id AS id, mail_addr AS name
            FROM mail_users
            JOIN domain USING(domain_id)
            WHERE status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY mail_id ASC
        "
    );

    # Process toadd|tochange|toenable|todisable|todelete Htusers tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::Htpasswd',
        "
            SELECT id, uname AS name
            FROM htaccess_users
            JOIN domain ON(domain_id = dmn_id)
            WHERE status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY id ASC
        "
    );

    # Process toadd|tochange|toenable|todisable|todelete Htgroups tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::Htgroup',
        "
            SELECT id, ugroup AS name
            FROM htaccess_groups
            JOIN domain ON(domain_id = dmn_id)
            WHERE status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY id ASC
        "
    );

    # Process toadd|tochange|toenable|todisable|todelete Htaccess tasks
    # For each entitty, process only if the parent entity is in a consistent state
    $self->_processModuleDbTasks(
        'Modules::Htaccess',
        "
            SELECT id, auth_name AS name
            FROM htaccess
            JOIN domain ON(domain_id = dmn_id)
            WHERE status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY id ASC
        "
    );

    # Process todelete subdomain aliases tasks
    $self->_processModuleDbTasks(
        'Modules::SubAlias',
        "
            SELECT subdomain_alias_id AS id, concat(subdomain_alias_name, '.', alias_name) AS name
            FROM subdomain_alias
            JOIN domain_aliasses USING(alias_id)
            WHERE subdomain_alias_status = 'todelete'
            ORDER BY subdomain_alias_id ASC
        "
    );

    # Process todelete domain aliases tasks
    # For each entity, process only if the entity do not have any direct children
    $self->_processModuleDbTasks(
        'Modules::Alias',
        "
            SELECT alias_id AS id, alias_name AS name
            FROM domain_aliasses
            LEFT JOIN (SELECT DISTINCT alias_id FROM subdomain_alias) AS subdomain_alias  USING(alias_id)
            WHERE alias_status = 'todelete'
            AND subdomain_alias.alias_id IS NULL
            ORDER BY alias_id ASC
        "
    );

    # Process todelete subdomains tasks
    $self->_processModuleDbTasks(
        'Modules::Subdomain',
        "
            SELECT subdomain_id AS id, CONCAT(subdomain_name, '.', domain_name) AS name
            FROM subdomain
            JOIN domain USING(domain_id)
            WHERE subdomain_status = 'todelete'
            ORDER BY subdomain_id ASC
        "
    );

    # Process todelete domains tasks
    # For each entity, process only if the entity do not have any direct children
    $self->_processModuleDbTasks(
        'Modules::Domain',
        "
            SELECT domain_id AS id, domain_name AS name
            FROM domain
            LEFT JOIN (SELECT DISTINCT domain_id FROM subdomain) as subdomain USING (domain_id)
            WHERE domain_status = 'todelete'
            AND subdomain.domain_id IS NULL
            ORDER BY domain_id ASC
        "
    );

    # Process todelete users tasks
    # For each entity, process only if the entity do not have any direct children
    $self->_processModuleDbTasks(
        'Modules::User',
        "
            SELECT admin_id AS id, admin_name AS name
            FROM admin
            LEFT JOIN domain ON(domain_admin_id = admin_id)
            WHERE admin_type = 'user'
            AND admin_status = 'todelete'
            AND domain_id IS NULL
            ORDER BY admin_id ASC
        "
    );

    # Process software package tasks
    local $self->{'_dbh'}->{'RaiseError'} = 1;

    my $rows = $self->{'_dbh'}->selectall_hashref(
        "
            SELECT domain_id, alias_id, subdomain_id, subdomain_alias_id, software_id, path, software_prefix,
                db, database_user, database_tmp_pwd, install_username, install_password, install_email,
                software_status, software_depot, software_master_id
            FROM web_software_inst
            WHERE software_status IN ('toadd', 'todelete')
            ORDER BY domain_id ASC
        ",
        'software_id'
    );

    if ( %{$rows} ) {
        newDebug( 'imscp_sw_mngr_engine' );

        for ( values %{$rows} ) {
            my $pushString = encode_base64(
                encode_json(
                    [
                        $_->{'domain_id'}, $_->{'software_id'}, $_->{'path'}, $_->{'software_prefix'}, $_->{'db'},
                        $_->{'database_user'}, $_->{'database_tmp_pwd'}, $_->{'install_username'},
                        $_->{'install_password'}, $_->{'install_email'}, $_->{'software_status'},
                        $_->{'software_depot'}, $_->{'software_master_id'}, $_->{'alias_id'},
                        $_->{'subdomain_id'}, $_->{'subdomain_alias_id'}
                    ]
                ),
                ''
            );

            my ($stdout, $stderr);
            execute(
                "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-sw-mngr " . escapeShell( $pushString ), \$stdout,
                \$stderr
            ) == 0 or die( $stderr || 'Unknown error' );
            debug( $stdout ) if $stdout;
            execute( "rm -fR /tmp/sw-$_->{'domain_id'}-$_->{'software_id'}", \$stdout, \$stderr ) == 0 or die(
                $stderr || 'Unknown error'
            );
            debug( $stdout ) if $stdout;
        }

        endDebug();
    }

    # Process software tasks
    $rows = $self->{'_dbh'}->selectall_hashref(
        "
            SELECT software_id, reseller_id, software_archive, software_status, software_depot
            FROM web_software
            WHERE software_status = 'toadd'
            ORDER BY reseller_id ASC
        ",
        'software_id'
    );

    if ( %{$rows} ) {
        newDebug( 'imscp_pkt_mngr_engine.log' );

        for ( values %{$rows} ) {
            my $pushstring = encode_base64(
                encode_json(
                    [
                        $_->{'software_id'}, $_->{'reseller_id'}, $_->{'software_archive'}, $_->{'software_status'},
                        $_->{'software_depot'}
                    ]
                ),
                ''
            );

            my ($stdout, $stderr);
            execute(
                "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-pkt-mngr " . escapeShell( $pushstring ), \$stdout,
                \$stderr
            ) == 0 or die( $stderr || 'Unknown error' );
            debug( $stdout ) if $stdout;
            execute( "rm -fR /tmp/sw-$_->{'software_archive'}-$_->{'software_id'}", \$stdout, \$stderr ) == 0 or die(
                $stderr || 'Unknown error'
            );
            debug( $stdout ) if $stdout;
        }

        endDebug();
    }
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::DbTasksProcessor or die on failure

=cut

sub _init
{
    my ($self) = @_;

    defined $self->{'mode'} or die( 'mode attribute is not defined' );
    $self->{'_dbh'} = iMSCP::Database->factory()->getRawDb();
    $self;
}

=item _processModuleDbTasks( $module, $sql [, $perItemLogFile = FALSE ] )

 Process db tasks from the given module

 Param string $module Module name to process
 Param string $sql SQL statement for retrieval of list of items to process by the given module
 Param bool $perItemLogFile Enable per item log file (default is per module log file)
 Return int 1 if at least one item has been processed, 0 if no item has been processed, die on failure

=cut

sub _processModuleDbTasks
{
    my ($self, $module, $sql, $perItemLogFile) = @_;

    eval {
        debug( sprintf( 'Processing %s tasks...', $module ), ( caller( 2 ) )[3] );

        local $self->{'_dbh'}->{'RaiseError'} = 1;

        my $sth = $self->{'_dbh'}->prepare( $sql );
        $sth->execute();

        my $countRows = $sth->rows();

        unless ( $countRows ) {
            debug( sprintf( 'No task to process for %s', $module ), ( caller( 2 ) )[3] );
            return 0;
        }

        eval "require $module" or die;

        my ($nStep, $rs) = ( 0, 0 );
        my $needStepper = grep( $self->{'mode'} eq $_, ( 'setup', 'uninstall' ) );

        while ( my $row = $sth->fetchrow_hashref() ) {
            my $name = encode_utf8( $row->{'name'} );

            debug( sprintf( 'Processing %s tasks for: %s (ID %s)', $module, $name, $row->{'id'} ), ( caller( 2 ) )[3] );
            newDebug( $module . ( ( $perItemLogFile ) ? "_${name}" : '' ) . '.log' );

            if ( $needStepper ) {
                $rs = step(
                    sub { $self->_processModuleTasks( $module, $row->{'id'} ); },
                    sprintf( 'Processing %s tasks for: %s (ID %s)', $module, $name, $row->{'id'} ),
                    $countRows,
                    ++$nStep
                );
            } else {
                $rs = $self->_processModuleTasks( $module, $row->{'id'} );
            }

            $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            endDebug();
        }
    };
    if ( $@ ) {
        endDebug();
        die;
    }

    1;
}

=item _processModuleTasks ( $module, $dbItemId )

 Process module tasks for the given db item

 Param string $module Module name
 Param int $dbItemId Database item unique identifier
 Return int 0 on success, other or die on failure

=cut

sub _processModuleTasks
{
    my ($self, $module, $dbItemId) = @_;

    # Only for backward compatibility with 3rd-party software.
    # Will be removed when RaiseError will be default in version 1.5.0
    local $self->{'_dbh'}->{'RaiseError'} = 0;

    $module->new()->process( $dbItemId );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
