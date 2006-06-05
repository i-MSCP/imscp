#ifndef _DEAMON_INIT_H

#define _DAEMON_INIT_H

/* signal() stiff */

#include <signal.h>

/* syslog() stuff */

#include <syslog.h>

/* fork() stuff */

#include <sys/types.h>
#include <unistd.h>

/* umask() stuff */

#include <sys/stat.h>

/* exit() stuff */

#include <stdlib.h>

void daemon_init(const char *pname, int facility);

#else
#
#endif
