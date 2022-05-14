import os
import sys
import random
import bz2
import sqlite3

import numpy as np

dim = 1024

descr = list()
with bz2.open("titles_descriptions.tsv,cleaned+dedup.bz2", "rt") as tsv:
    for line in tsv:
        title, str_descr = line.split("\t")
        descr.append( (title,  str_descr) )
print("Read {:,} titles and descriptions".format(len(descr)))

# -1 for the ones that are duplicates
all_labels = np.load("dedup_labels.npz")["arr_0"]
print("Read {:,} labels".format(len(all_labels)))

all_centers = dict()
with open("centers.tsv", "r") as tsv:
    for line in tsv:
        line = line.strip()
        parts = line.split("\t")
        if len(parts) == 4 and parts[-1]:
            all_centers[int(parts[0])] = int(parts[2])
print("Read {:,} centers".format(len(all_centers)))

all_clusters = dict()
missing_center = 0
for idx, clust in enumerate(all_labels):
    if clust == -1:
        continue # duplicate
    if clust in all_clusters:
        all_clusters[clust]['elems'].append(idx)
    else:
        if clust in all_centers:
            all_clusters[clust] = {
                'center' : all_centers[clust],
                'elems' : [ idx ]
                }
        else:
            missing_center += 1
            all_clusters[clust] = {
                'center' : idx, # first element is the center
                'elems' : [ idx ]
            }
is_center = set()
for cl in all_clusters.values():
    is_center.add(cl['center'])
            
if missing_center:
    print("Missing centers: {:,}".format(missing_center))

# pick the entries to keep the full LASER embeddings
rand = random.Random(42)

embedding = set()
for cl in all_clusters.values():
    # about 1,000 clusters
    if len(cl['elems']) >= 500:
        embedding.add(cl['center'])
        rest = list(cl['elems'])
        rest.remove(cl['center'])
        rand.shuffle(rest)
        # five representatives per cluster
        embedding.update(rest[:4])  

# dump it
os.system("cp empty.db tellandshow.db")

con = sqlite3.connect('file:tellandshow.db?nolock=1', uri=True)

cur = con.cursor()
cur.executescript("""
insert into langs(`lid`,`code`,`full`,`name_en`,`name_self`)
  values(NULL, "en", "en", "English (Any)", "English (Any)");
insert into cluster_runs(`cid`,`description`)
  values(NULL, "Birch clustering of LASER embeddings followed by TF dedup.");
""")

# ids for non-dups
to_insert = list()
for idx, clust in enumerate(all_labels):
    if clust == -1:
        continue # duplicate
    to_insert.append( (idx,) )
print("Items to insert: {:,}".format(len(to_insert)))
cur.executemany("insert into items(`iid`) values (?)", to_insert)

# descriptions and urls
to_insert = ""
to_insert_urls = list()
to_insert_descr = list()
for idx, t in enumerate(descr):
    if all_labels[idx] == -1:
        continue
    title, str_descr = t
    url = "https://commons.wikimedia.org/wiki/" + title
    to_insert_urls.append( (idx, 1, url) )
    to_insert_descr.append( (idx, 1, str_descr) )
print("Descriptions to insert: {:,}".format(len(to_insert_descr)))
cur.executemany("insert into urls(`item`, `lang`, `url`) values (?,?,?)", to_insert_urls)
cur.executemany("insert into descriptions(`item`, `lang`, `description`) values (?,?,?)", to_insert_descr)

# cluster
to_insert = list()
for idx, label in enumerate(all_labels):
    if label < 0:
        continue
    to_insert.append( (idx, 1, int(label), 1 if idx in is_center else 0) )
print("Clusters to insert: {:,}".format(len(to_insert)))
cur.executemany("insert into cluster_labels(`item`, `run`, `cluster`, `is_centroid`) values (?,?,?,?)", to_insert)

# fish the selected embeddings

chunks = list(filter(lambda x:x.startswith("chunk") and x.endswith("embedding"), os.listdir("./bulk/")))
chunks = sorted(chunks)
to_insert = list()
start = 0
for pos, chunk in enumerate(chunks):
    print(pos,end=" ")
    X = np.fromfile(f"./bulk/{chunk}", dtype=np.float32, count=-1)
    X.resize(X.shape[0] // dim, dim)

    for idx in range(start, start + X.shape[0]):
        if idx in embedding:
            entry = [ idx, 1 ]
            entry.extend(list(map(lambda x:float(x), X[idx - start])))
            to_insert.append( tuple(entry) )
    start += X.shape[0]

print()
entries = [ "c{:04}".format(x+1) for x in range(1024) ]
qmarks  = [ "?"                  for _ in range(1024) ]
print("Embeddings to insert: {:,}".format(len(to_insert)))
cur.executemany("insert into embeddings_laser(`item`, `version`," +
                ",".join(entries)
                + ") values (?,?," + ",".join(qmarks) + ")", to_insert)

con.commit()
con.close()
