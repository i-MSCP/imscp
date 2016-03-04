#ifndef _RECEIVE_DATA_H
#define _RECEIVE_DATA_H

#include <sys/types.h>
#include <unistd.h>
#include <errno.h>
#include "defs.h"

int receiveData(int fd, char *dest, size_t n);

#endif
