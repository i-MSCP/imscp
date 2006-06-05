#ifndef _RECV_LINE_H

#define _RECV_LINE_H

#include <errno.h>

#include <string.h>

#include <stdlib.h>

#include <stdio.h>

#define NO_ERROR                0

#define MSG_ERROR_SOCKET_RD     10012

#define MSG_ERROR_SOCKET_EOF    10013

#define MSG_BYTES_READ          10014

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int receive_data(int fd, char *dest, size_t n);

int recv_line(int fd, char *dest, size_t n);

#else
#
#endif
