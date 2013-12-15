##Plugin local configuration files

###Introduction


This directory allow to store plugin local configuration files. Local configuration files allow to override the default
plugin parameters defined in the default configuration files in persistent way, without having to restore them after
updating your plugins to a newer version. It's the responsability of the admin to check that parameters added in these
files still apply to the newest plugin versions.

**Note:** To avoid any accidental configuration data loss, the local configuration files are never automatically deleted.

###How it works

When a local configuration file is found for a plugin, both, the default configuration file and the local configuration
file are merged together. Parameters defined in the local configuration files take precedence over those defined in
default configuration files.

###Local configuration files naming convention

Local configuration files *MUST* be named with plugin name followed by the php file extension such as: JailKit.php

###Local configuration file sample

Here we want override the following default plugin configuration file:

```php
<?php
return array(
	// Jailkit installation directory.
	// This path is used as value of the --prefix option (JailKit configure script).
	// IMPORTANT: You must never change this parameter while updating the plugin to a new version.
	'install_path' => '/usr/local', // (Recommended value)

	// Full path to the root jail directory which holds all jails. Be sure that the partition in which this directory is
	// living has enough space to host the jails.
	// IMPORTANT: You must never change this parameter while updating the plugin to a new version.
	'root_jail_dir' => '/home/imscp-jails',

	// See man shells
	// Don't change this value if you do not know what you are doing
	'shell' => '/bin/bash', // (Recommended value)

	// See man jk_init
	'jail_app_sections' => array(
		'imscp-base', // Include Pre-selected sections, users and groups
		'mysql-client'
	),

	// See man jk_cp
	// Any file which is not installed on your system will be ignored
	'jail_additional_apps' => array(
		'/bin/hostname',
		'/usr/bin/basename',
		'/usr/bin/dircolors',
		'/usr/bin/dirname',
		'/usr/bin/clear_console',
		'/usr/bin/env',
		'/usr/bin/id',
		'/usr/bin/groups',
		'/usr/bin/lesspipe',
		'/usr/bin/tput',
		'/usr/bin/which'
	),

	// See man jk_socketd
	'jail_socketd_base' => '512',
	'jail_socketd_peak' => '2048',
	'jail_socketd_interval' => '5.0'
);
```

We create the following local configuration file:

**gui/data/persistent/plugins/JailKit.php file:**
```php
<?php
return array(
	// Override default jail roor directory
	'root_jail_dir' => '/var/www/imscp-jails',

	// Append the git section to the jail_app_sections parameter
	'jail_app_sections' => array(
		'git'
	)
);
```

####Important

Elements from default plugin configuration files are never removed automatically (this is by design). To remove an
element, you must process as follow:

**gui/data/persistent/plugins/JailKit.php file:**
```php
<?php
return array(
	'__OVERRIDE__' => array(
		// Override default jail roor directory
		'root_jail_dir' => '/var/www/imscp-jails',

		// Append the git section to the jail_app_sections parameter
		'jail_app_sections' => array(
			'git'
		)
	),
	'__REMOVE__' => array(
		// Remove mysql-client section from the jail_app_section parameter
		'jail_apps_sections => array(
			'mysql-client'
		),

		// Remove hostname command from the jail_additional_apps parameter
		'jail_additional_apps' => array(
			'/bin/hostname'
		)
	)
);
```

Here, the special array key '__OVERRIDE__' defines an array which contain elements to add/override, and the second
special array key '__REMOVE__', an array which contain elements to remove.
