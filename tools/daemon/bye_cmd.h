#ifndef _BYE_CMD_H

#define _BYE_CMD_H

#include "defs.h"

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

extern int recv_line(int fd, char *dest, size_t n);

extern int bye_syntax(int fd, char *buff);

int bye_cmd(int fd, char *msg);

#endif
