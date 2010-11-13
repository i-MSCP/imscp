#include "imscp_daemon.h"

int getopt(int argc, char * const argv[], const char *optstring);
extern char *optarg;
extern int optind, opterr, optopt;

int main(int argc, char **argv) {

	int listenfd, c;
	struct sockaddr_in  servaddr;

	int connfd;
	struct sockaddr_in  cliaddr;

	char *pidfile_path;
	int given_pid;

	pid_t childpid;
	socklen_t clilen;

	given_pid = 0;
	pidfile_path = (char)'\0';

	while ((c = getopt( argc, argv, "p:")) != EOF) {
		switch( c ) {
			case 'p':
			    pidfile_path = optarg;
			    given_pid = 1;
			    break;
		}
    }

	daemon_init(message(MSG_DAEMON_NAME), SYSLOG_FACILITY);

	listenfd = socket(AF_INET, SOCK_STREAM, 0);

	memset((void *) &servaddr, '\0', (size_t) sizeof(servaddr));

	servaddr.sin_family = AF_INET;
    servaddr.sin_addr.s_addr = htonl(0x7F000001);
	/*servaddr.sin_addr.s_addr = inet_addr("127.0.0.1");*/
	servaddr.sin_port = htons(SERVER_LISTEN_PORT);

    if (bind(listenfd, (struct sockaddr *) &servaddr, sizeof(servaddr)) < 0) {
        say(message(MSG_ERROR_BIND), strerror(errno));
		exit(errno);
    }

	if (listen(listenfd, MAX_LISTENQ) < 0) {
		say(message(MSG_ERROR_LISTEN), strerror(errno));
		exit(errno);
	}

	say("%s", message(MSG_DAEMON_VER));

	tv_rcv = (struct timeval *) calloc(1, sizeof(struct timeval));
	tv_snd = (struct timeval *) calloc(1, sizeof(struct timeval));

	memset(tv_rcv, '\0', sizeof(struct timeval));
	memset(tv_snd, '\0', sizeof(struct timeval));

	tv_rcv -> tv_sec = 30;
	tv_rcv -> tv_usec = 0;

	tv_snd -> tv_sec = 30;
	tv_snd -> tv_usec = 0;

	signal(SIGCHLD, sig_child);
	signal(SIGPIPE, sig_pipe);

	if(given_pid) {
		FILE *file = fopen(pidfile_path, "w");
		fprintf(file, "%ld", (long)getpid());
		fclose(file);
	}

	for (;;) {
		memset((void *) &cliaddr, '\0', sizeof(cliaddr));
		clilen = (socklen_t) sizeof(cliaddr);

		if ((connfd = accept(listenfd, (struct sockaddr *) &cliaddr, &clilen)) < 0) {
			if (errno == EINTR) {
				say("%s", message(MSG_ERROR_EINTR));
				continue;
			} else {
				say(message(MSG_ERROR_ACCEPT), strerror(errno));
				exit(errno);
			}
		}

		setsockopt(connfd, SOL_SOCKET, SO_RCVTIMEO, tv_rcv, sizeof(struct timeval));
		setsockopt(connfd, SOL_SOCKET, SO_SNDTIMEO, tv_snd, sizeof(struct timeval));

		memset(client_ip, '\0', MAX_MSG_SIZE);

		inet_ntop(AF_INET, &cliaddr.sin_addr, client_ip, MAX_MSG_SIZE);

		if ( ( childpid = fork() ) == 0) {
			char *nmb = calloc(50, sizeof(char));

			close(listenfd);

			childpid = getpid();

			sprintf(nmb, "%d", childpid);

			say(message(MSG_START_CHILD), nmb);

			take_connection(connfd);
            free(nmb);

			exit(0);
		}

		close(connfd);
	}

	closelog();

	return (NO_ERROR);
}
