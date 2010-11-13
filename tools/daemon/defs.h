#ifndef _DEFS_H

#define _DEFS_H

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
 Max length of transferred messages.
 */

#define MAX_MSG_SIZE        	    1026

/*
 Common Message Codes.
 */

#define NO_ERROR                0

#define MSG_WELCOME             10001
#define MSG_WELCOME_TXT	            "250 OK i-MSCP Daemon v1.1 Welcomes You!\r\n"
#define MSG_DAEMON_VER          10002
#define MSG_DAEMON_VER_TXT          "i-MSCP daemon v1.1 started!"
#define MSG_DAEMON_NAME         10003
#define MSG_DAEMON_NAME_TXT         "imscp_daemon"
#define MSG_ERROR_LISTEN        10004
#define MSG_ERROR_LISTEN_TXT        "listen() error: %s"
#define MSG_SIG_CHLD            10005
#define MSG_SIG_CHLD_TXT            "child %s terminated!"
#define MSG_SIG_PIPE            10006
#define MSG_SIG_PIPE_TXT            "Aeee! SIG_PIPE was received! Will we survive?"
#define MSG_ERROR_EINTR         10007
#define MSG_ERROR_EINTR_TXT         "EINTR was received! continue;"
#define MSG_ERROR_ACCEPT        10008
#define MSG_ERROR_ACCEPT_TXT        "accept() error: %s"
#define MSG_START_CHILD         10009
#define MSG_START_CHILD_TXT         "child %s started!"
#define MSG_ERROR_SOCKET_WR     10010
#define MSG_ERROR_SOCKET_WR_TXT     "send_line(): socket write error: %s"
#define MSG_BYTES_WRITTEN       10011
#define MSG_BYTES_WRITTEN_TXT       "send_line(): %s byte(s) successfully written!"
#define MSG_ERROR_SOCKET_RD     10012
#define MSG_ERROR_SOCKET_RD_TXT     "read_line(): socket read error: %s"
#define MSG_ERROR_SOCKET_EOF    10013
#define MSG_ERROR_SOCKET_EOF_TXT    "read_line(): socket EOF! other end closed the connection!"
#define MSG_BYTES_READ          10014
#define MSG_BYTES_READ_TXT          "read_line(): %s byte(s) successfully read!"
#define MSG_HELO_CMD            10015
#define MSG_HELO_CMD_TXT            "helo "
#define MSG_BAD_SYNTAX          10016
#define MSG_BAD_SYNTAX_TXT          "999 ERR Incorrect Syntax !\r\n"
#define MSG_CMD_OK              10017
#define MSG_CMD_OK_TXT              "250 OK "
#define MSG_BYE_CMD             10018
#define MSG_BYE_CMD_TXT             "bye"
#define MSG_EQ_CMD              10019
#define MSG_EQ_CMD_TXT              "execute query"
#define MSG_CONF_FILE           10020
#define MSG_CONF_FILE_TXT           "i-MSCP configuration file not found!"
#define MSG_MISSING_REG_DATA    10021
#define MSG_MISSING_REG_DATA_TXT    "i-MSCP data cannot be found in the config file!"
#define MSG_ERROR_BIND          10022
#define MSG_ERROR_BIND_TXT          "bind() error: %s! Please check for an other running daemon!"

#define MSG_MAX_COUNT           22

extern char *messages_array[][1];

#endif
