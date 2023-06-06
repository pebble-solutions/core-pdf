#!/bin/bash

docker exec html2pdf sh -c 'rm -rf /Work/Results/*'

docker cp ./Convert/ html2pdf:/Work/

docker cp ./Divers/ html2pdf:/Work/

docker exec html2pdf /Work/run.sh

docker cp html2pdf:/Work/Convert/. ./Results
