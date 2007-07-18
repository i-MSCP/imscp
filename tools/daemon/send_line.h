#ifndef _SEND_LINE_H

#define _SEND_LINE_H

#include "defs.h"

#include <errno.h>

#include <stdio.h>

#include <string.h>

#include <stdlib.h>

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_data(int fd, char *src, size_t n);

int send_line(int fd, char *src, size_t len);

#endif
