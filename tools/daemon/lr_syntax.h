#ifndef _LR_SYNTAX_H

#define _LR_SYNTAX_H

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#define MSG_BAD_SYNTAX          10016

#define MSG_CMD_OK              10017

#define MSG_LR_CMD              10018

#define MSG_LS_CMD              10018

#define MSG_EQ_CMD              10021

#define MSG_LICENSE_ERROR       10027

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

#include <stdio.h>

#include <unistd.h>

/*
 timestamp generation includes
 */

#include <time.h>

#include <sys/time.h>

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

#if defined(__OpenBSD__) || defined(__FreeBSD__)

#define QUERY_CMD "/var/www/vhcs2/engine/vhcs2-rqst-mngr"

#endif

#define LOG_DIR "/var/log/vhcs2"

#define STDOUT_LOG "vhcs2_daemon-stdout-log"

#define STDERR_LOG "vhcs2_daemon-stderr-log"

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_line(int fd, char *src, size_t len);

int lr_syntax(int fd, license_data_type *ld, char *buff);

extern char license_status [MAX_MSG_SIZE];

#else
#
#endif
