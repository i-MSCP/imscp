# ISPCP(tm) - Virtual Hosting Control System
# Copyright (c) 2001-2004 by moleSoftware GmbH
# http://www.molesoftware.com
#
#
# License:
#    This program is free software; you can redistribute it and/or
#    modify it under the terms of the MPL Mozilla Public License
#    as published by the Free Software Foundation; either version 1.1    
#    of the License, or (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
#    MPL Mozilla Public License for more details.
#    
#    You may have received a copy of the MPL Mozilla Public License
#    along with this program.
#    
#    An on-line copy of the MPL Mozilla Public License can be found
#    http://www.mozilla.org/MPL/MPL-1.1.html
#
#
# The ISPCP Home Page is at:
#
#    http://www.isp-control.net


OBJS = ispcp_daemon.o message.o daemon_init.o say.o signal-handlers.o\
receive_data.o send_data.o take_connection.o send_line.o recv_line.o\
helo_cmd.o helo_syntax.o lr_cmd.o lr_syntax.o bye_cmd.o bye_syntax.o

BUILD = gcc -O3 -ansi -Wall -Wstrict-prototypes -pedantic -o

COMPILE = gcc -O3 -ansi -Wall -Wstrict-prototypes -pedantic -c -o

STRIP = strip -s

ispcp_daemon: $(OBJS)
	$(BUILD) ispcp_daemon $(OBJS)
	$(STRIP) ispcp_daemon
        
%.o: %.c %.h
	$(COMPILE) $@ $*.c

uninstall: clean

clean:
	rm -rf *~
	rm -rf *.o
	rm -rf ispcp_daemon

.PHONY: clean uninstall
