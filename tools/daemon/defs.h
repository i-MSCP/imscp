#ifndef _DEFS_H

#define _DEFS_H

/* Needed headers. */

/*
 calloc, malloc, free, realloc - Allocate and free dynamic memory
 */

#include <stdlib.h>

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
 Socket timeouts.
 */

#include <sys/time.h>

/*
 Signal handling.
 */

#include <signal.h>

/*
 String manipulation.
 */

#include <string.h>

/*
 Error handling.
 */

#include <errno.h>

/*
 Something more about fork.
 */

#include <unistd.h>

/*
 String manipulation.
 */

#include <stdio.h>

/*
 inet_ntop() function.
 */

#include <arpa/inet.h>

/*
 Predefined names.
 */

/*
 syslog daemon options.
 */

#define SYSLOG_OPTIONS              LOG_PID

#define SYSLOG_FACILITY             LOG_DAEMON

#define SYSLOG_MSG_PRIORITY         LOG_NOTICE

/*
 Common daemon parameters.
 */

#define SERVER_LISTEN_PORT          9876

#define MAX_LISTENQ                 256

/*
 Max length of transfferd messages.
 */

#define MAX_MSG_SIZE        	    1026

/*
 Common Message Codes.
 */

#define MSG_MAX_COUNT           28

#define MSG_WELCOME             10001

#define MSG_DAEMON_VER          10002

#define MSG_DAEMON_NAME         10003

#define MSG_ERROR_LISTEN        10004

#define MSG_SIG_CHLD            10005

#define MSG_SIG_PIPE            10006

#define MSG_ERROR_EINTR         10007

#define MSG_ERROR_ACCEPT        10008

#define MSG_START_CHILD         10009

#define MSG_ERROR_SOCKET_WR     10010

#define MSG_BYTES_WRITTEN       10011

#define MSG_ERROR_SOCKET_RD     10012

#define MSG_ERROR_SOCKET_EOF    10013

#define MSG_BYTES_READ          10014

#define MSG_HELO_CMD            10015

#define MSG_BAD_SYNTAX          10016

#define MSG_CMD_OK              10017

#define MSG_LR_CMD              10018

#define MSG_BYE_CMD             10019

#define MSG_LS_CMD              10018

#define MSG_EQ_CMD              10021

#define MSG_CONF_FILE           10022

#define MSG_MISSING_REG_DATA    10023

#define MSG_ERROR_SOCKET        10024

#define MSG_ERROR_CONNECT       10025

#define MSG_LSERVER_WELCOME     10026

#define MSG_LICENSE_ERROR       10027

#define MSG_ERROR_BIND          10028

/*
 Common Error Codes.
 */
#define NO_ERROR                0


/* Type definitions. */

typedef unsigned int word;


/* Global variables. */

char *messages_array[MSG_MAX_COUNT][1] = {
    {"250 OK moleSoftware VHCS2 Server Welcomes You !\r\n"},
    {"moleSoftware vhcs_daemon v2.02 started !"},
    {"vhcs2_daemon"},
    {"listen() error: %s"},
    {"child %s terminated !"},
    {"Aeee! SIG_PIPE was received ! Will we survive ?"},
    {"EINTR was received ! continue;"},
    {"accept() error: %s"},
    {"child %s started !"},
    {"send_line(): socket write error: %s"},
    {"send_line(): %s byte(s) successfully written !"},
    {"read_line(): socket read error: %s"},
    {"read_line(): socket EOF ! other end closed the connection !"},
    {"read_line(): %s byte(s) successfully read !"},
    {"helo "},
    {"999 ERR Incorrect Syntax !\r\n"},
    {"250 OK "},
    {"license request: "},
    {"bye"},
    {"license status"},
    {"execute query"},
    {"VHCS Pro configuration file not found !"},
    {"VHCS Pro license data cannot be found in the config file !"},
    {"Connect to license-server: socket() error [%s]!"},
    {"Connect to license-server: connect() error [%s]!"},
    {"250 OK moleSoftware Licnese Server Welcomes You !\r\n"},
    {"999 ERR License error !\r\n"},
    {"bind() error: %s ! \r\n Please check for another daemon runing !\r\n "}
};

char client_ip [MAX_MSG_SIZE];

/*
 BEGIN: vhcs_daemon variables
 */

char product_number [MAX_MSG_SIZE];

char product_version [MAX_MSG_SIZE];

char license_key [MAX_MSG_SIZE];

char license_status [MAX_MSG_SIZE];

/*
 END: vhcs_daemon variables
 */

struct timeval     *tv_rcv;

struct timeval     *tv_snd;

/* External functions. */

extern void daemon_init(const char *pname, int facility);

extern char *message(int message_number);

extern void say(char *format, char *message);

extern void sig_child (int signo);

extern void sig_pipe(int signo);

extern void take_connection(int sockfd);

extern int query_license_server(void);

#endif

