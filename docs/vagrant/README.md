## Vagrant Files

This directory contains Vagrantfiles which can be used to setup and quickly test i-MSCP using [vagrant](http://www.vagrantup.com/).

To get started, it is best to link the Vagrantfile to the base directory of your development environment.

	cd ../..
	ln -s docs/vagrant/Vagrantfile Vagrantfile

You must also fill-up the docs/preseed.pl file according your needs.

Then to start the VM and immediately start the i-MSCP install and get a root ssh login...

	vagrant up
	vagrant ssh
	sudo -s

Thanks, happy vagranting! This process may change over time, so check back if things stop working for some reason.
