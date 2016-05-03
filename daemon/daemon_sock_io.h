#ifndef _DAEMON_SOCK_IO_H
#define _DAEMON_SOCK_IO_H

#include <errno.h>
#include <string.h>
#include <unistd.h>

#include "daemon_globals.h"

int read_data(int sockfd, char *buffer, size_t n);
int read_line(int sockfd, char *buffer, size_t n);
int write_data(int sockfd, char *src, size_t n);
int write_line(int sockfd, char *src, size_t n);

extern char *message(int message_number);
extern void say(char *format, char *message);

#endif
