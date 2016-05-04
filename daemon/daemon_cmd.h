#ifndef _DAEMON_CMD_H
#define _DAEMON_CMD_H

#define _POSIX_C_SOURCE 200809L

#include <errno.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

#include "daemon_globals.h"

int helo_command(int sockfd, char *buffer, char *cliaddr);
int backend_command(int sockfd, char *buffer);
int bye_command(int sockfd, char *buffer);

extern char *backendscriptpath;
extern char *backendscriptname;
extern char *message(int message_number);
extern int read_line(int sockfd, char *buffer, size_t n);
extern int write_line(int sockfd, char *src, size_t n);
extern void say(char *format, char *message);

#endif
