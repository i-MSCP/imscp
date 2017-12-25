<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Registry as Registry;

return [
    'general'    => [
        'label' => tr('General'),
        'uri'   => '/client/index.php',
        'class' => 'general',
        'pages' => [
            'overview' => [
                'label'       => tr('Overview'),
                'uri'         => '/client/index.php',
                'title_class' => 'general'
            ]
        ]
    ],
    'domains'    => [
        'label' => tr('Domains'),
        'uri'   => '/client/domains_manage.php',
        'class' => 'domains',
        'pages' => [
            'overview'              => [
                'label'       => tr('Overview'),
                'uri'         => '/client/domains_manage.php',
                'title_class' => 'domains',
                'pages'       => [
                    'domain_edit'            => [
                        'label'       => tr('Edit domain'),
                        'uri'         => '/client/domain_edit.php',
                        'title_class' => 'domains',
                        'visible'     => '0'
                    ],
                    'domain_alias_edit'      => [
                        'label'       => tr('Edit domain alias'),
                        'uri'         => '/client/alias_edit.php',
                        'title_class' => 'domains',
                        'visible'     => '0'
                    ],
                    'subdomain_edit'         => [
                        'label'       => tr('Edit subdomain'),
                        'uri'         => '/client/subdomain_edit.php',
                        'title_class' => 'domains',
                        'visible'     => '0'
                    ],
                    'custom_dns_record_edit' => [
                        'label'       => tr('Edit DNS resource record'),
                        'uri'         => '/client/dns_edit.php',
                        'title_class' => 'domains',
                        'visible'     => '0'
                    ],
                    'cert_view'              => [
                        'dynamic_title' => '{TR_DYNAMIC_TITLE}',
                        'uri'           => '/client/cert_view.php',
                        'title_class'   => 'domains',
                        'visible'       => '0'
                    ]
                ]
            ],
            'add_domain_alias'      => [
                'label'              => tr('Add domain alias'),
                'uri'                => '/client/alias_add.php',
                'title_class'        => 'domains',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'domain_aliases'
                ]
            ],
            'add_subdomain'         => [
                'label'              => tr('Add subdomain'),
                'uri'                => '/client/subdomain_add.php',
                'title_class'        => 'domains',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'subdomains'
                ]
            ],
            'add_custom_dns_record' => [
                'label'              => tr('Add DNS resource record'),
                'uri'                => '/client/dns_add.php',
                'title_class'        => 'domains',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'custom_dns_records'
                ]
            ],
            'php_settings'          => [
                'label'              => tr('PHP settings'),
                'uri'                => '/client/phpini.php',
                'title_class'        => 'domains',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'php_editor'
                ]
            ]
        ]
    ],
    'ftp'        => [
        'label'              => tr('Ftp'),
        'uri'                => '/client/ftp_accounts.php',
        'class'              => 'ftp',
        'privilege_callback' => [
            'name'  => 'customerHasFeature',
            'param' => 'ftp'
        ],
        'pages'              => [
            'overview'        => [
                'label'       => tr('Overview'),
                'uri'         => '/client/ftp_accounts.php',
                'title_class' => 'ftp',
                'pages'       => [
                    'ftp_account_edit' => [
                        'label'       => tr('Edit FTP account'),
                        'uri'         => '/client/ftp_edit.php',
                        'visible'     => '0',
                        'title_class' => 'ftp'
                    ]
                ]
            ],
            'add_ftp_account' => [
                'label'       => tr('Add FTP account'),
                'uri'         => '/client/ftp_add.php',
                'title_class' => 'ftp'
            ],
            'file_manager'    => [
                'label'  => tr('FileManager'),
                'uri'    => '/ftp/',
                'target' => '_blank'
            ]
        ]
    ],
    'databases'  => [
        'label'              => tr('Databases'),
        'uri'                => '/client/sql_manage.php',
        'class'              => 'database',
        'privilege_callback' => [
            'name'  => 'customerHasFeature',
            'param' => 'sql'
        ],
        'pages'              => [
            'overview'         => [
                'label'       => tr('Overview'),
                'uri'         => '/client/sql_manage.php',
                'title_class' => 'sql',
                'pages'       => [
                    'add_sql_user'             => [
                        'label'       => tr('Add SQL user'),
                        'uri'         => '/client/sql_user_add.php',
                        'visible'     => '0',
                        'title_class' => 'user'
                    ],
                    'update_sql_user_password' => [
                        'label'       => tr('Update SQL user password'),
                        'uri'         => '/client/sql_change_password.php',
                        'visible'     => '0',
                        'title_class' => 'password'
                    ]
                ]
            ],
            'add_sql_database' => [
                'label'              => tr('Add SQL database'),
                'uri'                => '/client/sql_database_add.php',
                'title_class'        => 'sql',
                'privilege_callback' => [
                    'name' => function () {
                        if (customerSqlDbLimitIsReached()) {
                            if(Registry::get('navigation')->findOneBy('uri', '/client/sql_manage.php')->isActive()) {
                                set_page_message(
                                    tr("SQL databases limit is reached. You cannot add new SQL databases."),
                                    'static_info'
                                );
                            }

                            return false;
                        }

                        return true;
                    }
                ]
            ],
            'phpmyadmin'       => [
                'label'  => tr('PhpMyAdmin'),
                'uri'    => '/pma/',
                'target' => '_blank'
            ]
        ]
    ],
    'mail'       => [
        'label'              => tr('Mail'),
        'uri'                => '/client/mail_accounts.php',
        'class'              => 'email',
        'privilege_callback' => [
            'name' => 'customerHasMailOrExtMailFeatures'
        ],
        'pages'              => [
            'overview'              => [
                'label'       => tr('Overview'),
                'uri'         => '/client/mail_accounts.php',
                'title_class' => 'email',
                'pages'       => [
                    'mail_account_edit'    => [
                        'label'       => tr('Edit mail account'),
                        'uri'         => '/client/mail_edit.php',
                        'visible'     => '0',
                        'title_class' => 'email'
                    ],
                    'enable_autoresponder' => [
                        'label'       => tr('Activate autoresponder'),
                        'uri'         => '/client/mail_autoresponder_enable.php',
                        'visible'     => '0',
                        'title_class' => 'email'
                    ],
                    'edit_autoresponder'   => [
                        'label'       => tr('Edit autoresponder'),
                        'uri'         => '/client/mail_autoresponder_edit.php',
                        'visible'     => '0',
                        'title_class' => 'email'
                    ]
                ]
            ],
            'add_email_account'     => [
                'label'              => tr('Add mail account'),
                'uri'                => '/client/mail_add.php',
                'title_class'        => 'email',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'mail'
                ],
            ],
            'catchall'              => [
                'label'              => tr('Catch-all accounts'),
                'uri'                => '/client/mail_catchall.php',
                'title_class'        => 'email',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'mail'
                ],
                'pages'              => [
                    'add_catchall' => [
                        'label'       => tr('Add catch-all account'),
                        'uri'         => '/client/mail_catchall_add.php',
                        'visible'     => '0',
                        'title_class' => 'email'
                    ]
                ]
            ],
            'external_mail_servers' => [
                'label'              => tr('External mail feature'),
                'uri'                => '/client/mail_external.php',
                'title_class'        => 'email',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'external_mail'
                ]
            ]
        ]
    ],
    'statistics' => [
        'label' => tr('Statistics'),
        'uri'   => '/client/traffic_statistics.php',
        'class' => 'statistics',
        'pages' => [
            'overview' => [
                'label'       => tr('Traffic statistics'),
                'uri'         => '/client/traffic_statistics.php',
                'title_class' => 'stats'
            ],
            'webstats' => [
                'label'              => tr('Web statistics'),
                'uri'                => '{WEBSTATS_PATH}',
                'target'             => '_blank',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'webstats'
                ]
            ]
        ]
    ],
    'webtools'   => [
        'label' => tr('Webtools'),
        'uri'   => '/client/webtools.php',
        'class' => 'webtools',
        'pages' => [
            'overview'           => [
                'label'       => tr('Overview'),
                'uri'         => '/client/webtools.php',
                'title_class' => 'tools'
            ],
            'protected_areas'    => [
                'label'              => tr('Protected areas'),
                'uri'                => '/client/protected_areas.php',
                'title_class'        => 'htaccess',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'protected_areas'
                ],
                'pages'              => [
                    'add_protected_area'               => [
                        'dynamic_title' => '{TR_DYNAMIC_TITLE}',
                        'uri'           => '/client/protected_areas_add.php',
                        'title_class'   => 'htaccess',
                        'visible'       => '0'
                    ],
                    'manage_htaccess_users_and_groups' => [
                        'label'       => tr('Manage htaccess users and groups'),
                        'uri'         => '/client/protected_user_manage.php',
                        'title_class' => 'users',
                        'visible'     => '0',
                        'pages'       => [
                            'assign_htaccess_group' => [
                                'label'       => tr('Assign group'),
                                'uri'         => '/client/protected_user_assign.php',
                                'title_class' => 'users',
                                'visible'     => '0'
                            ],
                            'edit_htaccess_user'    => [
                                'label'       => tr('Edit htaccess user'),
                                'uri'         => '/client/protected_user_edit.php',
                                'title_class' => 'users',
                                'visible'     => '0'
                            ],
                            'add_htaccess_user'     => [
                                'label'       => tr('Add Htaccess user'),
                                'uri'         => '/client/protected_user_add.php',
                                'title_class' => 'users',
                                'visible'     => '0'
                            ],
                            'add_htaccess_group'    => [
                                'label'       => tr('Add Htaccess group'),
                                'uri'         => '/client/protected_group_add.php',
                                'title_class' => 'users',
                                'visible'     => '0'
                            ]
                        ]
                    ]
                ]
            ],
            'custom_error_pages' => [
                'label'              => tr('Custom error pages'),
                'uri'                => '/client/error_pages.php',
                'title_class'        => 'errors',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'custom_error_pages'
                ],
                'pages'              => [
                    'custom_error_page_edit' => [
                        'label'       => tr('Edit custom error page'),
                        'uri'         => '/client/error_edit.php',
                        'visible'     => '0',
                        'title_class' => 'errors'
                    ],
                ],
            ],
            'softwares'          => [
                'label'              => tr('Software'),
                'uri'                => '/client/software.php',
                'title_class'        => 'apps_installer',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'aps'
                ],
                'pages'              => [
                    'software_view'    => [
                        'label'       => tr('Software details'),
                        'uri'         => '/client/software_view.php',
                        'title_class' => 'apps_installer',
                        'visible'     => '0'
                    ],
                    'software_install' => [
                        'label'       => tr('Software installation'),
                        'uri'         => '/client/software_install.php',
                        'visible'     => '0',
                        'title_class' => 'apps_installer'
                    ]
                ]
            ],
            'daily_backup'       => [
                'label'              => tr('Daily backup'),
                'uri'                => '/client/backup.php',
                'title_class'        => 'hdd',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'backup'
                ],
            ],
            'file_manager'       => [
                'label'              => tr('FileManager'),
                'uri'                => '/ftp/',
                'target'             => '_blank',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'ftp'
                ],
            ],
            'phpmyadmin'         => [
                'label'              => tr('PhpMyAdmin'),
                'uri'                => '/pma/',
                'target'             => '_blank',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'sql'
                ]
            ],
            'webstats'           => [
                'label'              => tr('Web statistics'),
                'uri'                => '{WEBSTATS_PATH}',
                'target'             => '_blank',
                'privilege_callback' => [
                    'name'  => 'customerHasFeature',
                    'param' => 'webstats'
                ]
            ]
        ]
    ],
    'support'    => [
        'label'              => tr('Support'),
        'uri'                => '{SUPPORT_SYSTEM_PATH}',
        'target'             => '{SUPPORT_SYSTEM_TARGET}',
        'class'              => 'support',
        'privilege_callback' => [
            'name'  => 'customerHasFeature',
            'param' => 'support'
        ],
        'pages'              => [
            'tickets_open'   => [
                'label'       => tr('Open tickets'),
                'uri'         => '/client/ticket_system.php',
                'title_class' => 'support'
            ],
            'tickets_closed' => [
                'label'       => tr('Closed tickets'),
                'uri'         => '/client/ticket_closed.php',
                'title_class' => 'support'
            ],
            'new_ticket'     => [
                'label'       => tr('New ticket'),
                'uri'         => '/client/ticket_create.php',
                'title_class' => 'support'
            ],
            'view_ticket'    => [
                'label'       => tr('View ticket'),
                'uri'         => '/client/ticket_view.php',
                'title_class' => 'support',
                'visible'     => '0'
            ]
        ]
    ],
    'profile'    => [
        'label' => tr('Profile'),
        'uri'   => '/client/profile.php',
        'class' => 'profile',
        'pages' => [
            'overview'      => [
                'label'       => tr('Account summary'),
                'uri'         => '/client/profile.php',
                'title_class' => 'profile'
            ],
            'personal_data' => [
                'label'       => tr('Personal data'),
                'uri'         => '/client/personal_change.php',
                'title_class' => 'profile'
            ],
            'passsword'     => [
                'label'       => tr('Password'),
                'uri'         => '/client/password_update.php',
                'title_class' => 'profile'
            ],
            'language'      => [
                'label'       => tr('Language'),
                'uri'         => '/client/language.php',
                'title_class' => 'multilanguage'
            ],
            'layout'        => [
                'label'       => tr('Layout'),
                'uri'         => '/client/layout.php',
                'title_class' => 'layout'
            ]
        ]
    ]
];
