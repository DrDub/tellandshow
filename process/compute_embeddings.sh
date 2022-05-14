#!/bin/bash

source ./venv/bin/activate

export LASER=$PWD/LASER
export LC_ALL=C.UTF-8
export LANG=C.UTF-8

model_dir="${LASER}/models"
encoder="${model_dir}/bilstm.93langs.2018-12-26.pt"
bpe_codes="${model_dir}/93langs.fcodes"

LANG=en
bzcat titles_descriptions.tsv,cleaned+dedup.bz2 | perl -pe 's/^[^\t]+\t//' > descriptions_cleaned+dedup.txt
split --lines=10000 descriptions_cleaned+dedup.txt chunk.
for chunk in chunk.*
do
    echo $chunk
    cat $chunk \
        | python ${LASER}/source/embed.py \
                 --encoder ${encoder} \
                 --token-lang $LANG \
                 --bpe-codes ${bpe_codes} \
                 --verbose \
                 --output $chunk.embedding
done


