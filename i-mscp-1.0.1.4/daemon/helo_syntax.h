#ifndef _HELO_SYNTAX_H

#define _HELO_SYNTAX_H

#include "defs.h"

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

#include <stdio.h>

extern char client_ip[MAX_MSG_SIZE];

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_line(int fd, char *src, size_t len);

int helo_syntax(int fd, char *buff);

#endif
