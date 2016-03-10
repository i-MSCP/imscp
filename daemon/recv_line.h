#ifndef _RECV_LINE_H
#define _RECV_LINE_H

#include <errno.h>
#include <string.h>
#include <stdlib.h>
#include "defs.h"

extern char *message(int message_number);
extern void say(char *format, char *message);
extern int receiveData(int fd, char *dest, size_t n);

int receiveLine(int fd, char *dest, size_t n);

#endif
