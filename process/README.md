# Tell-and-Show: Community Content-sharing Without Idols Nor Masters

The scripts in this folder operate on a dump of Wiki Commons (the one used is `commonswiki-20220320-pages-articles.xml.bz2`).

The scripts assume:

* A Wiki Commons dump already downloaded.
* A virtualenv in venv (from requirements.txt, these are mostly the LASER requirements).
* LASER installed in LASER folder, from https://github.com/facebookresearch/LASER
* You have at least 64G of RAM for the BIRCH clustering, 128 recommended
* A `bulk/` folder with lots of space (25G or more)

Flow:

1. `bzcat commonswiki-20220320-pages-articles.xml.bz2 |./get_descriptions.pl > titles_descriptions.tsv`  -- to obtain titles and descriptions
2. `cat titles_descriptions.tsv | ./clean_descriptions.pl > titles_descriptions.tsv,cleaned` -- clean descriptions
3. `cat titles_descriptions.tsv,cleaned | ./dedup_descriptions.pl > titles_descriptions.tsv,cleaned+dedup` -- base dedupliation
4. `compute_embeddings.sh` -- this will break the descriptions into 20,000 chunks and run LASER on each, producing `chunk.XY.embedding` for each chunk
5. `mv chunk* bulk/` -- to make space for the clustering
6. `python3 cluster.py` -- run BIRCH over the embeddings, produces `centers.npz` and `centers.tsv`, uses a LOT of RAM
7. `python3 dedup.py` -- near duplicates doing BIRCH again on each cluster but using tokens rather than embeddings, produces `dedup_labels.npz`
8. `cat empty.sql | sqlite3 empty.db` -- to create the empty SQLITE3 DB
8. `python3 build_database.py` -- store all the descriptions but the vectors only for clusters with at least 500 elements. For each of these clusters, store the center and 4 random other elements, this is what will be annotated. Produce `tellandshow.db` from `empty.db`. That DB is about 4Gb.
9. `echo "DELETE FROM descriptions WHERE descriptions.item NOT IN (SELECT embeddings_laser.item FROM embeddings_laser)" | sqlite3 tellandshow.db` -- if you don't want the descriptions not needed for annotation (resulting DB is about 50Mb).

Optional:

1. `python3 fetch_centers.py` -- fetch the thumbnails for the centers in the large clusters, they go into `thumbnails`
2. `python3 tsne_centers.py` -- produce a TSNE projection of the centers LASER embeddings using the thumbnails as representation, produces `tsne_centers.png`, used as the cover of the website.

