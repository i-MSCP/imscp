# i-MSCP upgrade

## I. Make sure to read the errata file

You must first read the
[errata](https://github.com/i-MSCP/imscp/blob/<release_tag>/docs/<release_branch>_errata.md)
file which can contain important notes regarding changes that were made in new
release, and possibly some pre-update tasks which must be done manually.

## II. Make sure to make a backup of your data

It is highly recommended to make a backup of the following directories:

```
/var/www/virtual
/var/mail/virtual
```

These directories hold the data of your customers and it is really important to
backup them for an easy recovering in case something goes wrong during upgrade.

You should also make a dump of your i-MSCP database.

## III. Make sure that your distribution is up-to-date

```
apt-get update
apt-get --assume-yes dist-upgrade
```

## IV. Download and untar the distribution files

```
cd /usr/local/src
wget https://github.com/i-MSCP/imscp/archive/<release_tag>.tar.gz
tar -xzf imscp-<release_tag>.tar.gz
```

## V. Change to the newly created directory

```
cd imscp-<release_tag>
```

## VI. Update i-MSCP by running its installer

```
perl imscp-autoinstall -d
```
