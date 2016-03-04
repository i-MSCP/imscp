#ifndef _SEND_DATA_H
#define _SEND_DATA_H

#include <sys/types.h>
#include <unistd.h>
#include <errno.h>
#include "defs.h"

int sendData(int fd, char *src, size_t n);

#endif
