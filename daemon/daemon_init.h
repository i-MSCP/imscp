#ifndef _DEAMON_INIT_H
#define _DAEMON_INIT_H

#include <stdio.h>
#include <stdlib.h>
#include <sys/signal.h>
#include <sys/stat.h>
#include <sys/time.h>
#include <sys/types.h>
#include <syslog.h>
#include <unistd.h>

#include "daemon_globals.h"

void daemon_init(void);

extern int notify_pipe[2];
extern void notify(int status);
extern char *message(int message_number);

#endif
