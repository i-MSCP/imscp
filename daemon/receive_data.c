#include "receive_data.h"

int receiveData(int fd, char *dest, size_t n)
{
	ssize_t i, rs;
	char c, *p;
	p = dest;

	for (i = 1; i <= n; i++) {
		try_again:

		if ((rs = read(fd, &c, 1)) == 1) {
			*p++ = c;

			if (c == '\n') {
				break;
			}
		} else if (rs == 0) { /* EOF, arrived ! */
			if (i == 1) { /* no data read. */
				return 0;
			} else { /* some data was read. */
				break;
			}
		} else {
			if (errno == EINTR) {
				goto try_again;
			}

			return -1;
		}
	}

	*p = 0;
	return i;
}
