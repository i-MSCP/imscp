# i-MSCP Vagrant box

This directory contains all you need to setup a
[Vagrant](http://www.vagrantup.com/) box with i-MSCP pre-installed.

## Requirements

- VirtualBox or (LXC and vagrant-lxc plugin)
- Vagrant ≥ 2.0.0
- vagrant-reload Vagrant plugin
- rsync

Note that the documentation below assumes the
[VirtualBox](https://www.vagrantup.com/docs/virtualbox/) Vagrant provider.

## Vagrant boxes

Vagrant boxes are pulled from [Vagrant Cloud](https://app.vagrantup.com/).

The following Vagrant boxes are made available

- Debian 9.x (Stretch): `imscp_debian_stretch` (VirtualBox, LXC)
- Debian 10.x (Buster): `imscp_debian_buster` (VirtualBox, LXC)
- Ubuntu 16.04 (Xenial Xerus): `imscp_ubuntu_xenial` (VirtualBox only)
- Ubuntu 18.04 (Bionic Beaver): `imscp_ubuntu_bionic` (VirtualBox only)
- Ubuntu 20.04 (Focal Fossa): `imscp_ubuntu_focal` (VirtualBox only)

## Getting started

### Installing Vagrant

You can download latest Vagrant distribution at
[Vagrant Download](https://www.vagrantup.com/downloads.html).

### Installing vagrant-reload Vagrant plugin

You must install the `vagrant-reload` Vagrant plugin:

```shell
cd <imscp_archive_dir>/Vagrant
vagrant plugin install vagrant-reload
```

#### PhpStorm IDE

If you run Vagrant through the PhpStorm IDE, you need to install the
`vagrant-reload` plugin through PhpStorm Vagrant settings interface:

1. Go to the Settings / Tools / Vagrant settings interface
2. On the right window, select the `Plugins` tab
3. Click on the addition icon
4. Type `vagrant-reload`
5. Click on the `OK` button
6. Click on the `Apply` button at bottom

### Preseeding file

You must create an
[i-MSCP preseeding](https://wiki.i-mscp.net/doku.php?id=start:preseeding) file:

```shell
cd <imscp_archive_dir>/Vagrant
cp ../docs/preseed.pl .
nano preseed.pl
```

Be careful to fill up all mandatory parameters. If one of required parameters is
missing, provisioning of the Vagrant box will fail.

Only the following parameters are mandatory:

- `ADMIN_PASSWORD`: Master administrator password
- `DEFAULT_ADMIN_ADDRESS`: Master administrator email address

You must also fill the `SERVER_HOSTNAME` parameter as the default hostname set in
Vagrant boxes doesn't fit with i-MSCP hostname policy.

For the `SQL_ROOT_PASSWORD` parameter, it is required only if the unix_socket
authentication isn't enabled for the SQL root user.

For all other parameters, the installer will make use of default values. Please
consult the [preseed.pl](../docs/preseed.pl) template file for further details.

## Creating the Vagrant box

You can create the Vagrant box as follows:

```shell
cd <imscp_archive_dir>/Vagrant
vagrant up <vagrant_box_name>
```

where `<vagrant_box_name>` must be one of names listed in the `Vagrant boxes`
section above.

For instance, to create a `Debian Buster` Vagrant box, you must run:

```shell
cd <imscp_archive_dir>/Vagrant
vagrant up imscp_debian_Buster
```

Note that if you don't pass a name, a `Debian Stretch` Vagrant box will be
created.

## Login into Vagrant box

You can login into the newly created Vagrant box as follows:

```shell
cd <imscp_archive_dir>/Vagrant
vagrant ssh <vagrant_box_name>
sudo -s
```

## Troubleshooting

### Default keyboard layout

Default keyboard layout fits well for Americans only. Thus, if you want to
connect to your Vagrant box through a ternminal (tty) and not simply through
SSH, you could have issues due to current keyboard layout.
 
You can reconfigure the keyboard layout as follows:

```shell
cd <imscp_archive_dir>/Vagrant
vagrant ssh <vagrant_box_name>
sudo -s
apt-get install console-setup
dpkg-reconfigure console-setup
dpkg-reconfigure keyboard-configuration
service keyboard-setup restart
udevadm trigger --subsystem-match=input --action=change
```

Then once done, reboot the Vagrant box.
