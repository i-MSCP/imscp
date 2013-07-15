#ifndef _HELO_SYNTAX_H

#define _HELO_SYNTAX_H

#include <stdlib.h>
#include <string.h>

#include "defs.h"

extern char client_ip[MAX_MSG_SIZE];

extern char *message(int message_number);
extern int sendLine(int fd, char *src, size_t len);

int heloSyntax(int fd, char *buffer);

#endif
