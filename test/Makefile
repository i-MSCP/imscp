#!/usr/bin/make -f

test:
	if test "$$TRAVIS" = true; then \
		perlbrew exec --with 5.14,5.16,5.18,5.20,5.21 fakeroot perl ./perlunit.pl; \
	else\
		fakeroot perl ./perlunit.pl; \
	fi\

.PHONY: test
