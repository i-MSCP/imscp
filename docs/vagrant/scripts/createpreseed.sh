#!/bin/sh
cd /vagrant/
head -n -1 docs/preseed.pl > /tmp/preseed.pl
echo "\$main::questions{'SERVER_HOSTNAME'} = 'vagrant.i-mscp.net';" >> /tmp/preseed.pl
echo "\$main::questions{'BASE_SERVER_VHOST'} = 'panel.vagrant.i-mscp.net';" >> /tmp/preseed.pl
echo "use iMSCP::IP;" >> /tmp/preseed.pl
echo "my \$ips = iMSCP::IP->new();" >> /tmp/preseed.pl
echo "my \$rs = \$ips->loadIPs();" >> /tmp/preseed.pl
echo "my @serverIps = \$ips->getIPs();" >> /tmp/preseed.pl
echo "while (@serverIps[0] eq '127.0.0.1' || @serverIps[0] eq \$ips->normalize('::1')) { shift(@serverIps); }" >> /tmp/preseed.pl
echo "print \"Server IP: \";" >> /tmp/preseed.pl
echo "print @serverIps[0];" >> /tmp/preseed.pl
echo "\$main::questions{'BASE_SERVER_IP'} = @serverIps[0];" >> /tmp/preseed.pl
echo "" >> /tmp/preseed.pl
echo "1;" >> /tmp/preseed.pl
