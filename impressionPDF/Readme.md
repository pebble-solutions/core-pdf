# README

Ce document fournit des instructions claires et concises pour exécuter le Docker container à partir du Dockerfile.

## Prérequis

1. Lancer le script :

    ```bash
    ./start.sh
    ```
## Utilisation

1. Placez votre fichier HTML dans le répertoire `/Convert`. Veuillez vous assurer que le fichier a l'extension `.html`.

2. Pour personnaliser l'en-tête et le pied de page de votre fichier HTML, créez deux fichiers nommés `header.html` et `footer.html` dans le répertoire `/Divers`.

3. Le fichier de style CSS doit être placé dans le répertoire `/Divers/stylesheet`.

4. Dans vos fichiers CSS, veuillez ajouter la balise suivante à la section `<head>` de chaque fichier HTML où vous souhaitez inclure le fichier CSS :

   ```html
   <link href="../Divers/stylesheet/*.css" rel="stylesheet">
   ```

5. N'oubliez pas de remplacer *.css par le nom réel de votre fichier CSS.

6. Pour lancer la conversion, exécutez le script bash generate.sh en utilisant la commande suivante :
    
    ```
    ./generate.sh
    ```
    Ce script effectuera la conversion du fichier HTML en PDF avec les en-têtes, les pieds de page et les styles appropriés.

7. Une fois la conversion terminée, vous pourrez récupérer le résultat dans le répertoire `/Results/`. Le fichier converti aura le même nom que le fichier d'origine, mais avec l'extension .pdf.

8. Vous avez maintenant converti votre fichier HTML en utilisant le Docker container et les fichiers associés. Vous pouvez trouver le résultat final dans le répertoire /Results.

## Fermeture

1.  Vous pouvez fermer le conteneur avec :

    ```
    ./stop.sh
    ```
