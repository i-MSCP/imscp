# i-MSCP Listener::Backup::Storage::Outsourcing listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

package Listener::Backup::Storage::Outsourcing;

# Stores customer backup directories elsewhere on local file system
#
# Howto setup and activate
# 1. Upload that listener file into the /etc/imscp/listeners.d directory
# 2. Edit the /etc/imscp/listeners.d/10_backup_storage_outsourcing.pl file
#    and set the $STORAGE_ROOT_PATH variable below according your needs
# 3. Trigger an i-MSCP reconfiguration: perl /var/www/imscp/engine/setup/imscp-reconfigure -danv

use strict;
use warnings;
use iMSCP::Debug qw/ error /;
use iMSCP::EventManager;
use iMSCP::Ext2Attributes qw/ setImmutable clearImmutable /;
use iMSCP::Dir;
use iMSCP::Mount qw/ addMountEntry removeMountEntry mount umount /;

#
## Configuration parameters
#

# Storage root path for outsourced customer backup directories
# For instance /srv/imscp/backups would mean that customer backup
# directories would be stored into:
# - /srv/imscp/backups/<customer1>
# - /srv/imscp/backups/<customer2>
# - ...
#
# Warning: Be sure to have enough space in the specified location.
my $STORAGE_ROOT_PATH = '';

#
## Please, don't edit anything below this line
#

# Don't register event listeners if the listener file is not configured yet
if ( $> == 0 && length $STORAGE_ROOT_PATH ) {
    iMSCP::EventManager->getInstance()->register(
        'onBoot',
        sub {
            local $@;
            eval {
                # Make sure that the root path for outsourced backup directories
                # exists and that it is set with expected ownership and permissions
                iMSCP::Dir->new( dirname => $STORAGE_ROOT_PATH )->make(
                    {
                        user  => $main::imscpConfig{'ROOT_USER'},
                        group => $main::imscpConfig{'ROOT_GROUP'},
                        mode  => 0750
                    }
                );
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            0;
        }
    );

    iMSCP::EventManager->getInstance()->register(
        'beforeHttpdAddFiles',
        sub {
            my ($data) = @_;

            return 0 unless $data->{'DOMAIN_TYPE'} eq 'dmn'
                && -d "$data->{'WEB_DIR'}/backups";

            # When files are being copied by i-MSCP httpd server, we must first
            # umount the outsourced backup directory
            umount( "$data->{'WEB_DIR'}/backups" );
        }
    );

    iMSCP::EventManager->getInstance()->register(
        'afterHttpdAddFiles',
        sub {
            my ($data) = @_;

            return 0 unless $data->{'DOMAIN_TYPE'} eq 'dmn';

            local $@;
            eval {
                my $backupDirHandle = iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/backups" );

                # If needed, moves data from existents backup directory into the
                # new backup directory
                unless ( $backupDirHandle->isEmpty() ) {
                    clearImmutable( $data->{'WEB_DIR'} );

                    unless ( -d "$STORAGE_ROOT_PATH/$data->{'DOMAIN_NAME'}" ) {
                        # Move backup directory to new location
                        $backupDirHandle->rcopy( "$STORAGE_ROOT_PATH/$data->{'DOMAIN_NAME'}" );
                    }

                    # Empty directory by re-creating it from scratch (should never occurs)
                    $backupDirHandle->clear();

                    setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
                } else {
                    # Create empty outsourced customer backup directory
                    iMSCP::Dir->new( dirname => "$STORAGE_ROOT_PATH/$data->{'DOMAIN_NAME'}" )->make(
                        {
                            user  => $data->{'USER'},
                            group => $data->{'GROUP'},
                            mode  => 0750
                        }
                    );
                }
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            # Outsource customer backup directory by mounting new backup directory on top of it
            my $rs ||= mount(
                {
                    fs_spec    => "$STORAGE_ROOT_PATH/$data->{'DOMAIN_NAME'}",
                    fs_file    => "$data->{'WEB_DIR'}/backups",
                    fs_vfstype => 'none',
                    fs_mntops  => 'bind,slave'
                }
            );
            $rs ||= addMountEntry(
                "$STORAGE_ROOT_PATH/$data->{'DOMAIN_NAME'} $data->{'WEB_DIR'}/backups none bind,slave"
            );
        }
    );

    iMSCP::EventManager->getInstance()->register(
        'beforeHttpdDelDmn',
        sub {
            my $data = shift;

            return 0 unless $data->{'DOMAIN_TYPE'} eq 'dmn';

            my $fsFile = "$data->{'WEB_DIR'}/backups";
            my $rs = removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
            $rs ||= umount( $fsFile );
            return $rs if $rs;

            local $@;
            eval { iMSCP::Dir->new( dirname => "$STORAGE_ROOT_PATH/$data->{'DOMAIN_NAME'}" )->remove(); };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            0;
        }
    );
}

1;
__END__
