#ifndef _SEND_LINE_H

#define _SEND_LINE_H

#include <errno.h>

#include <stdio.h>

#include <string.h>

#include <stdlib.h>

#define NO_ERROR                0

#define MSG_ERROR_SOCKET_WR     10010

#define MSG_BYTES_WRITTEN       10011

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_data(int fd, char *src, size_t n);

int send_line(int fd, char *src, size_t len);

#else
#
#endif
