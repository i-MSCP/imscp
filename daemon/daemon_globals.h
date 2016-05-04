#ifndef _DAEMON_GLOBALS_H
#define _DAEMON_GLOBALS_H

/* Syslog daemon options */
#define SYSLOG_OPTIONS              LOG_PID
#define SYSLOG_FACILITY             LOG_DAEMON
#define SYSLOG_MSG_PRIORITY         LOG_NOTICE

/* Daemon parameters */
#define DAEMON_LISTEN_ADDR          INADDR_LOOPBACK
#define DAEMON_LISTEN_PORT          9876
#define DAEMON_MAX_LISTENQ          256

/* Max length for transferred messages */
#define MAX_MSG_SIZE                1026

/* Messages */
#define MSG_MAX_COUNT               21

#define MSG_WELCOME                 101
#define MSG_WELCOME_TXT             "i-MSCP Daemon v1.3.0\n"
#define MSG_DAEMON_STARTED          102
#define MSG_DAEMON_STARTED_TXT      "i-MSCP daemon v1.3.0 started."
#define MSG_DAEMON_NAME             103
#define MSG_DAEMON_NAME_TXT         "imscp_daemon"
#define MSG_ERROR_LISTEN            104
#define MSG_ERROR_LISTEN_TXT        "listen() error: %s"
#define MSG_SIG_PIPE                105
#define MSG_SIG_PIPE_TXT            "SIG_PIPE was received."
#define MSG_ERROR_ACCEPT            106
#define MSG_ERROR_ACCEPT_TXT        "accept() error: %s"
#define MSG_START_CHILD             107
#define MSG_START_CHILD_TXT         "child started."
#define MSG_END_CHILD               108
#define MSG_END_CHILD_TXT           "child ended."
#define MSG_ERROR_SOCKET_WR         109
#define MSG_ERROR_SOCKET_WR_TXT     "write_line(): socket write error: %s"
#define MSG_ERROR_SOCKET_RD         110
#define MSG_ERROR_SOCKET_RD_TXT     "read_line(): socket read error: %s"
#define MSG_ERROR_SOCKET_EOF        111
#define MSG_ERROR_SOCKET_EOF_TXT    "read_line(): connection closed by remote host."
#define MSG_HELO_CMD                112
#define MSG_HELO_CMD_TXT            "helo "
#define MSG_BAD_SYNTAX              113
#define MSG_BAD_SYNTAX_TXT          "999 Error: command not recognized\n"
#define MSG_CMD_OK                  114
#define MSG_CMD_OK_TXT              "250 "
#define MSG_BYE_CMD                 115
#define MSG_BYE_CMD_TXT             "bye"
#define MSG_EQ_CMD                  116
#define MSG_EQ_CMD_TXT              "execute query"
#define MSG_CMD_ANSWER              117
#define MSG_CMD_ANSWER_TXT          "Query is being processed\n"
#define MSG_ERROR_BIND              118
#define MSG_ERROR_BIND_TXT          "bind() error: %s"
#define MSG_ERROR_SOCKET_CREATE     119
#define MSG_ERROR_SOCKET_CREATE_TXT "socket() error: %s"
#define MSG_ERROR_SOCKET_OPTION     120
#define MSG_ERROR_SOCKET_OPTION_TXT "setsockopt() error: %s"
#define MSG_GOOD_BYE                121
#define MSG_GOOD_BYE_TXT            "Good bye\n"

#endif
