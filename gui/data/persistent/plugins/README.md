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

**gui/data/persistent/plugins/JailKit.php file:**
```php
<?php
return array(
	// Override default jail_app_sections parameter to add the git section
	'jail_app_sections' => array(
		'imscp-base',
		'mysql-client',
		'git'
	)

);
```
