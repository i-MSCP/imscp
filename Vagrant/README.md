# i-MSCP Vagrant box

This directory contains all you need to setup a
[Vagrant](http://www.vagrantup.com/) box with i-MSCP pre-installed.

## Requirements

- VirtualBox or (LXC and vagrant-lxc plugin)
- Vagrant ≥ 2.0.0
- vagrant-reload Vagrant plugin
- rsync

Note that the documentation below assumes the VirtualBox Vagrant provider.

## Vagrant boxes

Vagrant boxes are pulled from [Vagrant Cloud](https://app.vagrantup.com/).

The following Vagrant boxes are made available

- Debian 8.x/Jessie: `imscp_debian_jessie` (VirtualBox, LXC)
- Debian 9.x/Stretch: `imscp_debian_stretch` (VirtualBox, LXC)
- Ubuntu 14.04/Trusty Thar: `imscp_ubuntu_trusty` (VirtualBox only)
- Ubuntu 16.04/Xenial Xerus: `imscp_ubuntu_xenial` (VirtualBox only)

## Getting started

### Installing Vagrant

You can download latest Vagrant distribution at
[Vagrant Download](https://www.vagrantup.com/downloads.html).

### Installing vagrant-reload Vagrant plugin

You must install the `vagrant-reload` Vagrant plugin:

```
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
[i-MSCP preseed](https://wiki.i-mscp.net/doku.php?id=start:preseeding) file:

```
cd <imscp_archive_dir>/Vagrant
cp ../docs/preseed.pl .
nano preseed.pl
```

Be careful to fill up all required parameters. If one required parameter is
missing, Vagrant box provisioning will fail.

## Creating the Vagrant box

You can create the Vagrant box as follows:

```
cd <imscp_archive_dir>/Vagrant
vagrant up <vagrant_box_name>
```

where `<vagrant_box_name>` must be one of names listed in the `Vagrant boxes`
section above.

For instance, to create a `Debian Jessie` Vagrant box, you must run:

```
cd <imscp_archive_dir>/Vagrant
vagrant up imscp_debian_jessie
```

Note that if you don't pass a name, a `Debian Stretch` Vagrant box will be
created.

## Login into Vagrant box

You can login into the newly created VM as follows:

```
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

```
cd <imscp_archive_dir>/Vagrant
vagrant ssh <vagrant_box_name>
sudo -s
apt-get install console-setup
dpkg-reconfigure console-setup
dpkg-reconfigure keyboard-configuration
service keyboard-setup restart
udevadm trigger --subsystem-match=input --action=change
```

Then once done, reboot the VM.
