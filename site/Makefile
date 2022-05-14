TEMPLATES=index learn technical about volunteer roadmap annotate download

all: lang_en lang_es index

index:
	cd templates; php index.php en > ../static/index.html

lang_%:
	lang=$@; lang=$${lang#lang_}; cd templates; for template in $(TEMPLATES); do echo $$template $$lang; php $$template.php $$lang > ../static/$${template}_$$lang.html; done

preprocess:
	php code/preprocess.php 1 1 data/run11-tree.ser

init:
	composer install
	rm -rf data/db; mkdir data/db; chmod a+rwx data/db; cp data/production.db ./data/db/; chmod a+rw ./data/db/*
	rm -rf data/runs; mkdir data/runs; chmod a+rwx data/runs

fetch:
	mkdir ./data/annotated; rsync -av --progress tands@tellandshow.org:/home/tands/site/data/db/production.db ./data/annotated/

export:
	php code/export.php

deploy:
	rsync -av --progress data/run11-tree.ser data/colors.lst data/db.php tands@tellandshow.org:/home/tands/site/data/
	rsync -av --delete composer.json composer.lock Makefile code static templates tands@tellandshow.org:/home/tands/site/
