import os
import sys
import random
import bz2

import numpy as np
from numpy.linalg import norm

from sklearn.cluster import Birch

dim = 1024
size = 10000 # chunk size

titles = list(enumerate(open("titles_cleaned+dedup.txt", "r").read().split("\n")))
titles_chunked = list()
all_titles = titles
while all_titles:
    titles_chunked.append(all_titles[:size])
    all_titles = all_titles[size:]

chunks = list(filter(lambda x:x.startswith("chunk") and x.endswith("embedding"), os.listdir("./bulk/")))
chunks = sorted(chunks)
# missing: randomize
chunk_titles = list(zip(chunks, titles_chunked))
#chunk_titles = chunk_titles[:3]
titles = [ title for ct in chunk_titles for title in ct[1] ]
chunks = [ chunk for chunk, _ in chunk_titles]
labels = np.zeros((len(chunks) * size,))

brc=Birch(n_clusters=None, threshold=0.4)
centroids = list()

for pos, chunk in enumerate(chunks):
    print(pos, len(centroids), len(set(labels)))
    X = np.fromfile(f"./bulk/{chunk}", dtype=np.float32, count=-1)
    X.resize(X.shape[0] // dim, dim)
    #TODO there might be a bug here in the last chunk
    start = pos * X.shape[0]

    brc.partial_fit(X)

    # label to centroid
    label_to_centroid = dict()
    for p in range(len(brc.subcluster_labels_)):
        label_to_centroid[brc.subcluster_labels_[p]] = p

    while len(centroids) < len(brc.subcluster_labels_):
        centroids.append(None)

    # update centroid representatives, if needed
    for p, l in enumerate(brc.labels_):
        dist = norm(brc.subcluster_centers_[label_to_centroid[l]] - X[p])
        if centroids[l] is None or centroids[l][1] > dist:
            orig_pos, title = titles[start + p]
            centroids[l] = (l, dist, orig_pos, title)
    labels[pos*size:(pos*size+len(brc.labels_))] = brc.labels_

# missing: save the title mapping
np.savez("centers.npz", all_labels=labels, centers=brc.subcluster_centers_, labels=brc.subcluster_labels_)
with open("centers.tsv", "w") as tsv:
    for n, e in enumerate(centroids):
        if e is None:
            tsv.write(f"{n}\t\t\t\n")
        else:
            l, dist, idx, title = e
            tsv.write(f"{l}\t{dist}\t{idx}\t{title}\n")

