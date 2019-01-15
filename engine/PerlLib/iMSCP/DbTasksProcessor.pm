=head1 NAME

 iMSCP::DbTasksProcessor - i-MSCP database tasks processor

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
use warnings;
use Encode 'encode_utf8';
use iMSCP::Boolean;
use iMSCP::Database;
use iMSCP::Debug qw/ debug getMessageByType newDebug endDebug /;
use iMSCP::Execute 'execute';
use iMSCP::Stepper 'step';
use JSON;
use MIME::Base64 qw/ encode_base64 /;
use Try::Tiny;
use parent 'Common::SingletonClass';

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
    my ( $self ) = @_;

    # Process plugins tasks
    # Must always be processed first to allow the plugins registering their listeners on the event manager
    $self->_process(
        'Modules::Plugin',
        "
            SELECT plugin_id AS id, plugin_name AS name
            FROM plugin
            WHERE plugin_status IN ('enabled', 'toinstall', 'toenable', 'toupdate', 'tochange', 'todisable', 'touninstall')
            AND plugin_error IS NULL AND plugin_backend = 'yes'
            ORDER BY plugin_priority DESC
        ",
        TRUE
    );

    # Process server IP addresses
    $self->_process(
        'Modules::ServerIP',
        "
            SELECT ip_id AS id, ip_number AS name
            FROM server_ips
            WHERE ip_status IN( 'toadd', 'tochange', 'todelete' )
        ",
        FALSE
    );

    # Process SSL certificate toadd|tochange SSL certificates tasks
    $self->_process(
        'Modules::SSLcertificate',
        "
            SELECT cert_id AS id, domain_type AS name
            FROM ssl_certs
            WHERE status IN ('toadd', 'tochange', 'todelete')
            ORDER BY cert_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange users tasks
    $self->_process(
        'Modules::User',
        "
            SELECT admin_id AS id, admin_name AS name FROM admin
            WHERE admin_type = 'user' AND admin_status IN ('toadd', 'tochange', 'tochangepwd')
            ORDER BY admin_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|torestore|toenable|todisable domain tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Domain',
        "
            SELECT t1.domain_id AS id, t1.domain_name AS name
            FROM domain AS t1
            JOIN admin AS t2 ON(admin_id = domain_admin_id)
            WHERE t1.domain_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
            AND t2.admin_status IN('ok', 'disabled')
            ORDER BY t1.domain_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|torestore|toenable|todisable subdomains tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Subdomain',
        "
            SELECT t1.subdomain_id AS id, CONCAT(t1.subdomain_name, '.', t2.domain_name) AS name
            FROM subdomain AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.subdomain_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
            AND t2.domain_status IN('ok', 'disabled')
            ORDER BY t1.subdomain_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|torestore|toenable|todisable domain aliases tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Alias',
        "
           SELECT t1.alias_id AS id, t1.alias_name AS name
           FROM domain_aliasses AS t1
           JOIN domain AS t2 USING(domain_id)
           WHERE t1.alias_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
           AND t2.domain_status IN('ok', 'disabled')
           ORDER BY t1.alias_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|torestore|toenable|todisable subdomains of domain aliases tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::SubAlias',
        "
            SELECT t1.subdomain_alias_id AS id, CONCAT(t1.subdomain_alias_name, '.', t2.alias_name) AS name
            FROM subdomain_alias AS t1
            JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE t1.subdomain_alias_status IN ('toadd', 'tochange', 'torestore', 'toenable', 'todisable')
            AND t2.alias_status IN('ok', 'disabled')
            ORDER BY t1.subdomain_alias_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|toenable||todisable|todelete custom DNS records which belong to domains
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::CustomDNS',
        "
            SELECT t1.domain_id AS id, 'domain' AS type, t2.domain_name AS name
            FROM domain_dns AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.domain_dns_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
            AND t1.alias_id = 0
            AND t2.domain_status IN('ok', 'disabled')
            LIMIT 1
        ",
        FALSE
    );

    # Process toadd|tochange|toenable|todisable|todelete custom DNS records which belong to domain aliases
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::CustomDNS',
        "
            SELECT t1.alias_id AS id, 'alias' AS type, t2.alias_name AS name
            FROM domain_dns AS t1
            JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE t1.domain_dns_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
            AND t1.alias_id <> 0
            AND t2.alias_status IN('ok', 'disabled')
            LIMIT 1
        ",
        FALSE
    );

    # Process toadd|tochange|toenable|todisable|todelete ftp users tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::FtpUser',
        "
            SELECT t1.userid AS id, t1.userid AS name
            FROM ftp_users AS t1
            JOIN domain AS t2 ON(domain_admin_id = admin_id)
            WHERE t1.status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND t2.domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY t1.userid ASC
        ",
        FALSE
    );

    # Process toadd|tochange|toenable|todisable|todelete mail tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Mail',
        "
            SELECT t1.mail_id AS id, t1.mail_addr AS name
            FROM mail_users AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND t2.domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY t1.mail_id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|toenable|todisable|todelete Htpasswd tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Htpasswd',
        "
            SELECT t1.id, t1.uname AS name
            FROM htaccess_users AS t1
            JOIN domain AS t2 ON(domain_id = dmn_id)
            WHERE t1.status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND t2.domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY t1.id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|toenable|todisable|todelete Htgroup tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Htgroup',
        "
            SELECT t1.id, t1.ugroup AS name
            FROM htaccess_groups AS t1
            JOIN domain AS t2 ON(domain_id = dmn_id)
            WHERE t1.status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND t2.domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY t1.id ASC
        ",
        FALSE
    );

    # Process toadd|tochange|toenable|todisable|todelete Htaccess tasks
    # Process only if parent aggregate is in expected state
    $self->_process(
        'Modules::Htaccess',
        "
            SELECT t1.id, t1.auth_name AS name
            FROM htaccess AS t1
            JOIN domain AS t2 ON(domain_id = dmn_id)
            WHERE t1.status IN ('toadd', 'tochange', 'toenable', 'todelete', 'todisable')
            AND t2.domain_status IN('ok', 'todelete', 'disabled')
            ORDER BY t1.id ASC
        ",
        FALSE
    );

    # Process todelete subdomain aliases tasks
    $self->_process(
        'Modules::SubAlias',
        "
            SELECT t1.subdomain_alias_id AS id, concat(t1.subdomain_alias_name, '.', t2.alias_name) AS name
            FROM subdomain_alias AS t1
            JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE t1.subdomain_alias_status = 'todelete'
            ORDER BY t1.subdomain_alias_id ASC
        ",
        FALSE
    );

    # Process todelete domain aliases tasks
    # Process only if that aggregate has no subdomains
    $self->_process(
        'Modules::Alias',
        "
            SELECT t1.alias_id AS id, t1.alias_name AS name
            FROM domain_aliasses AS t1
            LEFT JOIN (SELECT DISTINCT alias_id FROM subdomain_alias) AS t2 USING(alias_id)
            WHERE t1.alias_status = 'todelete'
            AND t2.alias_id IS NULL
            ORDER BY t1.alias_id ASC
        ",
        FALSE
    );

    # Process todelete subdomains tasks
    $self->_process(
        'Modules::Subdomain',
        "
            SELECT t1.subdomain_id AS id, CONCAT(t1.subdomain_name, '.', t2.domain_name) AS name
            FROM subdomain AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.subdomain_status = 'todelete'
            ORDER BY t1.subdomain_id ASC
        ",
        FALSE
    );

    # Process todelete domains tasks
    # Process only if parent aggregate is in expected state
    # Process only if that aggregate is empty
    $self->_process(
        'Modules::Domain',
        "
            SELECT t1.domain_id AS id, t1.domain_name AS name
            FROM domain AS t1
            LEFT JOIN (SELECT DISTINCT domain_id FROM subdomain) as t2 USING (domain_id)
            WHERE t1.domain_status = 'todelete'
            AND t2.domain_id IS NULL
            ORDER BY t1.domain_id ASC
        ",
        FALSE
    );

    # Process todelete users tasks
    # Process only if that aggregate is empty
    $self->_process(
        'Modules::User',
        "
            SELECT t1.admin_id AS id, t1.admin_name AS name
            FROM admin AS t1
            LEFT JOIN domain t2 ON(domain_admin_id = admin_id)
            WHERE t1.admin_type = 'user'
            AND t1.admin_status = 'todelete'
            AND t2.domain_id IS NULL
            ORDER BY t1.admin_id ASC
        ",
        FALSE
    );

    # Process software packages tasks

    my $rows = $self->{'_conn'}->run( fixup => sub {
        $_->selectall_hashref(
            "
                SELECT domain_id, alias_id, subdomain_id, subdomain_alias_id, software_id, path, software_prefix, db, database_user, database_tmp_pwd,
                    install_username, install_password, install_email, software_status, software_depot, software_master_id
                FROM web_software_inst
                WHERE software_status IN ('toadd', 'todelete')
                ORDER BY domain_id ASC
            ",
            'software_id'
        );
    } );

    if ( %{ $rows } ) {
        newDebug( 'imscp_sw_mngr_engine' );

        for ( values %{ $rows } ) {
            my $pushString = encode_base64(
                encode_json( [
                    $_->{'domain_id'}, $_->{'software_id'}, $_->{'path'}, $_->{'software_prefix'}, $_->{'db'},
                    $_->{'database_user'}, $_->{'database_tmp_pwd'}, $_->{'install_username'},
                    $_->{'install_password'}, $_->{'install_email'}, $_->{'software_status'},
                    $_->{'software_depot'}, $_->{'software_master_id'}, $_->{'alias_id'},
                    $_->{'subdomain_id'}, $_->{'subdomain_alias_id'}
                ] ),
                ''
            );

            my ( $stdout, $stderr );
            execute( [  "$::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-sw-mngr", $pushString ], \$stdout, \$stderr ) == 0 or die(
                $stderr || 'Unknown error'
            );
            debug( $stdout ) if $stdout;
            execute( "rm -fR /tmp/sw-$_->{'domain_id'}-$_->{'software_id'}", \$stdout, \$stderr ) == 0 or die( $stderr || 'Unknown error' );
            debug( $stdout ) if $stdout;
        }

        endDebug();
    }

    # Process software tasks
    $rows = $self->{'_conn'}->run( fixup => sub {
        $_->selectall_hashref(
            "
                SELECT software_id, reseller_id, software_archive, software_status, software_depot
                FROM web_software
                WHERE software_status = 'toadd'
                ORDER BY reseller_id ASC
            ",
            'software_id'
        );
    } );

    if ( %{ $rows } ) {
        newDebug( 'imscp_pkt_mngr_engine.log' );

        for ( values %{ $rows } ) {
            my $pushString = encode_base64(
                encode_json( [ $_->{'software_id'}, $_->{'reseller_id'}, $_->{'software_archive'}, $_->{'software_status'}, $_->{'software_depot'} ] ),
                ''
            );

            my ( $stdout, $stderr );
            execute( [ "$::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-pkt-mngr", $pushString ], \$stdout, \$stderr ) == 0 or die(
                $stderr || 'Unknown error'
            );
            debug( $stdout ) if $stdout;
            execute( "rm -fR /tmp/sw-$_->{'software_archive'}-$_->{'software_id'}", \$stdout, \$stderr ) == 0 or die( $stderr || 'Unknown error' );
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
    my ( $self ) = @_;

    $self->{'_conn'} = iMSCP::Database->factory()->getConnector();
    $self;
}

=item _process( $module, $sql [, $perItemLogFile = FALSE ] )

 Process the given module tasks

 Param string $module Module name to process
 Param string $sql SQL statement for retrieval of list of items to process by the given module
 Param bool $perItemLogFile Flag indicating whether a log file must be created for each item (default is per module log file)
 Return void, die on failure

=cut

sub _process
{
    my ( $self, $module, $sql, $perItemLogFile ) = @_;
    
    debug( sprintf( 'Processing %s tasks...', $module ));

    my $sth = $self->{'_conn'}->run( fixup => sub {
        my $sth = $_->prepare( $sql );
        $sth->execute();
        $sth;
    } );

    my $countRows = $sth->rows();
    unless ( $countRows ) {
        debug( sprintf( 'No task to process for %s', $module ));
        return;
    }

    eval "require $module" or die $_;
    my $nStep = 0;
    my $needStepper = grep ( $::execmode eq $_, ( 'setup', 'uninstaller' ) );
    while ( my $row = $sth->fetchrow_hashref() ) {
        try {
            my $name = encode_utf8( $row->{'name'} );
            debug( sprintf( 'Processing %s tasks for: %s (ID %s)', $module, $name, $row->{'id'} ));
            newDebug( $module . ( $perItemLogFile ? "_${name}" : '' ) . '.log' );
            ( $needStepper
                ? step(
                    sub { $module->new()->process( $row ); },
                    sprintf( 'Processing %s tasks for: %s (ID %s)', $module, $name, $row->{'id'} ), $countRows, ++$nStep
                )
                : $module->new()->process( $row )
            ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' );
        } catch {
            die $_;
        } finally {
            endDebug();
        };
    }
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
