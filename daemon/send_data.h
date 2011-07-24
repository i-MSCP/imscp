#ifndef _SEND_DATA_H

#define _SEND_DATA_H

#include "defs.h"

#include <sys/types.h>

#include <unistd.h>

#include <errno.h>

extern char *message(int message_number);

extern void say(char *format, char *message);

int send_data(int fd, char *src, size_t n);

#endif
