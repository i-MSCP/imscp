# $Id: Makefile,v 1.1 2003/12/14 21:30:05 indiri69 Exp $
.SUFFIXES:
.SUFFIXES: .po .mo

SUBDIR = $(wildcard */LC_MESSAGES/*.po)

%.mo: %.po
	msgfmt $? -o $@

all: $(foreach dir,$(SUBDIR),$(basename $(dir)).mo)
