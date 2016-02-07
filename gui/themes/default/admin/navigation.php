<?php

/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
        'uri' => '/admin/index.php',
        'class' => 'general',
        'pages' => array(
            'overview' => array(
                'label' => tr('Overview'),
                'uri' => '/admin/index.php',
                'title_class' => 'general'
            ),
            'server_status' => array(
                'label' => tr('Services status'),
                'uri' => '/admin/server_status.php',
                'title_class' => 'serverstatus'
            ),
            'admin_log' => array(
                'label' => tr('Admin log'),
                'uri' => '/admin/admin_log.php',
                'title_class' => 'adminlog'
            )
        )
    ),
    'users' => array(
        'label' => tr('Users'),
        'uri' => '/admin/manage_users.php',
        'class' => 'manage_users',
        'pages' => array(
            'overview' => array(
                'label' => tr('Overview'),
                'uri' => '/admin/manage_users.php',
                'title_class' => 'users',
                'pages' => array(
                    'reseller_edit_link' => array(
                        'label' => tr('Edit reseller'),
                        'uri' => '/admin/reseller_edit.php',
                        'title_class' => 'user_green',
                        'visible' => '0'
                    ),
                    'domain_detail_link' => array(
                        'label' => tr('Domain details'),
                        'uri' => '/admin/domain_details.php',
                        'title_class' => 'general',
                        'visible' => '0'
                    ),
                    'customer_edit_link' => array(
                        'label' => tr('Edit admin'),
                        'uri' => '/admin/admin_edit.php',
                        'title_class' => 'user_blue',
                        'visible' => '0'
                    ),
                    'customer_delete_link' => array(
                        'label' => tr('Delete customer'),
                        'uri' => '/admin/user_delete.php',
                        'title_class' => 'user_blue',
                        'visible' => '0'
                    ),
                    'domain_edit_link' => array(
                        'label' => tr('Edit domain'),
                        'uri' => '/admin/domain_edit.php',
                        'title_class' => 'domains',
                        'visible' => '0'
                    )
                )
            ),
            'add_admin' => array(
                'label' => tr('Add admin'),
                'uri' => '/admin/admin_add.php',
                'title_class' => 'user_yellow'
            ),
            'add_reseller' => array(
                'label' => tr('Add reseller'),
                'uri' => '/admin/reseller_add.php',
                'title_class' => 'user_green'
            ),
            'resellers_assignment' => array(
                'label' => tr('Reseller assignments'),
                'uri' => '/admin/manage_reseller_owners.php',
                'title_class' => 'users2',
                'privilege_callback' => array(
                    array(
                        'name' => 'systemHasManyAdmins',
                    ),
                    array(
                        'name' => 'systemHasResellers'
                    )
                )
            ),
            'customers_assignment' => array(
                'label' => tr('Customer assignments'),
                'uri' => '/admin/manage_reseller_users.php',
                'title_class' => 'users2',
                'privilege_callback' => array(
                    'name' => 'systemHasResellers',
                    'param' => '2'
                )
            ),
            'circular' => array(
                'label' => tr('Circular'),
                'uri' => '/admin/circular.php',
                'title_class' => 'email',
                'privilege_callback' => array(
                    'name' => 'systemHasAdminsOrResellersOrCustomers'
                )
            ),
            'sessions_management' => array(
                'label' => tr('Sessions'),
                'uri' => '/admin/sessions_manage.php',
                'title_class' => 'users2'
            )
        )
    ),
    'system_tools' => array(
        'label' => tr('System tools'),
        'uri' => '/admin/system_info.php',
        'class' => 'webtools',
        'pages' => array(
            'overview' => array(
                'label' => tr('System information'),
                'uri' => '/admin/system_info.php',
                'title_class' => 'tools'
            ),
            'maintenance_settings' => array(
                'label' => tr('Maintenance settings'),
                'uri' => '/admin/settings_maintenance_mode.php',
                'title_class' => 'maintenancemode'
            ),
            'updates' => array(
                'label' => tr('i-MSCP updates'),
                'uri' => '/admin/imscp_updates.php',
                'title_class' => 'update'
            ),
            'database_updates' => array(
                'label' => tr('Database update'),
                'uri' => '/admin/database_update.php',
                'title_class' => 'update'
            ),
            'debugger' => array(
                'label' => tr('Debugger'),
                'uri' => '/admin/imscp_debugger.php',
                'title_class' => 'debugger'
            ),
            'rootkits_log' => array(
                'label' => tr('Anti-Rootkits Logs'),
                'uri' => '/admin/rootkit_log.php',
                'title_class' => 'general',
                'privilege_callback' => array(
                    'name' => 'systemHasAntiRootkits'
                )
            )
        )
    ),
    'statistics' => array(
        'label' => tr('Statistics'),
        'uri' => '/admin/server_statistic.php',
        'class' => 'statistics',
        'pages' => array(
            'server_statistic' => array(
                'label' => tr('Server statistics'),
                'uri' => '/admin/server_statistic.php',
                'title_class' => 'stats',
                'pages' => array(
                    'server_day_statistics_link' => array(
                        'label' => tr('Day statistics'),
                        'uri' => '/admin/server_statistic_day.php',
                        'title_class' => 'stats',
                        'visible' => '0'
                    )
                )
            ),
            'resellers_statistics' => array(
                'label' => tr('Reseller statistics'),
                'uri' => '/admin/reseller_statistics.php',
                'title_class' => 'stats',
                'privilege_callback' => array(
                    'name' => 'systemHasResellers',
                ),
                'pages' => array(
                    'reseller_user_statistics_link' => array(
                        'label' => tr('User statistics'),
                        'uri' => '/admin/reseller_user_statistics.php',
                        'visible' => '0',
                        'title_class' => 'stats',
                        'pages' => array(
                            'reseller_user_statistics_detail_link' => array(
                                'label' => tr('{USERNAME} user statistics'),
                                'uri' => '/admin/reseller_user_statistics_details.php',
                                'visible' => '0',
                                'title_class' => 'stats'
                            )
                        )
                    )
                )
            ),
            'ip_usage' => array(
                'label' => tr('IP usage'),
                'uri' => '/admin/ip_usage.php',
                'title_class' => 'ip',
                'privilege_callback' => array(
                    'name' => 'systemHasCustomers'
                )
            )
        )
    ),
    'support' => array(
        'label' => tr('Support'),
        'uri' => '/admin/ticket_system.php',
        'class' => 'support',
        'privilege_callback' => array(
            'name' => 'systemHasResellers'
        ),
        'pages' => array(
            'open_tickets' => array(
                'label' => tr('Open tickets'),
                'uri' => '/admin/ticket_system.php',
                'title_class' => 'support'
            ),
            'closed_tickets' => array(
                'label' => tr('Closed tickets'),
                'uri' => '/admin/ticket_closed.php',
                'title_class' => 'support'
            ),
            'view_ticket' => array(
                'label' => tr('View ticket'),
                'uri' => '/admin/ticket_view.php',
                'title_class' => 'support',
                'visible' => '0'
            )
        )
    ),
    'settings' => array(
        'label' => tr('Settings'),
        'uri' => '/admin/settings.php',
        'class' => 'settings',
        'pages' => array(
            'general' => array(
                'label' => tr('General settings'),
                'uri' => '/admin/settings.php',
                'title_class' => 'general'
            ),
            'language' => array(
                'label' => tr('Languages'),
                'uri' => '/admin/multilanguage.php',
                'title_class' => 'multilanguage'
            ),
            'custom_menus' => array(
                'label' => tr('Custom menus'),
                'dynamic_title' => '{TR_DYNAMIC_TITLE}',
                'uri' => '/admin/custom_menus.php',
                'title_class' => 'custom_link'
            ),
            'ip_management' => array(
                'label' => tr('IP management'),
                'uri' => '/admin/ip_manage.php',
                'title_class' => 'ip'
            ),
            'server_traffic' => array(
                'label' => tr('Server traffic'),
                'uri' => '/admin/settings_server_traffic.php',
                'title_class' => 'traffic'
            ),
            'welcome_mail' => array(
                'label' => tr('Welcome email'),
                'uri' => '/admin/settings_welcome_mail.php',
                'title_class' => 'email'
            ),
            'lostpassword_mail' => array(
                'label' => tr('Lost password email'),
                'uri' => '/admin/settings_lostpassword.php',
                'title_class' => 'email'
            ),
            'service_ports' => array(
                'label' => tr('Service ports'),
                'uri' => '/admin/settings_ports.php',
                'title_class' => 'general'
            ),
            'softwares_management' => array(
                'label' => tr('Software management'),
                'uri' => '/admin/software_manage.php',
                'title_class' => 'apps_installer',
                'pages' => array(
                    'softwares_permissions_link' => array(
                        'label' => tr('Software permissions'),
                        'uri' => '/admin/software_rights.php',
                        'visible' => '0',
                        'title_class' => 'apps_installer'
                    )
                )
            ),
            'softwares_options' => array(
                'label' => tr('Software options'),
                'uri' => '/admin/software_options.php',
                'title_class' => 'apps_installer'
            ),
            'plugins_management' => array(
                'label' => tr('Plugin management'),
                'uri' => '/admin/settings_plugins.php',
                'title_class' => 'plugin'
            )
        )
    ),
    'profile' => array(
        'label' => tr('Profile'),
        'uri' => '/admin/profile.php',
        'class' => 'profile',
        'pages' => array(
            'overview' => array(
                'label' => tr('Account summary'),
                'uri' => '/admin/profile.php',
                'title_class' => 'profile'
            ),
            'personal_change' => array(
                'label' => tr('Personal data'),
                'uri' => '/admin/personal_change.php',
                'title_class' => 'profile'
            ),
            'passsword_change' => array(
                'label' => tr('Password'),
                'uri' => '/admin/password_update.php',
                'title_class' => 'profile'
            ),
            'language' => array(
                'label' => tr('Language'),
                'uri' => '/admin/language.php',
                'title_class' => 'multilanguage',
            ),
            'layout' => array(
                'label' => tr('Layout'),
                'uri' => '/admin/layout.php',
                'title_class' => 'layout'
            )
        )
    )
);
