#include "send_line.h"

int sendLine(int fd, char *src, size_t len)
{
	int res;

	if ((res = sendData(fd, src, len)) < 0) {
		say(message(MSG_ERROR_SOCKET_WR), strerror(errno));

		return (-1);
	}

	return NO_ERROR;
}
