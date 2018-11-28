#ifndef _DAEMON_SIGNALS_H
#define _DAEMON_SIGNALS_H

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/signal.h>

#include "daemon_globals.h"

void handle_signal(int signo);

extern void say(char *format, char *message);
extern char *message(int message_number);

#endif
