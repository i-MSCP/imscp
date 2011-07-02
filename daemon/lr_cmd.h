#ifndef _LR_CMD_H

#define _LR_CMD_H

#include "defs.h"

#include <sys/types.h>

#include <stdlib.h>

/* memset() stuff; */

#include <string.h>

extern int recv_line(int fd, char *dest, size_t n);

extern int lr_syntax(int fd, char *buff);

int lr_cmd(int fd, char *msg);

#endif
