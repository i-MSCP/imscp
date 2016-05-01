#ifndef _DAEMON_SEND_H
#define _DAEMON_SEND_H

#include <stdlib.h>
#include <sys/types.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include "daemon_globals.h"

int send_data(int fd, char *src, size_t n);
int send_line(int fd, char *src, size_t len);

extern char *message(int message_number);
extern void say(char *format, char *message);

#endif
