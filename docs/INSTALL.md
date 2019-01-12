# i-MSCP installation

## Supported Distributions

- Debian Jessie 8.x, Stretch 9.x
- Devuan Jessie 1.0, ASCII 2.0
- Ubuntu Trusty Thar 14.04, Xenial Xerus 16.04, Bionic Beaver 18.04

## I. Make sure that your distribution is up-to-date

```
apt-get update
apt-get --assume-yes dist-upgrade
```

## II. Install the pre-required packages

```
apt-get --assume-yes  install ca-certificates perl whiptail wget
```

## III. Download and un-tar the distribution files

```
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<release_tag>.tar.gz
tar -xzf imscp-<release_tag>.tar.gz
```

## IV. Change to the newly created directory

```
cd imscp-<release_tag>
```

## V. Install i-MSCP by running its installer

```
perl imscp-autoinstall -d
```
