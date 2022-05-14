import os
import sys
import random
import bz2
import sqlite3

import numpy as np

from sklearn.manifold import TSNE

con = sqlite3.connect('file:tellandshow.db?nolock=1', uri=True)
cur = con.cursor()

entries = list()
for row in cur.execute("""
SELECT urls.url, urls.item, embeddings_laser.* FROM embeddings_laser LEFT JOIN
   urls
ON urls.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
WHERE
   cluster_labels.run = 1 AND
   cluster_labels.is_centroid = 1 AND 
   embeddings_laser.version = 1;
"""):
    item = row[1]
    vector = row[4:]
    if os.path.exists(f"thumbnails/{item}.png"):
        if os.path.getsize(f"thumbnails/{item}.png") > 0:
            entries.append( (item, vector) )

dim = 1024
vectors = np.zeros( (len(entries), dim) )
for pos, entry in enumerate(entries):
    vectors[pos] = entry[1]
  
print(f"Plotting {vectors.shape[0]} vectors")

tsne_model = TSNE(perplexity=40, n_components=2, init='pca', n_iter=1000, random_state=23, learning_rate=200, )
projected = tsne_model.fit_transform(vectors)

x = []
y = []
for t in projected:
    x.append(t[0])
    y.append(t[1])

import matplotlib.pyplot as plt
from matplotlib.offsetbox import (OffsetImage,AnnotationBbox)
from matplotlib.cbook import get_sample_data

plt.figure(figsize=(20,20), dpi=300)
fig, ax = plt.subplots()
ax.axis("off")

plotted_count   = 0
plotted_section = set()
r = random.Random(42)
# plot a meaningful, visible sample
fullp = os.path.realpath('.')
for idx in range(len(x)):
    #if r.random() < 0.95:
    #    continue
    
    section = str(int(x[idx] * 10 * 4)) + "-" + str(int(y[idx] * 10 * 4))
    #if section in plotted_section:
    #    continue # ensure visible
    plotted_section.add(section)
    if True: #r.random() < 0.1:
        fn = get_sample_data(f"{fullp}/thumbnails/{entries[idx][0]}.png", asfileobj=False)
        try:
            arr_img = plt.imread(fn, format='png')
        except:
            continue
        cmap = None
        if len(arr_img.shape) == 2:
            w, h = arr_img.shape
            cmap = "gray"
        else:
            w, h, _ = arr_img.shape
        imagebox = OffsetImage(arr_img, zoom=0.25, cmap=cmap)
        imagebox.image.axes = ax
        ab = AnnotationBbox(imagebox, [ x[idx], y[idx] ],
                            xybox=(-0.25, -0.25),
                            xycoords='data',
                            boxcoords="offset points",
                            pad=0, frameon=False)
        ax.add_artist(ab)

        plotted_count += 1
        #if plotted_count > 150:
        #    break # ensure visible
    plt.scatter(x[idx] ,y[idx])
plt.savefig("tsne_centers.png", bbox_inches='tight', dpi=300)
