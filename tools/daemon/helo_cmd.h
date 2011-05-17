#ifndef _HELO_CMD_H

#define _HELO_CMD_H

#include "defs.h"

#include <sys/types.h>

#include <stdlib.h>

/* memset() struff; */

#include <string.h>

extern void say(char *format, char *message);

extern int recv_line(int fd, char *dest, size_t n);

extern int helo_syntax(int fd, char *buff);

int helo_cmd(int fd);

#endif
