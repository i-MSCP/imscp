#ifndef _BYE_SYNTAX_H

#define _BYE_SYNTAX_H

#include <stdlib.h>
#include <string.h>

#include "defs.h"

extern char *message(int message_number);
extern int sendLine(int fd, char *src, size_t len);

int byeSyntax(int fd, char *buffer);

#endif
