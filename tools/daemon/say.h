#ifndef _SAY_H

#define _SAY_H

/*
 syslog daemon options.
 */

#define SYSLOG_OPTIONS              LOG_PID

#define SYSLOG_FACILITY             LOG_DAEMON

#define SYSLOG_MSG_PRIORITY         LOG_NOTICE

#include <syslog.h>

void say(char *format, char *message);

#else
#
#endif
