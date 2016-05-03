#ifndef _DAEMON_NOTIFY_H
#define _DAEMON_NOTIFY_H

#define _POSIX_C_SOURCE 200809L

#include <errno.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>

int notify_pipe[2];
void notify(int status);

extern void say(char *format, char *message);

#endif
