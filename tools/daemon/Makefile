# VHCS(tm) - Virtual Hosting Control System
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
# The VHCS Home Page is at:
#
#    http://www.vhcs.net


OBJS = vhcs_daemon.o message.o daemon_init.o say.o signal-handlers.o\
receive_data.o send_data.o take_connection.o send_line.o recv_line.o\
helo_cmd.o helo_syntax.o lr_cmd.o lr_syntax.o bye_cmd.o bye_syntax.o\
query_license_server.o

BUILD = gcc -O2 -ansi -Wall -Wstrict-prototypes -pedantic -o

COMPILE = gcc -O2 -ansi -Wall -Wstrict-prototypes -pedantic -c -o

STRIP = strip -s

vhcs2_daemon: $(OBJS)
	$(BUILD) vhcs2_daemon $(OBJS)
	$(STRIP) vhcs2_daemon
        
vhcs_daemon.o: vhcs_daemon.c defs.h
	$(COMPILE) vhcs_daemon.o vhcs_daemon.c

daemon_init.o: daemon_init.c daemon_init.h
	$(COMPILE) daemon_init.o daemon_init.c
        
message.o: message.c message.h 
	$(COMPILE) message.o message.c
        
say.o: say.c say.h
	$(COMPILE) say.o say.c

signal-handlers.o: signal-handlers.c signal-handlers.h
	$(COMPILE) signal-handlers.o signal-handlers.c
        
receive_data.o: receive_data.c receive_data.h
	$(COMPILE) receive_data.o receive_data.c

send_data.o: send_data.c send_data.h
	$(COMPILE) send_data.o send_data.c

take_connection.o: take_connection.c take_connection.h
	$(COMPILE) take_connection.o take_connection.c

send_line.o: send_line.c send_line.h
	$(COMPILE) send_line.o send_line.c

recv_line.o: recv_line.c recv_line.h
	$(COMPILE) recv_line.o recv_line.c

helo_cmd.o: helo_cmd.c helo_cmd.h
	$(COMPILE) helo_cmd.o helo_cmd.c

helo_syntax.o: helo_syntax.c helo_syntax.h
	$(COMPILE) helo_syntax.o helo_syntax.c

lr_cmd.o: lr_cmd.c lr_cmd.h
	$(COMPILE) lr_cmd.o lr_cmd.c

lr_syntax.o: lr_syntax.c lr_syntax.h
	$(COMPILE) lr_syntax.o lr_syntax.c

bye_cmd.o: bye_cmd.c bye_cmd.h
	$(COMPILE) bye_cmd.o bye_cmd.c

bye_syntax.o: bye_syntax.c bye_syntax.h
	$(COMPILE) bye_syntax.o bye_syntax.c

query_license_server.o: query_license_server.c query_license_server.h
	$(COMPILE) query_license_server.o query_license_server.c

uninstall: clean

clean:
	rm -rf *~
	rm -rf *.o
	rm -rf vhcs2_daemon

