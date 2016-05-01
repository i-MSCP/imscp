#ifndef _DAEMON_SIGNALS_H
#define _DAEMON_SIGNALS_H

#include <sys/types.h>
#include <sys/wait.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include "daemon_globals.h"

void handle_signal(int signo);

extern void say(char *format, char *message);
extern char *message(int message_number);

#endif
