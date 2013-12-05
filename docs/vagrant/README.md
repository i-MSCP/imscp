## Vagrant Files

This directory contains Vagrantfiles that can be used to setup and quickly test i-MSCP using [vagrant](http://www.vagrantup.com/).

To get started, it is best to link the Vagrantfile to your the base directory of your development enviroment.

	cd ../..
	ln -s docs/vagrant/Vagrantfile Vagrantfile

Then to start the VM and immediately start the i-MSCP install and get a root ssh login...

	vagrant up
	vagrant ssh
	sudo -s

Thanks, happy vagranting! This process may change over time, so check back if things stop working for some reason
