
#include "say.h"

void say(char *format, char *message)
{
    syslog(SYSLOG_MSG_PRIORITY, format, message);
}
