
export

COMPILE=gcc -c -ansi -Wall -Wstrict-prototypes -pedantic

COMPILE_MCRYPT=gcc -g3 -ansi -Wall -Wstrict-prototypes -pedantic -lmcrypt

BUILD=gcc -o

BUILD_MCRYPT=gcc -lmcrypt -o

gen-keys: gen-keys.c

	$(COMPILE) gen-keys.c
	$(BUILD) gen-keys gen-keys.o
	strip -v gen-keys
#	./gen-keys 32 16 > keys.txt
	chown root:root ./rpl.pl ./gen-keys
	chmod 0700 ./rpl.pl ./gen-keys
	./rpl.pl $(SYSTEM_ROOT)/gui/include/ispcp-db-keys.php $(SYSTEM_ROOT)/engine/ispcp-db-keys.pl $(SYSTEM_ROOT)/engine/messager/ispcp-db-keys.pl
        
install: gen-keys 

all: install

uninstall:

	rm -rf ./gen-keys
	rm -rf ./*.o
	rm -rf ./*~
	rm -rf ./keys.txt

clean: uninstall

