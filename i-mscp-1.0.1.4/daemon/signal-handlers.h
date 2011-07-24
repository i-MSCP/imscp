#ifndef _SIGNAL_HANDLERS_H

#define _SIGNAL_HANDLERS_H

#include "defs.h"

#include <sys/types.h>

#include <sys/wait.h>

#include <stdlib.h>

#include <stdio.h>

#include <string.h>

#if defined(__OpenBSD__) || defined(__FreeBSD__)

#define SIGCHLD			13

#define SIGPIPE 		17

#endif

extern void say(char *format, char *message);

extern char *message(int message_number);

void sig_child (int signo);

void sig_pipe(int signo);

#endif
