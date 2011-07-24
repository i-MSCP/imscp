#ifndef _RECV_LINE_H

#define _RECV_LINE_H

#include "defs.h"

#include <errno.h>

#include <string.h>

#include <stdlib.h>

#include <stdio.h>

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int receive_data(int fd, char *dest, size_t n);

int recv_line(int fd, char *dest, size_t n);

#endif
