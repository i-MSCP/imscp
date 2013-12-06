##Plugin local configuration files

###Introduction


This directory allow to store plugin local configuration files. Local configuration files allow to override the default
plugin parameters defined in the default configuration files in persistent way, without having to restore them after
updating your plugin to a newer version. It's the responsability of the admin to check that parameters added in these
files still apply to the newest plugin version.

**Note:** To avoid any accidental configuration loss, the local configuration files are never automatically deleted.

###How it works

When a local configuration file is found for a plugin, both, the default configuration file and the local configuration
file are merged together. Parameters defined in the local configuration file take precedence over those defined in
default configuration file.

###Local configuration file naming convention

Local configuration files *MUST* be named with plugin name followed by the php file extension such as: JailKit.php

###Local configuration file sample

**gui/data/persistent/plugins/JailKit.php file:**
```php
<?php
return array(
	// Override default installation path
	'install_path' => '/usr'
);
```
