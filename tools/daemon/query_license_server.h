#ifndef _QLS_CMD_H

#define _QLS_CMD_H

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#define MSG_LR_CMD              10018

#define MSG_ERROR_SOCKET        10024

#define MSG_ERROR_CONNECT       10025

#define MSG_HELO_CMD            10015

#define MSG_CMD_OK              10017

#define MSG_LSERVER_WELCOME     10026

#define MSG_BYE_CMD             10019

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

#include <arpa/inet.h>

#include <errno.h>


/*
 Will we log it ?
 */

#include <syslog.h>

/*
 Socket manipulation functions.
 */

#include <sys/stat.h>
#include <sys/types.h>
#include <sys/socket.h>

/*
 Internet Socket Address Structure IPv4.
 */

#include <netinet/in.h>

/*
 Something more about fork.
 */

#include <unistd.h>

/*
 Socket timeouts.
 */

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

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int recv_line(int fd, char *dest, size_t n);

extern int send_line(int fd, char *src, size_t len);

extern int query_license_server_syntax(int fd, license_data_type *ld, char *buff);

extern char product_number [MAX_MSG_SIZE];

extern char product_version [MAX_MSG_SIZE];

extern char license_key [MAX_MSG_SIZE];

extern char license_status [MAX_MSG_SIZE];

int query_license_server(void);

#else
#
#endif
