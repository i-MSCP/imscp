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
		'uri' => '/reseller/index.php',
		'class' => 'general',
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/reseller/index.php',
				'title_class' => 'general'
			)
		)
	),
	'customers' => array(
		'label' => tr('Customers'),
		'uri' => '/reseller/users.php',
		'class' => 'manage_users',
		'pages' => array(
			'overview' => array(
				'label' => tr('Overview'),
				'uri' => '/reseller/users.php',
				'title_class' => 'users',
				'pages' => array(
					'domain_detail_link' => array(
						'label' => tr('Domain details'),
						'uri' => '/reseller/domain_details.php',
						'visible' => '0',
						'title_class' => 'domains'
					),
					'domain_edit_link' => array(
						'label' => tr('Edit domain'),
						'uri' => '/reseller/domain_edit.php',
						'visible' => '0',
						'title_class' => 'domains'
					),
					'customer_edit_link' => array(
						'label' => tr('Edit customer'),
						'uri' => '/reseller/user_edit.php',
						'visible' => '0',
						'title_class' => 'user_blue'
					),
					'domain_delete_link' => array(
						'label' => tr('Delete customer - {DOMAIN_NAME}'),
						'uri' => '/reseller/user_delete.php',
						'visible' => '0',
						'title_class' => 'domains'
					)
				)
			),
			'add_customer' => array(
				'label' => tr('Add customer'),
				'uri' => '/reseller/user_add1.php',
				'title_class' => 'user',
				'pages' => array(
					'add_customer_p2' => array(
						'label' => tr('Add customer - Next step'),
						'uri' => '/reseller/user_add2.php',
						'visible' => '0',
						'title_class' => 'user'
					),
					'add_customer_p3' => array(
						'label' => tr('Add customer - Next step'),
						'uri' => '/reseller/user_add3.php',
						'visible' => '0',
						'title_class' => 'user'
					),
					'add_customer_p4' => array(
						'label' => tr('Add customer - Next step'),
						'uri' => '/reseller/user_add4.php',
						'visible' => '0',
						'title_class' => 'user'
					)
				)
			),
			'manage_aliasses' => array(
				'label' => tr('Domain aliases'),
				'uri' => '/reseller/alias.php',
				'title_class' => 'domains',
				'privilege_callback' => array(
					array(
						'name' => 'resellerHasFeature',
						'param' => 'domain_aliases'
					),
					array(
						'name' => 'resellerHasCustomers'
					)
				),
				'pages' => array(
					'add_alias' => array(
						'label' => tr('Add domain alias'),
						'uri' => '/reseller/alias_add.php',
						'visible' => '0',
						'title_class' => 'domains'
					),
					'edit_alias' => array(
						'label' => tr('Edit domain alias'),
						'uri' => '/reseller/alias_edit.php',
						'visible' => '0',
						'title_class' => 'domains'
					)
				)
			),
			'welcome_mail' => array(
				'label' => tr('Welcome email'),
				'uri' => '/reseller/settings_welcome_mail.php',
				'title_class' => 'email'
			),
			'lostpassword_mail' => array(
				'label' => tr('Lost password email'),
				'uri' => '/reseller/settings_lostpassword.php',
				'title_class' => 'email'
			),
			'circular' => array(
				'label' => tr('Circular'),
				'uri' => '/reseller/circular.php',
				'title_class' => 'email',
				'privilege_callback' => array(
					'name' => 'resellerHasCustomers'
				)
			)
		)
	),
	'hosting_plans' => array(
		'label' => tr('Hosting plans'),
		'uri' => '/reseller/hosting_plan.php',
		'class' => 'hosting_plans',
		'pages' => array(
			'overview' => array(
				'label' => tr('Hosting plans'),
				'uri' => '/reseller/hosting_plan.php',
				'title_class' => 'hosting_plans',
				'pages' => array(
					'hosting_plan_edit_link' => array(
						'dynamic_title' => '{TR_DYNAMIC_TITLE}',
						'uri' => '/reseller/hosting_plan_edit.php',
						'title_class' => 'hosting_plans',
						'visible' => '0'
					)
				)
			),
			'add_hosting_plan' => array(
				'label' => tr('Add hosting plan'),
				'uri' => '/reseller/hosting_plan_add.php',
				'title_class' => 'hosting_plans',
				'class' => 'hosting_plan_add'
			)
		)
	),
	'statistics' => array(
		'label' => tr('Statistics'),
		'uri' => '/reseller/user_statistics.php',
		'class' => 'statistics',
		'privilege_callback' => array(
			'name' => 'resellerHasCustomers'
		),
		'pages' => array(
			'user_statistics' => array(
				'label' => tr('User statistics'),
				'uri' => '/reseller/user_statistics.php',
				'title_class' => 'stats',
				'pages' => array(
					'user_statistics_details' => array(
						'label' => tr('{USERNAME} user statistics'),
						'uri' => '/reseller/user_statistics_details.php',
						'visible' => '0',
						'title_class' => 'stats'
					)
				)
			),
			'ip_usage' => array(
				'label' => tr('IP assignments'),
				'uri' => '/reseller/ip_usage.php',
				'title_class' => 'stats'
			)
		)
	),
	'supports' => array(
		'label' => tr('Support'),
		'uri' => '{SUPPORT_SYSTEM_PATH}',
		'target' => '{SUPPORT_SYSTEM_TARGET}',
		'class' => 'support',
		'privilege_callback' => array(
			array(
				'name' => 'resellerHasFeature',
				'param' => 'support'
			),
			array(
				'name' => 'resellerHasCustomers'
			)
		),
		'pages' => array(
			'tickets_open' => array(
				'label' => tr('Open tickets'),
				'uri' => '/reseller/ticket_system.php',
				'title_class' => 'support'
			),
			'tickets_closed' => array(
				'label' => tr('Closed tickets'),
				'uri' => '/reseller/ticket_closed.php',
				'title_class' => 'support'
			),
			'new_ticket' => array(
				'label' => tr('New ticket'),
				'uri' => '/reseller/ticket_create.php',
				'title_class' => 'support'
			),
			'view_ticket' => array(
				'label' => tr('View ticket'),
				'uri' => '/reseller/ticket_view.php',
				'title_class' => 'support',
				'visible' => '0'
			)
		)
	),
	'profile' => array(
		'label' => tr('Profile'),
		'uri' => '/reseller/profile.php',
		'class' => 'profile',
		'pages' => array(
			'overview' => array(
				'label' => tr('Account summary'),
				'uri' => '/reseller/profile.php',
				'title_class' => 'profile'
			),
			'personal_data' => array(
				'label' => tr('Personal data'),
				'uri' => '/reseller/personal_change.php',
				'title_class' => 'profile'
			),
			'passsword' => array(
				'label' => tr('Password'),
				'uri' => '/reseller/password_update.php',
				'title_class' => 'profile'
			),
			'language' => array(
				'label' => tr('Language'),
				'uri' => '/reseller/language.php',
				'title_class' => 'multilanguage'
			),
			'layout' => array(
				'label' => tr('Layout'),
				'uri' => '/reseller/layout.php',
				'title_class' => 'layout'
			)
		)
	)
);
