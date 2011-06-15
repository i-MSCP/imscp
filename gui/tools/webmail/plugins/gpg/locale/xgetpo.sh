#!/bin/sh

cd ..
mv locale/gpg.pot locale/gpg.pot.bak

xgettext --keyword=_ -keyword=N_ --default-domain=gpg -s -C *.php help/*.php modules/*.php modules/*.mod --output=locale/gpg.pot
#xgettext --keyword=_ -keyword=N_ --default-domain=gpg -s -C *.php --output=locale/gpg.pot
#xgettext --keyword=_ -keyword=N_ --default-domain=gpg -s -C -j modules/*.mod --output=locale/gpg.pot
#xgettext --keyword=_ -keyword=N_ --default-domain=gpg -s -C -j modules/*.php --output=locale/gpg.pot
#xgettext --keyword=_ -keyword=N_ --default-domain=gpg -s -C -j help/*.php --output=locale/gpg.pot

cd locale
