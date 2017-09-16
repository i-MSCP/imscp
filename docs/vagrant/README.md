# i-MSCP Vagrant box

This directory contains all you need to setup a
[Vagrant](http://www.vagrantup.com/) box with i-MSCP pre-installed.

Note that Vagrant boxes are pulled from
[Vagrant Cloud](https://app.vagrantup.com/debian/boxes/stretch64).

## Requirements

- VirtualBox
- Vagrant >= 2.0.0
- vagrant-reload Vagrant plugin

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

If you run Vagrant through PhpStorm IDE, you need to install the `vagrant-reload` plugin
through PhpStorm Vagrant settings interface:

1. Go to the Settings / Tools / Vagrant settings interface
2. On the right window, select the plugin tab
3. Click on the plugin addition icon
4. Type `vagrant-reload`
5. Click on `OK` button
6. Click on `Apply` button at bottom


### Vagrant file

You must link the [Vagrantfile](Vagrantfile) to the base directory
of the i-MSCP archive directory:

```
cd <imscp_archive_dir>
ln -s docs/vagrant/Vagrantfile Vagrantfile
```

### Preseeding file

You must create and fill an
[i-MSCP preseed](https://wiki.i-mscp.net/doku.php?id=start:preseeding) file:

```
cd <imscp_archive_dir>
cp docs/preseed.pl imscp_preseed.pl
nano imscp_preseed.pl
```

## Creating the box by running Vagrant

You can create the VM as follows:

```
vagrant up
```

## Login into Vagrant box

You can login into the newly created VM as follows:

```
vagrant ssh
sudo -s
```

## Troubleshooting

### Default keyboard layout

Default keyboard layout fits for Americans. You can reconfigure the keyboard as follows:

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
