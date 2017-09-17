# i-MSCP Vagrant box

This directory contains all you need to setup a
[Vagrant](http://www.vagrantup.com/) box with i-MSCP pre-installed.

Note that Vagrant boxes are pulled from
[Vagrant Cloud](https://app.vagrantup.com/debian/boxes/stretch64).

## Requirements

- VirtualBox or (LXC and vagrant-lxc plugin)
- Vagrant >= 2.0.0
- vagrant-reload Vagrant plugin

Note that the documentation below assumes VirtualBox.

## Vagrant boxes

Vagrant boxes are pulled from [Vagrant Cloud](https://app.vagrantup.com/).

The following Vagrant boxes are made available

- Debian Jessie: `imscp_debian_jessie` (VirtualBox, LXC)
- Debian Stretch: `imscp_debian_stretch` (VirtualBox, LXC)
- Ubuntu Trusty Thar: `imscp_ubuntu_trusty` (VirtualBox only)
- Ubuntu Xenial Xerus: `imscp_ubuntu_xenial` (VirtualBox only)

## Getting started

### Installing Vagrant

You can download latest Vagrant distribution at
[Vagrant Download](https://www.vagrantup.com/downloads.html).

### Installing vagrant-reload Vagrant plugin

You must install the `vagrant-reload` Vagrant plugin:

```
vagrant plugin install vagrant-reload
```

#### PhpStorm IDE

If you run Vagrant through PhpStorm IDE, you need to install the
`vagrant-reload` plugin through PhpStorm Vagrant settings interface:

1. Go to the Settings / Tools / Vagrant settings interface
2. On the right window, select the `Plugins` tab
3. Click on the addition icon
4. Type `vagrant-reload`
5. Click on the `OK` button
6. Click on the `Apply` button at bottom

### Vagrant file

You must link the [Vagrantfile](Vagrantfile) to the base directory
of the i-MSCP archive directory:

```
cd <imscp_archive_dir>
ln -s docs/vagrant/Vagrantfile Vagrantfile
```

### Preseeding file

You must create an
[i-MSCP preseed](https://wiki.i-mscp.net/doku.php?id=start:preseeding) file:

```
cd <imscp_archive_dir>
cp docs/preseed.pl imscp_preseed.pl
nano imscp_preseed.pl
```

## Creating the Vagrant box

You can create the Vagrant box as follows:

```
vagrant up <vagrant_box_name>
```

where `<vagrant_box_name>` must be one of names listed in the Vagrant boxes
section above.

For instance, to create a Debian Jessie Vagrant box, you must run:

```
vagrant up imscp_debian_jessie
```

Note that if you don't pass a name, a Debian Stretch Vagrant box will be
created.

## Login into Vagrant box

You can login into the newly created VM as follows:

```
vagrant ssh
sudo -s
```

## Troubleshooting

### Default keyboard layout

Default keyboard layout fits for Americans. You can reconfigure the keyboard as
follows:

```
vagrant ssh
sudo -s
apt-get install console-setup
dpkg-reconfigure console-setup
dpkg-reconfigure keyboard-configuration
service keyboard-setup restart
udevadm trigger --subsystem-match=input --action=change
```

Then once done, reboot the VM.
