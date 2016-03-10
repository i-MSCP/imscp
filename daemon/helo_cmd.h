#ifndef _HELO_CMD_H
#define _HELO_CMD_H

#include <stdlib.h>
#include <string.h>
#include "defs.h"

extern int receiveLine(int fd, char *dest, size_t n);
extern int heloSyntax(int fd, char *buffer);

int heloCommand(int fd);

#endif
