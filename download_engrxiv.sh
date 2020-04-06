csvtool col 1 engrxiv-papers.csv > junk.csv
csvtool drop 1 junk.csv > guids.csv
while IFS=, read GUID
do
    wget https://osf.io/$GUID/download -O $GUID.pdf
done < guids.csv
rm junk.csv
