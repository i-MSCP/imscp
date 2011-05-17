#ifndef _RECEIVE_DATA_H

#define _RECEIVE_DATA_H

#include "defs.h"

#include <sys/types.h>

#include <unistd.h>

#include <errno.h>

extern char *message(int message_number);

extern void say(char *format, char *message);

int receive_data(int fd, char *dest, size_t n);

#endif
