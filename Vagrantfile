# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  # All Vagrant configuration is done here. The most common configuration
  # options are documented and commented below. For a complete reference,
  # please see the online documentation at vagrantup.com.

  # Every Vagrant virtual environment requires a box to build off of.
  #config.vm.box = "precise32"

  # The url from where the 'config.vm.box' box will be fetched if it
  # doesn't already exist on the user's system.
  #config.vm.box_url = "http://files.vagrantup.com/precise32.box"

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  # config.vm.network :forwarded_port, guest: 80, host: 8080
  # config.vm.network :forwarded_port, guest: 443, host: 8443

  # Provisioning 

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  # config.vm.network :private_network, ip: "192.168.56.10"
  # config.vm.network :private_network

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  config.vm.network :public_network



  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.

  # Drop the memory requirement to 256 for now.
  config.vm.provider :virtualbox do |vb, override|
    vb.customize ["modifyvm", :id, "--memory", "256"]
    override.vm.box = "precise32"
    override.vm.box_url = "http://files.vagrantup.com/precise32.box"
  end

  config.vm.provider :lxc do |lxc, override|
    lxc.cgroup.memory.limit_in_bytes='256M'
    override.vm.box = "precise64"
    override.vm.box_url = "http://bit.ly/vagrant-lxc-precise64-2013-10-23"
  end

  # Provision i-MSCP
  $script = <<SCRIPT
echo Setting up default locale...
apt-get update
apt-get install -y language-pack-en libdata-validate-ip-perl
echo 'dictionaries-common dictionaries-common/default-ispell string american (American English)' | debconf-set-selections
echo 'dictionaries-common dictionaries-common/default-wordlist string american (American English)' | debconf-set-selections

echo Setting up i-MSCP with defaults from docs/preseed.pl ...
cd /vagrant/
head -n -1 docs/preseed.pl > /tmp/preseed.pl
echo "\\\$main::questions{'SERVER_HOSTNAME'} = 'vagrant.i-mscp.net';" >> /tmp/preseed.pl
echo "\\\$main::questions{'BASE_SERVER_VHOST'} = 'panel.vagrant.i-mscp.net';" >> /tmp/preseed.pl
echo "use iMSCP::IP;" >> /tmp/preseed.pl
echo "my \\\$ips = iMSCP::IP->new();" >> /tmp/preseed.pl
echo "my \\\$rs = \\\$ips->loadIPs();" >> /tmp/preseed.pl
echo "my @serverIps = \\\$ips->getIPs();" >> /tmp/preseed.pl
echo "while (@serverIps[0] eq '127.0.0.1' || @serverIps[0] eq \\\$ips->normalize('::1')) { shift(@serverIps); }" >> /tmp/preseed.pl
echo "print \\"Server IP: \\";" >> /tmp/preseed.pl
echo "print @serverIps[0];" >> /tmp/preseed.pl
echo "\\\$main::questions{'BASE_SERVER_IP'} = @serverIps[0];" >> /tmp/preseed.pl
echo "" >> /tmp/preseed.pl
echo "1;" >> /tmp/preseed.pl
./imscp-autoinstall --debug --noprompt --preseed /tmp/preseed.pl
SCRIPT

  config.vm.provision "shell", inline: $script
end
