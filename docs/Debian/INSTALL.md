# i-MSCP installation on Debian

## Supported Debian versions

- Debian Jessie (8.x)
- Debian Stretch (9.x)

## Installation

### 1. Make sure that your distribution is up-to-date

```
apt-get update
apt-get --assume-yes --auto-remove --no-install-recommends dist-upgrade
```

### 2. Install the pre-required packages

```
apt-get -y --auto-remove --no-install-recommends install ca-certificates perl \
whiptail wget
```

### 3. Download and un-tar the distribution files

```
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<release_tag>.tar.gz
tar -xzf imscp-<release_tag>.tar.gz
```

### 4. Change to the newly created directory

```
cd imscp-<release_tag>
```

### 5. Install i-MSCP by running its installer

```
perl imscp-autoinstall -d
```

## i-MSCP Upgrade

### 1. Make sure to read the errata file

Before upgrading, you must not forget to read the
[errata file](https://github.com/i-MSCP/imscp/blob/<release_tag>/docs/<release_branch>_errata.md)

### 2. Make sure to make a backup of your data

Before any upgrade attempt it is highly recommended to make a backup of the
following directories:

```
/var/www/virtual
/var/mail/virtual
```

These directories hold the data of your customers and it is really important to
backup them for an easy recovering in case something goes wrong during upgrade.

### 3. Make sure that your distribution is up-to-date

```
apt-get update
apt-get --assume-yes --auto-remove --no-install-recommends dist-upgrade
```

### 4. Download and un-tar the distribution files

```
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<release_tag>.tar.gz
tar -xzf imscp-<release_tag>.tar.gz
```

### 5. Change to the newly created directory

```
cd imscp-<release_tag>
```

### 6. Update i-MSCP by running its installer

```
perl imscp-autoinstall -d
```
