#ifndef _LR_CMD_H

#define _LR_CMD_H

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#include <sys/types.h>

#include <stdlib.h>

/* memset() stuff; */

#include <string.h>

typedef struct {

    char ip[MAX_MSG_SIZE];

    char host[MAX_MSG_SIZE];

    /*
     Request data.
     */

    char rd[MAX_MSG_SIZE];

    /*
     Status data.
     */

    char sd[MAX_MSG_SIZE];

} license_data_type;

extern int recv_line(int fd, char *dest, size_t n);

extern int lr_syntax(int fd, license_data_type *ld, char *buff);

int lr_cmd(int fd, license_data_type *ld);

#else
#
#endif
