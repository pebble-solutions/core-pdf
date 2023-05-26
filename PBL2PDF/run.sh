#!/bin/bash

if ls /Work/Convert/*.html 1> /dev/null 2>&1; then
    find /Work/Convert -type f -name '*.html' -print0 | while IFS= read -r -d '' file; do
        
node convert.js $file $file.pdf /Work/Divers/header.html /Work/Divers/footer.html
    
rm "$file"
done
fi
if  ls /Work/Convert/*.htm 1> /dev/null 2>&1; then
    find /Work/Convert -type f -name '*.htm' -print0 | while IFS= read -r -d '' file; do
        
node convert.js $file $file.pdf /Work/Divers/header.html /Work/Divers/footer.html
rm "$file"
done
fi
