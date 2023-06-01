# core-pdf

## Instructions

Prérequis : Assurez-vous d'avoir Docker installé sur votre système.

1. Création du réseau Docker :
   ```
   docker network create network2pdf
   ```
2. Lancement du conteneur html2pdf :
   Pour lancer le conteneur html2pdf, exécutez le script start.sh depuis le répertoire /PBL2PDF :
   ```
   ./start.sh
    ```
3. Construction de l'image du conteneur composer :
   Dans le répertoire /APHPI/, exécutez la commande suivante :
   ```
   docker build -t composer .
   ```
4. Lancement du conteneur composer :
   Pour lancer le conteneur composer, exécutez la commande suivante :
   ```
   docker run -d -p 8080:80 --network network2pdf --name composer -v <racine_du_projet_symfony>:/var/www/html/ --restart always composer
   ```
Une fois les conteneurs lancés, exécutez les commandes suivantes sur chaque conteneur :

- Sur le conteneur html2pdf :
    ```
  service ssh restart
    ```
- Sur le conteneur composer :
    ```
  service mariadb start
  echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY 'root' WITH GRANT OPTION;" | mysql -u root -proot
  echo "create database messengerDB;" | mysql -u root -proot
  ssh-copy-id root@html2pdf
    ```
    Mot de passe : password
    ```
  php bin/console doctrine:schema:update -f
  ```
Ensuite, sur le même conteneur composer, exécutez la commande suivante pour lancer le worker qui effectuera les tâches de manière asynchrone :
  ```
  php bin/console messenger:consume -vv
  ```
    