#ifndef _BYE_CMD_H

#define _BYE_CMD_H

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

extern int recv_line(int fd, char *dest, size_t n);

extern int bye_syntax(int fd, char *buff);

int bye_cmd(int fd);

#else
#
#endif
