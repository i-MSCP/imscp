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
    'general'      => [
        'label' => tr('General'),
        'uri'   => '/admin/index.php',
        'class' => 'general',
        'pages' => [
            'overview'      => [
                'label'       => tr('Overview'),
                'uri'         => '/admin/index.php',
                'title_class' => 'general'
            ],
            'services_statuses' => [
                'label'       => tr('Service statuses'),
                'uri'         => '/admin/service_statuses.php',
                'title_class' => 'serverstatus'
            ],
            'admin_log'     => [
                'label'       => tr('Admin log'),
                'uri'         => '/admin/admin_log.php',
                'title_class' => 'adminlog'
            ]
        ]
    ],
    'users'        => [
        'label' => tr('Users'),
        'uri'   => '/admin/users.php',
        'class' => 'manage_users',
        'pages' => [
            'overview'             => [
                'label'       => tr('Overview'),
                'uri'         => '/admin/users.php',
                'title_class' => 'users',
                'pages'       => [
                    'user_edit'     => [
                        'dynamic_title' => '{TR_DYNAMIC_TITLE}',
                        'uri'           => '/admin/user_edit.php',
                        'title_class'   => '{DYNAMIC_TITLE_CLASS}',
                        'visible'       => '0'
                    ],
                    'reseller_edit' => [
                        'label'       => tr('Edit reseller'),
                        'uri'         => '/admin/reseller_edit.php',
                        'title_class' => 'user_green',
                        'visible'     => '0'
                    ],
                    'domain_detail' => [
                        'label'       => tr('Domain details'),
                        'uri'         => '/admin/domain_details.php',
                        'title_class' => 'general',
                        'visible'     => '0'
                    ]
                ]
            ],
            'add_admin'            => [
                'label'       => tr('Add admin'),
                'uri'         => '/admin/admin_add.php',
                'title_class' => 'user_yellow'
            ],
            'add_reseller'         => [
                'label'       => tr('Add reseller'),
                'uri'         => '/admin/reseller_add.php',
                'title_class' => 'user_green'
            ],
            'resellers_assignment' => [
                'label'              => tr('Reseller assignments'),
                'uri'                => '/admin/manage_reseller_owners.php',
                'title_class'        => 'users2',
                'privilege_callback' => [
                    [
                        'name' => 'systemHasManyAdmins',
                    ],
                    [
                        'name' => 'systemHasResellers'
                    ]
                ]
            ],
            'customers_assignment' => [
                'label'              => tr('Customer assignments'),
                'uri'                => '/admin/manage_reseller_users.php',
                'title_class'        => 'users2',
                'privilege_callback' => [
                    'name'  => 'systemHasResellers',
                    'param' => '2'
                ]
            ],
            'circular'             => [
                'label'              => tr('Circular'),
                'uri'                => '/admin/circular.php',
                'title_class'        => 'email',
                'privilege_callback' => [
                    'name' => 'systemHasAdminsOrResellersOrCustomers'
                ]
            ],
            'sessions_management'  => [
                'label'       => tr('Sessions'),
                'uri'         => '/admin/sessions_manage.php',
                'title_class' => 'users2'
            ]
        ]
    ],
    'system_tools' => [
        'label' => tr('System tools'),
        'uri'   => '/admin/system_info.php',
        'class' => 'webtools',
        'pages' => [
            'overview'             => [
                'label'       => tr('System information'),
                'uri'         => '/admin/system_info.php',
                'title_class' => 'tools'
            ],
            'maintenance_settings' => [
                'label'       => tr('Maintenance settings'),
                'uri'         => '/admin/settings_maintenance_mode.php',
                'title_class' => 'maintenancemode'
            ],
            'updates'              => [
                'label'              => tr('i-MSCP updates'),
                'uri'                => '/admin/imscp_updates.php',
                'title_class'        => 'update',
                'privilege_callback' => [
                    'name' => function () {
                        return stripos(Registry::get('config')['Version'], 'git') === false;
                    }
                ]
            ],
            'debugger'             => [
                'label'       => tr('Debugger'),
                'uri'         => '/admin/imscp_debugger.php',
                'title_class' => 'debugger'
            ],
            'rootkits_log'         => [
                'label'              => tr('Anti-Rootkits Logs'),
                'uri'                => '/admin/rootkit_log.php',
                'title_class'        => 'general',
                'privilege_callback' => [
                    'name' => 'systemHasAntiRootkits'
                ]
            ]
        ]
    ],
    'statistics'   => [
        'label' => tr('Statistics'),
        'uri'   => '/admin/server_statistic.php',
        'class' => 'statistics',
        'pages' => [
            'server_statistic'     => [
                'label'       => tr('Server statistics'),
                'uri'         => '/admin/server_statistic.php',
                'title_class' => 'stats'
            ],
            'resellers_statistics' => [
                'label'              => tr('Reseller statistics'),
                'uri'                => '/admin/reseller_statistics.php',
                'title_class'        => 'stats',
                'privilege_callback' => [
                    'name' => 'systemHasResellers',
                ],
                'pages'              => [
                    'reseller_user_statistics' => [
                        'label'       => tr('User statistics'),
                        'uri'         => '/admin/reseller_user_statistics.php',
                        'visible'     => '0',
                        'title_class' => 'stats',
                        'pages'       => [
                            'reseller_user_statistics_detail' => [
                                'label'       => tr('{USERNAME} user statistics'),
                                'uri'         => '/admin/reseller_user_statistics_details.php',
                                'visible'     => '0',
                                'title_class' => 'stats'
                            ]
                        ]
                    ]
                ]
            ],
            'ip_usage'             => [
                'label'              => tr('IP usage'),
                'uri'                => '/admin/ip_usage.php',
                'title_class'        => 'ip',
                'privilege_callback' => [
                    'name' => 'systemHasCustomers'
                ]
            ]
        ]
    ],
    'support'      => [
        'label'              => tr('Support'),
        'uri'                => '/admin/ticket_system.php',
        'class'              => 'support',
        'privilege_callback' => [
            'name' => 'systemHasResellers'
        ],
        'pages'              => [
            'open_tickets'   => [
                'label'       => tr('Open tickets'),
                'uri'         => '/admin/ticket_system.php',
                'title_class' => 'support'
            ],
            'closed_tickets' => [
                'label'       => tr('Closed tickets'),
                'uri'         => '/admin/ticket_closed.php',
                'title_class' => 'support'
            ],
            'view_ticket'    => [
                'label'       => tr('View ticket'),
                'uri'         => '/admin/ticket_view.php',
                'title_class' => 'support',
                'visible'     => '0'
            ]
        ]
    ],
    'settings'     => [
        'label' => tr('Settings'),
        'uri'   => '/admin/settings.php',
        'class' => 'settings',
        'pages' => [
            'general'              => [
                'label'       => tr('General settings'),
                'uri'         => '/admin/settings.php',
                'title_class' => 'general'
            ],
            'language'             => [
                'label'       => tr('Languages'),
                'uri'         => '/admin/multilanguage.php',
                'title_class' => 'multilanguage'
            ],
            'custom_menus'         => [
                'label'         => tr('Custom menus'),
                'dynamic_title' => '{TR_DYNAMIC_TITLE}',
                'uri'           => '/admin/custom_menus.php',
                'title_class'   => 'custom_link'
            ],
            'ip_management'        => [
                'label'       => tr('IP management'),
                'uri'         => '/admin/ip_manage.php',
                'title_class' => 'ip'
            ],
            'server_traffic'       => [
                'label'       => tr('Monthly server traffic'),
                'uri'         => '/admin/settings_server_traffic.php',
                'title_class' => 'traffic'
            ],
            'welcome_mail'         => [
                'label'       => tr('Welcome email'),
                'uri'         => '/admin/settings_welcome_mail.php',
                'title_class' => 'email'
            ],
            'lostpassword_mail'    => [
                'label'       => tr('Lost password email'),
                'uri'         => '/admin/settings_lostpassword.php',
                'title_class' => 'email'
            ],
            'service_ports'        => [
                'label'       => tr('Service ports'),
                'uri'         => '/admin/settings_ports.php',
                'title_class' => 'general'
            ],
            'softwares_management' => [
                'label'       => tr('Software management'),
                'uri'         => '/admin/software_manage.php',
                'title_class' => 'apps_installer',
                'pages'       => [
                    'softwares_permissions' => [
                        'label'       => tr('Software permissions'),
                        'uri'         => '/admin/software_rights.php',
                        'visible'     => '0',
                        'title_class' => 'apps_installer'
                    ]
                ]
            ],
            'softwares_options'    => [
                'label'       => tr('Software options'),
                'uri'         => '/admin/software_options.php',
                'title_class' => 'apps_installer'
            ],
            'plugins_management'   => [
                'label'       => tr('Plugin management'),
                'uri'         => '/admin/settings_plugins.php',
                'title_class' => 'plugin'
            ]
        ]
    ],
    'profile'      => [
        'label' => tr('Profile'),
        'uri'   => '/admin/profile.php',
        'class' => 'profile',
        'pages' => [
            'overview'         => [
                'label'       => tr('Account summary'),
                'uri'         => '/admin/profile.php',
                'title_class' => 'profile'
            ],
            'personal_change'  => [
                'label'       => tr('Personal data'),
                'uri'         => '/admin/personal_change.php',
                'title_class' => 'profile'
            ],
            'passsword_change' => [
                'label'       => tr('Password'),
                'uri'         => '/admin/password_update.php',
                'title_class' => 'profile'
            ],
            'language'         => [
                'label'       => tr('Language'),
                'uri'         => '/admin/language.php',
                'title_class' => 'multilanguage',
            ],
            'layout'           => [
                'label'       => tr('Layout'),
                'uri'         => '/admin/layout.php',
                'title_class' => 'layout'
            ]
        ]
    ]
];
