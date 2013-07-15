#ifndef _LR_SYNTAX_H

#define _LR_SYNTAX_H

#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <unistd.h>
#include <errno.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <sys/param.h>
#include <time.h>
#include <sys/time.h>

#if defined(__OpenBSD__) || defined(__FreeBSD__)
#include <sys/proc.h>
#else
#include <sys/procfs.h>
#endif

#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
int readlink(char *pathname, char *buf, int bufsize);
#elif defined(__FreeBSD__)
ssize_t readlink(const char * __restrict, char * __restrict, size_t);
#else
int readlink(const char *pathname, char *buf, int bufsize);
#endif

#include "defs.h"

extern char *message(int message_number);
extern void say(char *format, char *message);
extern int sendLine(int fd, char *src, size_t len);

int lrSyntax(int fd, char *buffer);

#endif
