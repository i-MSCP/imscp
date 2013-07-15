#ifndef _SIGNAL_HANDLERS_H

#define _SIGNAL_HANDLERS_H

#include <sys/types.h>
#include <sys/wait.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>

#include "defs.h"

#if defined(__OpenBSD__) || defined(__FreeBSD__)
#define SIGCHLD     13
#define SIGPIPE     17
#endif

extern void say(char *format, char *message);
extern char *message(int message_number);

void sigChild (int signo);
void sigPipe(int signo);

#endif
