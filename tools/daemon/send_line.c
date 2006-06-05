
#include "send_line.h"


int send_line(int fd, char *src, size_t len)
{
	int res;

	if ((res = send_data(fd, src, len)) < 0) {

		say(message(MSG_ERROR_SOCKET_WR), strerror(errno));

		return (-1);

	} else {
		/*char *nmb = calloc(10, sizeof(char));

        sprintf(nmb, "%d", res);

		say(message(MSG_BYTES_WRITTEN), nmb);

		free(nmb);*/

        return (NO_ERROR);
	}
}
