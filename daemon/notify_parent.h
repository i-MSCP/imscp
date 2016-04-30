#ifndef _NOTIFY_PARENT_H
#define _NOTIFY_PARENT_H

#include <stdlib.h>
#include <unistd.h>

int notification_pipe[2];

void notify_parent(int status);

extern void say(char *format, char *message);

#endif
