#include "lr_cmd.h"

int lr_cmd(int fd, char *msg) {
	return lr_syntax(fd, msg);
}
