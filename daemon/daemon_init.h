#ifndef _DEAMON_INIT_H

#define _DAEMON_INIT_H

#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <syslog.h>
#include <sys/types.h>
#include <sys/stat.h>

#include "defs.h"

extern char *message(int message_number);
extern void say(char *format, char *message);

void daemonInit(char *pidfile);

#endif
