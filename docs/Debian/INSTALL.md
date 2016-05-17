## i-MSCP installation on Debian

### 1) Requirements

- 1 GHz or faster 32 bits (x86) or 64 bits (x64) processor (recommended)
- 1 Gio memory (minimum) - For heavily loaded servers or high flow is recommended at least 8 Gio
- 1 Gio of available hard disk space (only for i-MSCP and managed services)
- Internet access (at least 100 Mbits/s recommended)
- A file system supporting extended attributes such as ext2, ext3, ext4 and reiserfs*.

See http://wiki.i-mscp.net/doku.php?id=start:howto:reiserfs if you use a reiserfs file system.

#### Supported Debian versions

Any released version >= 7.x (Debian 8.x recommended)

### 2) i-MSCP Installation

#### 1. Make sure that your system is up-to-date

    # apt-get update
    # apt-get dist-upgrade

#### 2. Install the pre-required packages

    # apt-get install ca-certificates perl whiptail

#### 3. Download and untar the distribution files to a secure directory

    # cd /usr/local/src
    # wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
    # tar xzf imscp-<version>.tar.gz

#### 4. Change to the newly created directory

    # cd imscp-<version>

#### 5. Install by running the imscp-autoinstall script

    # perl imscp-autoinstall -d

### 3) i-MSCP upgrade

#### 1. Make sure that your system is up-to-date

    # apt-get update
    # apt-get dist-upgrade

#### 2. Download and untar the distribution files to a secure directory

    # cd /usr/local/src
    # wget https://github.com/i-MSCP/imscp/archive/<version>.tar.gz
    # tar xzf imscp-<version>.tar.gz

#### 3. Change to the newly created directory

    # cd imscp-<version>

#### 4. Update by running the imscp-autoinstall script

    # perl imscp-autoinstall -d
