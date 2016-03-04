#ifndef _SEND_LINE_H
#define _SEND_LINE_H

#include <errno.h>
#include <string.h>
#include <stdlib.h>
#include "defs.h"

extern char *message(int message_number);
extern void say(char *format, char *message);
extern int sendData(int fd, char *src, size_t n);

int sendLine(int fd, char *src, size_t len);

#endif
