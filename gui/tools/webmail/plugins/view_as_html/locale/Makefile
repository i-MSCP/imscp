# $Id$
.SUFFIXES:
.SUFFIXES: .po .mo

SUBDIR = $(wildcard */LC_MESSAGES/*.po)

%.mo: %.po
	msgfmt $? -o $@

all: $(foreach dir,$(SUBDIR),$(basename $(dir)).mo)
