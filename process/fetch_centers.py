import os
import sys
import random
import sqlite3
import hashlib

from urllib.parse import quote

con = sqlite3.connect('file:tellandshow.db?nolock=1', uri=True)
cur = con.cursor()


# https://stackoverflow.com/questions/33689980/get-thumbnail-image-from-wikimedia-commons
for row in cur.execute("""
SELECT urls.url, urls.item FROM embeddings_laser LEFT JOIN
   urls
ON urls.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = urls.item
WHERE
   cluster_labels.run = 1 AND
   cluster_labels.is_centroid = 1;
"""):
    url = row[0][len("https://commons.wikimedia.org/wiki/"):]
    if url[:5] != 'File:':
        # print(url)
        pass
    else:
        url = url[5:]
        url = url.replace(" ", "_")
        m = hashlib.md5()
        m.update(url.encode("UTF-8"))
        md5 = m.hexdigest()
        url = quote(url, safe="_")
        print(f"wget -O thumbnails/{row[1]}.png https://upload.wikimedia.org/wikipedia/commons/thumb/{md5[0]}/{md5[0:2]}/{url}/32px-{url}.png")
        print(f"sleep {random.randint(2,5)}")
        
