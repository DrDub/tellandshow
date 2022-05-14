import os
import sys
import random
import bz2

import numpy as np

from sklearn.manifold import TSNE

dim = 1024

chunks = list(filter(lambda x:x.startswith("chunk") and x.endswith("embedding"), os.listdir(".")))
chunks = sorted(chunks)
chunks = chunks[:20]
vectors = np.zeros( (len(chunks) * 10000, dim) )
for pos, chunk in enumerate(chunks):
    X = np.fromfile(chunk, dtype=np.float32, count=-1)
    X.resize(X.shape[0] // dim, dim)
    vectors[pos:(pos+X.shape[0]),:] = X

titles = list()
#with bz2.open("titles_descriptions.tsv,cleaned+dedup.bz2", "rt") as tsv:
with open("titles_cleaned+dedup.txt", "r") as tsv:
    count = 0
    for line in tsv:
        title, _ = line.split("\t")
        if title.startswith("File:"):
            title = title[len("File:"):]
        title=title[:50]
        titles.append(title)
        if len(titles) == vectors.shape[0]:
            break

print(f"Plotting {vectors.shape[0]} vectors")

words = titles[:vectors.shape[0]]
tsne_model = TSNE(perplexity=40, n_components=2, init='pca', n_iter=500, random_state=23, learning_rate=200, )
projected = tsne_model.fit_transform(vectors)

x = []
y = []
for t in projected:
    x.append(t[0])
    y.append(t[1])

import matplotlib.pyplot as plt
plt.figure(figsize=(15,15), dpi=300)
plotted_count   = 0
plotted_section = set()
r = random.Random(42)
# plot a meaningful, visible sample
for idx in range(len(x)):
    if r.random() < 0.95:
        continue
    
    section = str(int(x[idx] * 10 * 4)) + "-" + str(int(y[idx] * 10 * 4))
    if section in plotted_section:
        continue # ensure visible
    plotted_section.add(section)
    plt.scatter(x[idx] ,y[idx])
    if r.random() < 0.1:
        plt.annotate(words[idx], xy=(x[idx], y[idx]), xytext=(5, 2), 
                     textcoords='offset points', ha='right', va='bottom')
        plotted_count += 1
        if plotted_count > 150:
            break # ensure visible

plt.savefig("tsne.png", bbox_inches='tight', dpi=300)
