#include "send_data.h"

int sendData(int fd, char *src, size_t n)
{
	char *p;
	ssize_t rs;
	size_t i = 0;
	p = src;

	while (i < n) {
		if ((rs = write(fd, p, 1)) <= 0) {
			if (rs != EINTR) {
				return (-1);
			}
		} else {
			p++;
			i++;
		}
	}

	return i;
}
