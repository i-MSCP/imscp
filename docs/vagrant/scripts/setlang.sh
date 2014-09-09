#!/bin/sh
DEBIAN_FRONTEND=noninteractive apt-get install -y language-pack-en
echo 'dictionaries-common dictionaries-common/default-ispell string american (American English)' | debconf-set-selections
echo 'dictionaries-common dictionaries-common/default-wordlist string american (American English)' | debconf-set-selections
