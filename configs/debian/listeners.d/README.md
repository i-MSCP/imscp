i-MSCP listener files
=====================

### Introduction

This directory contains the listener files which are responsible to register your own event listeners on the i-MSCP event
manager. Any listener file found in this directory is loaded automatically by i-MSCP at runtime.

A listener file is a simple Perl script which defines one or many event listeners and register them on the i-MSCP event
manager. When the events on which the listeners are listening are triggered, the listeners are automatically run.

### Listener file namespaces

Each listener file must declare its own namespace such as

```
Listener::Postfix::Smarthost
```

This allow to not pollute other symbol tables.

### Listener file naming convention

Each listener file must be named using the following naming convention

```
<nn>_<namespace>.pl
```

where

* **nn** is a number which gives the listener file priority
* **namespace** is the lowercase namespace, stripped of the prefix and where any double colon is replaced by an underscore

In the example above, the filename would be **00_postfix_smarthost.pl**

### Listener sample

Listener sample ( 00_sample.pl ):

```perl
#!/usr/bin/perl

Package Listener::Sample;

use iMSCP::Debug;
use iMSCP::EventManager;

# Listener which simply cancel installation
sub sample
{
    warning("Installation has been cancelled by an event listener.");
    exit 0;
}

iMSCP::EventManager->getInstance()->register('preInstall', \&sample);

1;
```
