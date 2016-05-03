#ifndef _DAEMON_MSG_H
#define _DAEMON_MSG_H

#include <syslog.h>

#include "daemon_globals.h"

char *messages_array[MSG_MAX_COUNT][1] = {
    {MSG_WELCOME_TXT},
    {MSG_DAEMON_STARTED_TXT},
    {MSG_DAEMON_NAME_TXT},
    {MSG_ERROR_LISTEN_TXT},
    {MSG_SIG_PIPE_TXT},
    {MSG_ERROR_ACCEPT_TXT},
    {MSG_START_CHILD_TXT},
    {MSG_END_CHILD_TXT},
    {MSG_ERROR_SOCKET_WR_TXT},
    {MSG_ERROR_SOCKET_RD_TXT},
    {MSG_ERROR_SOCKET_EOF_TXT},
    {MSG_HELO_CMD_TXT},
    {MSG_BAD_SYNTAX_TXT},
    {MSG_CMD_OK_TXT},
    {MSG_BYE_CMD_TXT},
    {MSG_EQ_CMD_TXT},
    {MSG_CMD_ANSWER_TXT},
    {MSG_ERROR_BIND_TXT},
    {MSG_ERROR_SOCKET_CREATE_TXT},
    {MSG_ERROR_SOCKET_OPTION_TXT},
    {MSG_GOOD_BYE_TXT}
};

char *message(int message_number);
void say(char *format, char *message);

#endif
