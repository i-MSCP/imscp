#ifndef _BYE_SYNTAX_H

#define _BYE_SYNTAX_H

#include "defs.h"

#include <stdlib.h>

#include <string.h>

extern char *message(int message_number);

extern int send_line(int fd, char *src, size_t len);

int bye_syntax(int fd, char *buff);

#endif
