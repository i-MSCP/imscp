## i-MSCP installation on Devuan

### Requirements

- 1 GHz or faster 32 bits (x86) or 64 bits (x64) processor
- 1 Gio memory (minimum) - For heavily loaded servers or high flow is recommended at least 8 Gio
- 1 Gio of available hard disk space for i-MSCP and managed services, excluding user data
- Internet access (at least 100 Mbits/s recommended)
- A Linux kernel >= 2.6.26
- A file system supporting extended attributes such as ext2, ext3, ext4 and reiserfs*
- Appropriate privileges to create devices (CAP_MKNOD capability)
- Appropriate privileges to mount, unmount and remount filesystems (CAP_SYS_ADMIN capability)

#### Reiserfs users

In order, to use the reiserfs file system with i-MSCP, you must follow these steps:

Edit your `/etc/fstab` file to add the `attrs` option for your device (e.g. device containing the /var partition). For
instance:

```
UUID=74699091-3ab8-43f2-bdd5-d1d898ab50fd /     reiserfs notail          0    1
```

should be updated to:

```
UUID=74699091-3ab8-43f2-bdd5-d1d898ab50fd /     reiserfs notail,attrs    0    1
```

Once you did that, you can remount your device. For instance:

```bash
mount -o remount /dev/disk/by-uuid/74699091-3ab8-43f2-bdd5-d1d898ab50fd
```

*Note:* If needed, you can find the uuid of your device, with the following command:

```bash
blkid <device>
```

where `<device>` must be replaced by your device path such as `/dev/sda1`

#### LXC containers

If you want install i-MSCP inside a LXC container, the following conditions have to be met:

- You must have the `CAP_MKNOD` capability inside the container. Thus, you must ensure that `mknod` is not in the list
  of dropped capabilities
- You must have the `CAP_SYS_ADMIN` capability inside the container (required to mount filesystems). Thus, you must
ensure that `sys_admin` is not in the list of dropped capabilities.
- You must allow the creation of devices inside the container by white-listing them. Easy solution is to add
  `lxc.cgroup.devices.allow = a *:* rwm` in LXC container configuration file.
- If you use `Apparmor`, you must allow mount,umount and remount operations inside your container by modifying the
  default apparmor profile `/etc/apparmor.d/lxc/lxc-default` or by creating a specific apparmor profile for the
  container.

Note that these operations must be done on the host, not in the container.

**See also:**

- https://i-mscp.net/index.php/Thread/14039-i-MSCP-inside-a-LXC-container-Managed-by-Proxmox-4-x
- https://linuxcontainers.org/fr/lxc/manpages/man5/lxc.container.conf.5.html
- https://help.ubuntu.com/lts/serverguide/lxc.html#lxc-apparmor
- http://wiki.apparmor.net/index.php/AppArmor_Core_Policy_Reference#Mount_rules_.28AppArmor_2.8_and_later.29

#### OpenVZ containers (Proxmox and Virtuozzo)

You could have to increase the `fs.ve-mount-nr` limit, else, an error such as `mount: Cannot allocate memory` could be
threw by CageFS. To avoid this problem you must:

- Increase the limit by adding an entry such as `fs.ve-mount-nr = 4096` to your `/etc/sysctl.conf` file
- Make the new limit effective by executing the `sysctl -p` command

Note that these operations must be done on the host, not in the container.

#### Supported Devuan versions

Any released version >= 1.0 (Devuan Jessie)

### i-MSCP Installation

#### 1. Make sure that your distribution is up-to-date

```bash
apt-get update && apt-get --assume-yes --auto-remove --no-install-recommends dist-upgrade
```

#### 2. Install the pre-required packages

```bash
apt-get --assume-yes --auto-remove --no-install-recommends install ca-certificates perl wget whiptail
```

#### 3. Download and untar the distribution files

```bash
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
tar -xzf <version>.tar.gz
```

#### 4. Change to the newly created directory

```bash
cd imscp-<version>
```

#### 5. Install i-MSCP by running its installer

```bash
perl imscp-autoinstall -d
```

### i-MSCP Upgrade

#### 1. Make sure to read the errata file

Before upgrading your must read the errata file which describ information about changes made in new i-MSCP versions,
and any pre-task that must be done by the administrator before upgrading.

The errata file for this i-MSCP version is located at: https://github.com/i-MSCP/imscp/blob/<version>/docs/1.4.x_errata.md

#### 2. Make sure to make a backup of your data

Before any upgrade attempt it is highly recommended to perform a backup of the following directories:

```
/var/www/virtual
/var/mail/virtual
```

Those directories hold the data of your customers and it is really important to backup them for an easy recovering in
case something goes wrong during upgrade.

#### 3. Make sure that your distribution is up-to-date

```bash
apt-get update && apt-get --assume-yes --auto-remove --no-install-recommend dist-upgrade
```

#### 4. Download and untar the distribution files

```bash
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
tar -xzf <version>.tar.gz
```

#### 5. Change to the newly created directory

```bash
cd imscp-<version>
```

#### 6. Update i-MSCP by running its installer

```bash
perl imscp-autoinstall -d
```
