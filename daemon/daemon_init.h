#ifndef _DEAMON_INIT_H
#define _DAEMON_INIT_H

#define _XOPEN_SOURCE

#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/signal.h>
#include <sys/time.h>
#include <syslog.h>
#include <errno.h>
#include "daemon_globals.h"

void daemon_init(void);

extern int notify_pipe[2];
extern void notify(int status);
extern char *message(int message_number);

#endif
