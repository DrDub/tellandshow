SELECT urls.url FROM embeddings_laser LEFT JOIN
   urls
ON urls.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
WHERE
   cluster_labels.is_centroid = 1
ORDER BY RANDOM() LIMIT 1;



SELECT urls.url, embeddings_laser.* FROM embeddings_laser LEFT JOIN
   urls
ON urls.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
WHERE
   cluster_labels.run = 1 AND
   cluster_labels.is_centroid = 1 AND 
   embeddings_laser.version = 1;




SELECT count(*) FROM cluster_labels 
WHERE
   cluster_labels.run = 1 AND
   cluster_labels.cluster IN (SELECT cluster_labels.cluster FROM embeddings_laser 
 LEFT JOIN 	
   cluster_labels
 ON cluster_labels.item = embeddings_laser.item
WHERE
   cluster_labels.is_centroid = 1);



SELECT DISTINCT cluster_labels.cluster FROM cluster_labels ;



SELECT descriptions.description, descriptions.item FROM embeddings_laser LEFT JOIN
   descriptions
ON descriptions.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
LEFT JOIN
   langs
ON descriptions.lang = langs.lid
WHERE
   (cluster_labels.cluster = 15 OR cluster_labels.cluster = 518) AND
   langs.code = "en" AND
   cluster_labels.run = 1 AND
   embeddings_laser.version = 1;



SELECT * FROM descriptions
WHERE
  descriptions.item NOT IN (SELECT embeddings_laser.item FROM embeddings_laser);


DELETE FROM descriptions
WHERE
  descriptions.item NOT IN (SELECT embeddings_laser.item FROM embeddings_laser);
