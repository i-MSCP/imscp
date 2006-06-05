
#include "message.h"

char *message(int message_number)
{
	if ((message_number - 10001) < 0 ) {
		return (messages_array[0][0]);
	} else {
		return (messages_array[message_number - 10001][0]);
	}
}
