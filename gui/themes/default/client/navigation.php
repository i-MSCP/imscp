<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

return array(
	'general' => array(
		'label' => tr('General'),
		'uri' => '/client/index.php',
		'class' => 'general',
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/client/index.php',
				'title_class' => 'general'
			)
		)
	),
	'domains' => array(
		'label' => tr('Domains'),
		'uri' => '/client/domains_manage.php',
		'class' => 'domains',
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/client/domains_manage.php',
				'title_class' => 'domains',
				'pages' => array(
					'domain_alias_edit_link' => array(
						'label' => tr('Edit domain alias'),
						'uri' => '/client/alias_edit.php',
						'title_class' => 'domains',
						'visible' => '0'
					),
					'subdomain_edit_link' => array(
						'label' => tr('Edit subdomain'),
						'uri' => '/client/subdomain_edit.php',
						'title_class' => 'domains',
						'visible' => '0'
					),
					'custom_dns_record_edit_link' => array(
						'label' => tr('Edit custom DNS record'),
						'uri' => '/client/dns_edit.php',
						'title_class' => 'domains',
						'visible' => '0'
					),
					'cert_view_link' => array(
						'dynamic_title' => '{TR_DYNAMIC_TITLE}',
						'uri' => '/client/cert_view.php',
						'title_class' => 'domains',
						'visible' => '0'
					)
				)
			),
			'add_domain_alias' => array(
				'label' => tr('Add domain alias'),
				'uri' => '/client/alias_add.php',
				'title_class' => 'domains',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'domain_aliases'
				)
			),
			'add_subdomain' => array(
				'label' => tr('Add subdomain'),
				'uri' => '/client/subdomain_add.php',
				'title_class' => 'domains',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'subdomains'
				)
			),
			'add_custom_dns_record' => array(
				'label' => tr('Add custom DNS record'),
				'uri' => '/client/dns_add.php',
				'title_class' => 'domains',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'custom_dns_records'
				)
			),
			'php_settings' => array(
				'label' => tr('PHP settings'),
				'uri' => '/client/phpini.php',
				'title_class' => 'domains',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'php_editor'
				)
			)
		)
	),
	'ftp' => array(
		'label' => tr('FTP'),
		'uri' => '/client/ftp_accounts.php',
		'class' => 'ftp',
		'privilege_callback' => array(
			'name' => 'customerHasFeature',
			'param' => 'ftp'
		),
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/client/ftp_accounts.php',
				'title_class' => 'ftp',
				'pages' => array(
					'ftp_account_edit_link' => array(
						'label' => tr('Edit FTP account'),
						'uri' => '/client/ftp_edit.php',
						'visible' => '0',
						'title_class' => 'ftp'
					)
				)
			),
			'add_ftp_account' => array(
				'label' => tr('Add FTP account'),
				'uri' => '/client/ftp_add.php',
				'title_class' => 'ftp'
			),
			'file_manager' => array(
				'label' => tr('Filemanager'),
				'uri' => '/ftp/',
				'target' => '_blank'
			)
		)
	),
	'databases' => array(
		'label' => tr('Databases'),
		'uri' => '/client/sql_manage.php',
		'class' => 'database',
		'privilege_callback' => array(
			'name' => 'customerHasFeature',
			'param' => 'sql'
		),
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/client/sql_manage.php',
				'title_class' => 'sql',
				'pages' => array(
					'add_sql_user_link' => array(
						'label' => tr('Add SQL user'),
						'uri' => '/client/sql_user_add.php',
						'visible' => '0',
						'title_class' => 'user'
					),
					'update_sql_user_password_link' => array(
						'label' => tr('Update SQL user password'),
						'uri' => '/client/sql_change_password.php',
						'visible' => '0',
						'title_class' => 'password'
					)
				)
			),
			'add_sql_database' => array(
				'label' => tr('Add SQL database'),
				'uri' => '/client/sql_database_add.php',
				'title_class' => 'sql'
			),
			'phpmyadmin' => array(
				'label' => tr('phpMyAdmin'),
				'uri' => '/pma/',
				'target' => '_blank'
			)
		)
	),
	'mail' => array(
		'label' => tr('Mail'),
		'uri' => '/client/mail_accounts.php',
		'class' => 'email',
		'privilege_callback' => array(
			'name' => 'customerHasMailOrExtMailFeatures'
		),
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/client/mail_accounts.php',
				'title_class' => 'email',
				'pages' => array(
					'mail_account_edit_link' => array(
						'label' => tr('Edit mail account'),
						'uri' => '/client/mail_edit.php',
						'visible' => '0',
						'title_class' => 'email'
					),
					'mail_account_quota_link' => array(
						'label' => tr('Edit mail quota'),
						'uri' => '/client/mail_quota.php',
						'visible' => '0',
						'title_class' => 'email'
					),
					'enable_autoresponder_link' => array(
						'label' => tr('Enable auto responder'),
						'uri' => '/client/mail_autoresponder_enable.php',
						'visible' => '0',
						'title_class' => 'email'
					),
					'edit_autoresponder_link' => array(
						'label' => tr('Edit auto responder'),
						'uri' => '/client/mail_autoresponder_edit.php',
						'visible' => '0',
						'title_class' => 'email'
					)
				)
			),
			'add_email_account' => array(
				'label' => tr('Add email account'),
				'uri' => '/client/mail_add.php',
				'title_class' => 'email',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'mail'
				),
			),
			'catchall' => array(
				'label' => tr('Catchall'),
				'uri' => '/client/mail_catchall.php',
				'title_class' => 'email',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'mail'
				),
				'pages' => array(
					'add_catchall_link' => array(
						'label' => tr('Add catchall'),
						'uri' => '/client/mail_catchall_add.php',
						'visible' => '0',
						'title_class' => 'email'
					)
				)
			),
			'external_mail_servers' => array(
				'label' => tr('External mail servers'),
				'uri' => '/client/mail_external.php',
				'title_class' => 'email',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'external_mail'
				),
				'pages' => array(
					'add_ext_mail_server_link' => array(
						'label' => tr('Add external mail server for {DOMAIN_UTF8}'),
						'uri' => '/client/mail_external_add.php',
						'visible' => '0',
						'title_class' => 'email'
					),
					'edit_ext_mail_server_link' => array(
						'label' => tr('Edit external mail server for {DOMAIN_UTF8}'),
						'uri' => '/client/mail_external_edit.php',
						'visible' => '0',
						'title_class' => 'email'
					)
				)
			)
		)
	),
	'statistics' => array(
		'label' => tr('Statistics'),
		'uri' => '/client/traffic_statistics.php',
		'class' => 'statistics',
		'pages' => array(
			'overview' => array(
				'label' => tr('Traffic statistics'),
				'uri' => '/client/traffic_statistics.php',
				'title_class' => 'stats'
			),
			'webstats' => array(
				'label' => tr('Web statistics'),
				'uri' => '{WEBSTATS_PATH}',
				'target' => '_blank',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'webstats'
				)
			)
		)
	),
	'webtools' => array(
		'label' => tr('Webtools'),
		'uri' => '/client/webtools.php',
		'class' => 'webtools',
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/client/webtools.php',
				'title_class' => 'tools'
			),
			'protected_areas' => array(
				'label' => tr('Protected areas'),
				'uri' => '/client/protected_areas.php',
				'title_class' => 'htaccess',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'protected_areas'
				),
				'pages' => array(
					'add_protected_area_link' => array(
						'dynamic_title' => '{TR_DYNAMIC_TITLE}',
						'uri' => '/client/protected_areas_add.php',
						'title_class' => 'htaccess',
						'visible' => '0'
					),
					'manage_htaccess_users_and_groups_link' => array(
						'label' => tr('Manage htaccess users and groups'),
						'uri' => '/client/protected_user_manage.php',
						'title_class' => 'users',
						'visible' => '0',
						'pages' => array(
							'assign_htaccess_group_link' => array(
								'label' => tr('Assign group'),
								'uri' => '/client/protected_user_assign.php',
								'title_class' => 'users',
								'visible' => '0'
							),
							'edit_htaccess_user_link' => array(
								'label' => tr('Edit htaccess user'),
								'uri' => '/client/protected_user_edit.php',
								'title_class' => 'users',
								'visible' => '0'
							),
							'add_htaccess_user_link' => array(
								'label' => tr('Add Htaccess user'),
								'uri' => '/client/protected_user_add.php',
								'title_class' => 'users',
								'visible' => '0'
							),
							'add_htaccess_group_link' => array(
								'label' => tr('Add Htaccess group'),
								'uri' => '/client/protected_group_add.php',
								'title_class' => 'users',
								'visible' => '0'
							)
						)
					)
				)
			),
			'custom_error_pages' => array(
				'label' => tr('Custom error pages'),
				'uri' => '/client/error_pages.php',
				'title_class' => 'errors',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'custom_error_pages'
				),
				'pages' => array(
					'custom_error_page_edit_link' => array(
						'label' => tr('Edit custom error page'),
						'uri' => '/client/error_edit.php',
						'visible' => '0',
						'title_class' => 'errors'
					),
				),
			),
			'daily_backup' => array(
				'label' => tr('Daily backup'),
				'uri' => '/client/backup.php',
				'title_class' => 'hdd',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'backup'
				),
			),
			'file_manager' => array(
				'label' => tr('Filemanager'),
				'uri' => '/ftp/',
				'target' => '_blank',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'ftp'
				),
			),
			'phpmyadmin' => array(
				'label' => tr('PhpMyAdmin'),
				'uri' => '/pma/',
				'target' => '_blank',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'sql'
				)
			),
			'webstats' => array(
				'label' => tr('Web statistics'),
				'uri' => '{WEBSTATS_PATH}',
				'target' => '_blank',
				'privilege_callback' => array(
					'name' => 'customerHasFeature',
					'param' => 'webstats'
				)
			)
		)
	),
	'support' => array(
		'label' => tr('Support'),
		'uri' => '{SUPPORT_SYSTEM_PATH}',
		'target' => '{SUPPORT_SYSTEM_TARGET}',
		'class' => 'support',
		'privilege_callback' => array(
			'name' => 'customerHasFeature',
			'param' => 'support'
		),
		'pages' => array(
			'tickets_open' => array(
				'label' => tr('Open tickets'),
				'uri' => '/client/ticket_system.php',
				'title_class' => 'support'
			),
			'tickets_closed' => array(
				'label' => tr('Closed tickets'),
				'uri' => '/client/ticket_closed.php',
				'title_class' => 'support'
			),
			'new_ticket' => array(
				'label' => tr('New ticket'),
				'uri' => '/client/ticket_create.php',
				'title_class' => 'support'
			),
			'view_ticket' => array(
				'label' => tr('View ticket'),
				'uri' => '/client/ticket_view.php',
				'title_class' => 'support',
				'visible' => '0'
			)
		)
	),
	'profile' => array(
		'label' => tr('Profile'),
		'uri' => '/client/profile.php',
		'class' => 'profile',
		'pages' => array(
			'overview' => array(
				'label' => tr('Account summary'),
				'uri' => '/client/profile.php',
				'title_class' => 'profile'
			),
			'personal_data' => array(
				'label' => tr('Personal data'),
				'uri' => '/client/personal_change.php',
				'title_class' => 'profile'
			),
			'passsword' => array(
				'label' => tr('Password'),
				'uri' => '/client/password_update.php',
				'title_class' => 'profile'
			),
			'language' => array(
				'label' => tr('Language'),
				'uri' => '/client/language.php',
				'title_class' => 'multilanguage'
			),
			'layout' => array(
				'label' => tr('Layout'),
				'uri' => '/client/layout.php',
				'title_class' => 'layout'
			)
		)
	)
);
