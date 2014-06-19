#include "send_data.h"

int sendData(int fd, char *src, size_t n)
{
	char *p;
	ssize_t res;
	size_t i = 0;
	p = src;

	while (i < n) {
		if ((res = write(fd, p, 1)) <= 0) {
			if (res != EINTR) {
				return (-1);
			}
		} else {
			p++;
			i++;
		}
	}

	return i;
}
