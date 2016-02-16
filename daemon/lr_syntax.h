#ifndef _LR_SYNTAX_H

#define _LR_SYNTAX_H
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <libgen.h>

#include "defs.h"

extern char *backendscriptpath;

extern char *message(int message_number);
extern int sendLine(int fd, char *src, size_t len);

int lrSyntax(int fd, char *buffer);

#endif
