#include "daemon_msg.h"

char *message(int message_number)
{
    if ((message_number - 101) < 0) {
        return (messages_array[0][0]);
    }

    return (messages_array[message_number - 101][0]);
}

void say(char *format, char *message)
{
    syslog(SYSLOG_MSG_PRIORITY, format, message);
}
