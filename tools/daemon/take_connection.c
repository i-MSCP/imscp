
#include "take_connection.h"

void take_connection(int sockfd)
{
    license_data_type ld;


    /*
     chek for client ip
     */
    if (strcmp(client_ip,"127.0.0.1")) {
        /*
         ints not local host
         */
        close(sockfd);
        return;
    }

    send_line(sockfd, message(MSG_WELCOME), strlen(message(MSG_WELCOME)));

    if (helo_cmd(sockfd, &ld)) {
        close(sockfd);
        return;
    }

    if (lr_cmd(sockfd, &ld)) {
        close(sockfd);
        return;
    }

    if (bye_cmd(sockfd)) {
        close(sockfd);
        return;
    }

    sleep(1);

    close(sockfd);
}
