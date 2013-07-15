#ifndef _DEAMON_INIT_H

#define _DAEMON_INIT_H

#include <stdlib.h>
#include <unistd.h>
#include <signal.h>
#include <syslog.h>
#include <sys/types.h>
#include <sys/stat.h>

void daemonInit(const char *pname, int facility);

#endif
