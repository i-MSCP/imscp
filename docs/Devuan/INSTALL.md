# i-MSCP installation on Devuan

## Supported Devuan versions

Any released version >= 1.0 (Devuan Jessie)

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

### 3. Download and untar the distribution files

```
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
tar -xzf <version>.tar.gz
```

### 4. Change to the newly created directory

```
cd imscp-<version>
```

### 5. Install i-MSCP by running its installer

```
perl imscp-autoinstall -d
```

## Upgrade

### 1. Make sure to read the errata file

Before upgrading, you must not forget to read the
[errata file](https://github.com/i-MSCP/imscp/blob/<version>/docs/1.5.x_errata.md)


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

### 4. Download and untar the distribution files

```
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
tar -xzf <version>.tar.gz
```

### 5. Change to the newly created directory

```
cd imscp-<version>
```

### 6. Update i-MSCP by running its installer

```
perl imscp-autoinstall -d
```
