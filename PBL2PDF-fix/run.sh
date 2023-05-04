#!/bin/bash

if ls /Work/Convert/*.html 1> /dev/null 2>&1; then
    find /Work/Convert -type f -name '*.html' -print0 | while IFS= read -r -d '' file; do
        echo "$file"
wkhtmltopdf --enable-local-file-access --user-style-sheet /Work/Divers/stysheet/style.css  --encoding utf-8 --margin-top 3cm --margin-bottom 3cm --margin-left 1cm --margin-right 1cm --header-html /Work/Divers/header.html --footer-html /Work/Divers/footer.html --header-spacing 5 --footer-spacing 5 "$file" "$file.pdf"        
rm "$file"
done
fi
if  ls /Work/Convert/*.htm 1> /dev/null 2>&1; then
    find /Work/Convert -type f -name '*.htm' -print0 | while IFS= read -r -d '' file; do
        echo "$file"
wkhtmltopdf --enable-local-file-access --user-style-sheet /Work/Divers/stysheet/style.css --encoding utf-8 --margin-top 3cm --margin-bottom 3cm --margin-left 1cm --margin-right 1cm --header-html /Work/Divers/header.html --footer-html /Work/Divers/footer.html --header-spacing 5 --footer-spacing 5 "$file" "$file.pdf"        
rm "$file"
done
fi
