#include "imscp_daemon.h"

int main(int argc, char *argv[])
{
	int listenfd, connfd, c, given_pid;
	char *pidfile_path;
	struct sockaddr_in servaddr, cliaddr;
	struct timeval timeout_rcv, timeout_snd;

	pid_t childpid;
	socklen_t clilen;

	given_pid = 0;
	pidfile_path = (char)'\0';

	/* Parse command line options */
	while ((c = getopt(argc, argv, "p:")) != EOF) {
		switch(c) {
			case 'p':
				pidfile_path = optarg;
				given_pid = 1;
				break;
		}
	}

	/* Daemonize */
	daemonInit(message(MSG_DAEMON_NAME), SYSLOG_FACILITY);

	/* Creates an endpoint for communication */
	if((listenfd = socket(AF_INET, SOCK_STREAM, IPPROTO_IP)) < 0) {
		say(message(MSG_ERROR_SOCKET_CREATE), strerror(errno));
		exit(errno);
	}

	/* Ident socket */
	memset((void *) &servaddr, '\0', (size_t) sizeof(servaddr));
	servaddr.sin_family = AF_INET;
	servaddr.sin_addr.s_addr = htonl(DAEMON_LISTEN_ADDR);
	servaddr.sin_port = htons(DAEMON_LISTEN_PORT);

	/* Assign name to the socket */
	if (bind(listenfd, (struct sockaddr *) &servaddr, sizeof(servaddr)) < 0) {
		say(message(MSG_ERROR_BIND), strerror(errno));
		exit(errno);
	}

	/* Marks the socket referred to by listenfd as a passive socket */
	if (listen(listenfd, DAEMON_MAX_LISTENQ) < 0) {
		say(message(MSG_ERROR_LISTEN), strerror(errno));
		exit(errno);
	}

	/* Setup timeout for input operations  */
	timeout_rcv.tv_sec = 10;
	timeout_rcv.tv_usec = 0;

	/* Setup timeout for output operations */
	timeout_snd.tv_sec = 10;
	timeout_snd.tv_usec = 0;

	signal(SIGCHLD, sigChild);
	signal(SIGPIPE, sigPipe);

	if(given_pid) {
		FILE *file = fopen(pidfile_path, "w");
		fprintf(file, "%ld", (long)getpid());
		fclose(file);
	}

	say("%s", message(MSG_DAEMON_STARTED));

	while (1) {
		memset((void *) &cliaddr, '\0', sizeof(cliaddr));
		clilen = (socklen_t) sizeof(cliaddr);

		/* Wait for new connection */
		if ((connfd = accept(listenfd, (struct sockaddr *) &cliaddr, &clilen)) < 0) {
			if (errno == EINTR) {
				continue;
			} else {
				say(message(MSG_ERROR_ACCEPT), strerror(errno));
				exit(errno);
			}
		}

		setsockopt(connfd, SOL_SOCKET, SO_RCVTIMEO, (char *)&timeout_rcv, sizeof(timeout_rcv));
		setsockopt(connfd, SOL_SOCKET, SO_SNDTIMEO, (char *)&timeout_snd, sizeof(timeout_snd));

		if ( ( childpid = fork() ) == 0) {
			char *nmb = calloc(50, sizeof(char));

			close(listenfd);

			childpid = getpid();

			sprintf(nmb, "%d", childpid);

			say(message(MSG_START_CHILD), nmb);

			takeConnection(connfd);
			free(nmb);

			exit(0);
		}

		close(connfd);
	}

	closelog();

	return 0;
}
