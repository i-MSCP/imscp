#include "send_line.h"

int send_line(int fd, char *src, size_t len) {

	int res;

	if ((res = send_data(fd, src, len)) < 0) {
		say(message(MSG_ERROR_SOCKET_WR), strerror(errno));

		return (-1);
	}

	return NO_ERROR;
}
