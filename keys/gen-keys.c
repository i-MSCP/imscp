
#include <stdlib.h>

#include <string.h>

#include <stdio.h>

typedef unsigned int word;

unsigned char *key;
word key_size;

unsigned char *iv;
word iv_size;

unsigned char alphabet_name[] = "twofish";

unsigned char alphabet_mode[] = "cfb";

int rand_seq(unsigned char *dest, int len);

int check_char(unsigned int ch);

int main(int argc, char **argv)
{
    if (argc != 3) {

        fprintf(stderr, "Wrong arguments count !\n");

        return 1;

    }

    key_size = (word) atoi(argv[1]);

    iv_size = (word) atoi(argv[2]);

    if (key_size == 0 || iv_size == 0) {

        fprintf(stderr, "Wrong argument syntax !\n");

        return 1;
    }

    key = calloc((size_t) key_size + 1, sizeof(unsigned char));

    iv = calloc((size_t) iv_size + 1, sizeof(unsigned char));

    if (key == NULL || iv == NULL) {

        fprintf(stderr, "Memory allocation error !\n");

        return 1;

    }

    if (rand_seq(key, key_size) || rand_seq(iv, iv_size)) {

        fprintf(stderr, "rand_seq() error !\n");

        return 1;

    }

    fprintf(stdout, "%s\n%s\n", key, iv);

    free(key); free(iv);

    return 0;

}

int check_char(unsigned int ch) {

    if (ch > 32 && ch < 127) return 1;

    return 0;

}

int rand_seq(unsigned char *dest, int len) {

    int i, ch, result;

    FILE *fp;

    fp = fopen("/dev/urandom", "rb");

    if (fp == NULL)

        return -1;

    for (i = 0; i < len; i++) {

        do {

            ch = getc(fp);

        } while (!check_char(ch));

        *(dest)++ = (unsigned char) ch;
    }

    result = fclose(fp);

    if (result)

        return -1;

    return 0;
}

