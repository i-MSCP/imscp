#include "recv_line.h"

int receiveLine(int fd, char *dest, size_t n)
{
	int rs;

	if ((rs = receiveData(fd, dest, n)) < 0) {
		say(message(MSG_ERROR_SOCKET_RD), strerror(errno));
		return -1;
	}

	if (rs == 0) {
		say("%s", message(MSG_ERROR_SOCKET_EOF));
	}

	return rs;
}
