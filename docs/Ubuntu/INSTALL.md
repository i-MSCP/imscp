## i-MSCP installation on Ubuntu

### 1) Requirements

- 1 GHz or faster 32 bits (x86) or 64 bits (x64) processor (recommended)
- 1 Gio memory (minimum) - For heavily loaded servers or high flow is recommended at least 16 Gio
- 2 Gio of available hard disk space for i-MSCP and managed services, excluding user data
- Internet access (at least 100 Mbits/s recommended)
- A file system supporting extended attributes such as ext2, ext3, ext4 and reiserfs*.
- System supporting bind mounts

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

```
# mount -o remount /dev/disk/by-uuid/74699091-3ab8-43f2-bdd5-d1d898ab50fd
```

*Note:* If needed, you can find the uuid of your device, with the following command:

```
# blkid <device>
```

where `<device>` must be replaced by your device path such as `/dev/sda1`

#### LXC users

If you want install i-MSCP inside a LXC container, the following conditions must be met:

- You must have the `CAP_MKNOD` capability inside the container. Thus, you must ensure that `mknod` is not in the list
  of dropped capabilities (needed for pbuilder).
- You must have the `CAP_SYS_ADMIN` capability inside the container (needed for mount(8)). Thus, you must ensure that
  `sys_admin` is not in the list of dropped capabilities.
- You must allow the creation of devices inside the container by white-listing them (needed for pbuilder). Easy solution
  is to add `lxc.cgroup.devices.allow = a *:* rwm` in LXC container configuration file.
- If you use `Apparmor`, you must allow bindmounts inside your container by modifying the default apparmor profile
  `/etc/apparmor.d/lxc/lxc-default` or by creating a specific apparmor profile for the container.

Note that all those operations must be done on the host, not in the container.

**See also:**

- https://linuxcontainers.org/fr/lxc/manpages/man5/lxc.container.conf.5.html
- https://help.ubuntu.com/lts/serverguide/lxc.html#lxc-apparmor

#### Supported Ubuntu versions

Any LTS version >= 12.04 (Ubuntu 16.04 recommended)

### 2) i-MSCP Installation

#### 1. Make sure that your system is up-to-date

    # apt-get update
    # apt-get dist-upgrade

#### 2. Install the pre-required packages

    # apt-get install ca-certificates perl wget whiptail

#### 3. Download and untar the distribution files

    # cd /usr/local/src
    # wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
    # tar -xzf <version>.tar.gz

#### 4. Change to the newly created directory

    # cd imscp-<version>

#### 5. Install i-MSCP by running its installer

    # perl imscp-autoinstall -d

### 3) i-MSCP Upgrade

#### 1. Make sure that your system is up-to-date

    # apt-get update
    # apt-get dist-upgrade

#### 2. Download and untar the distribution files

    # cd /usr/local/src
    # wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
    # tar -xzf <version>.tar.gz

#### 3. Change to the newly created directory

    # cd imscp-<version>

#### 4. Update i-MSCP by running its installer

    # perl imscp-autoinstall -d
