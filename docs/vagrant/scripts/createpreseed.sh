#!/bin/sh
cd /vagrant/
head -n -1 docs/preseed.pl > /tmp/preseed.pl
echo "\$main::questions{'SERVER_HOSTNAME'} = 'vagrant.i-mscp.net';" >> /tmp/preseed.pl
echo "\$main::questions{'BASE_SERVER_VHOST'} = 'panel.vagrant.i-mscp.net';" >> /tmp/preseed.pl
echo "use iMSCP::Net;" >> /tmp/preseed.pl
echo "my \$ips = iMSCP::Net->getInstance();" >> /tmp/preseed.pl
echo "my @serverIps = \$ips->getAddresses();" >> /tmp/preseed.pl
echo "while (\$ips->getAddrVersion(@serverIps[0]) eq 'ipv6') { shift(@serverIps); }" >> /tmp/preseed.pl
echo "\$main::questions{'BASE_SERVER_IP'} = \$serverIps[0];" >> /tmp/preseed.pl
echo "\$main::questions{'BASE_SERVER_PUBLIC_IP'} = \$serverIps[0];" >> /tmp/preseed.pl
echo "print \"Server IP set to: \$serverIps[0]\";" >> /tmp/preseed.pl
echo "" >> /tmp/preseed.pl
echo "1;" >> /tmp/preseed.pl
