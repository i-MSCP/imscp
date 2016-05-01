#!/usr/bin/make -f

# i-MSCP - internet Multi Server Control Panel
#
# Copyright (C) 2010-2015 by internet Multi Server Control Panel - http://i-mscp.net
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
# or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
# for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>
#
# The Original Code is "i-MSCP internet Multi Server Control Panel".
#
# The Initial Developer of the Original Code is i-MSCP Team.
# Portions created by Initial Developer are Copyright (C) 2010 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# @category     i-MSCP
# @copyright    2010-2016 by i-MSCP | http://i-mscp.net
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/ GPL v2

EXEC = imscp_daemon
CC = gcc -ansi -Wall -Wstrict-prototypes -pedantic $(CFLAGS)
CFLAGS = -g -O2

OBJECTS = daemon_cmd.o daemon_conn.o daemon_init.o daemon_main.o daemon_msg.o\
          daemon_notify.o daemon_receive.o daemon_send.o daemon_signals.o

$(EXEC): clean $(OBJECTS)
	$(CC) $(CFLAGS) $(OBJECTS) -o $@
	strip -s $@
	@echo "Compilation done"

%.o : %.c
	$(CC) -c $(CFLAGS) $<

clean:
	rm -f *.o *~ $(EXEC)

.PHONY: clean
