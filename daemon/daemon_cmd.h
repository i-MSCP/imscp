#ifndef _DAEMON_CMD_H
#define _DAEMON_CMD_H

#define _POSIX_C_SOURCE 200809L

#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <libgen.h>
#include "daemon_globals.h"

int helo_command(int fd);
int helo_syntax(int fd, char *buffer);
int bye_command(int fd, char *msg);
int backend_command(int fd, char *msg);

extern char *backendscriptpath;
extern char *message(int message_number);
extern int receive_line(int fd, char *dest, size_t n);
extern int send_line(int fd, char *src, size_t len);
extern void say(char *format, char *message);

#endif
