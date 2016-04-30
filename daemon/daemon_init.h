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
#include "defs.h"

void daemonInit(void);

extern int notification_pipe[2];

extern void notify_parent(int status);
extern char *message(int message_number);

#endif
