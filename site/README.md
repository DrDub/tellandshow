# Tell-and-Show website

Dependencies:

* php 7.4
* php-yaml
* php-sqlite3
* composer
* RubixML (through composer)

Configuration:

* Serve `static/`
* Set php `memory_limit` to 1G

Fetch `http://tellandshow.org/production.db` and put it in `data/` (that's an DB with no annotations, not the *actual* production DB), it is 50Mb. See [the processing instructions](../process/README.md) for information about how that file came about. 

Execute `make init; make; make preprocess`. This should have the site ready to receive annotations.

