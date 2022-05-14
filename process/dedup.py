import os
import sys
import random
import bz2
import re

import numpy as np
from numpy.linalg import norm

from sklearn.cluster import Birch

descr = list()

with bz2.open("titles_descriptions.tsv,cleaned+dedup.bz2", "rt") as tsv:
    for line in tsv:
        title, str_descr = line.split("\t")
        descr.append( (title,  str_descr) )
print("Read {:,} titles and descriptions".format(len(descr)))

npz = np.load("centers.npz")
all_labels = npz['all_labels']
all_labels = [ int(lbl) for lbl in all_labels[:len(descr)] ] # extra is batching issue of LASER
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

if missing_center:
    print("Missing centers: {:,}".format(missing_center))

vsize = 1024
bsize = 10000

def myhash(x):
    return abs(hash(x)) % vsize

final_labels = all_labels # will set to -1 for dups

clust_nums = list(all_clusters.keys())
for clust in clust_nums: #[:100]:
    elems = all_clusters[clust]['elems']
    header = f"Cluster {clust} size {len(elems)}, {descr[all_clusters[clust]['center']][0] if 'center' in all_clusters[clust] else 'missing'}"
    print(header)
    header = ""

    vocab = dict()
    cl_titles = list()
    cl_toks = list()
    for idx in elems:
        title, text = descr[idx]
        cl_titles.append(title)
        str_toks = re.split("\\W", text)
        toks = list()
        for tok in str_toks:
            tok = myhash(tok)
            if tok not in vocab:
                vocab[tok] = len(vocab)
            toks.append(vocab[tok])
        cl_toks.append(toks)
    #print(f"\tVocab size: {len(vocab)}")

    # birch the descriptions as flat Boolean vectors
    all_labels = list()
    bpos = 0
    brc = Birch(n_clusters=None, threshold=0.1)
    while bpos * bsize < len(elems):
        batch = elems[(bpos*bsize):((bpos+1)*bsize)]
        X = np.zeros( (len(batch), len(vocab)) )
        for eidx in range(len(batch)):
            for tidx in cl_toks[bpos*bsize+eidx]:
                X[eidx,tidx] = 1.0
        brc.partial_fit(X)
        for lbl in brc.labels_:
            all_labels.append(lbl)
        bpos += 1
        X = None

    dups = dict()
    for eidx, lbl in enumerate(all_labels):
        if lbl not in dups:
            dups[lbl] = [ eidx ]
        else:
            dups[lbl].append(eidx)
    for lbl, idxs in dups.items():
        if len(idxs) > 1:
            # we got near dups
            if header:
                print(header)
                header = ""
            print("\tNear dups:")
            for dup in idxs:
                print(f"\t\t{': '.join(descr[elems[dup]])}")
            # pick representative, make sure it is the center if the center is there
            center_idx = all_clusters[clust]['center']
            center = False
            for dup in idxs:
                final_labels[elems[dup]] = -1
                if elems[dup] == center_idx:
                    final_labels[elems[dup]] = clust
                    center = True
            if not center:
                final_labels[elems[0]] = clust # keep first
    brc = None

np.savez("dedup_labels.npz", np.array(final_labels))

