#ifndef _DAEMON_RECEIVE_H
#define _DAEMON_RECEIVE_H

#include <sys/types.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include "daemon_globals.h"

int receive_data(int fd, char *dest, size_t n);
int receive_line(int fd, char *dest, size_t n);

extern char *message(int message_number);
extern void say(char *format, char *message);

#endif
