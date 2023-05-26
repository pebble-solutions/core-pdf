-Après avoir lancer le conteneur html2pdf
	service ssh restart

-Après avoir lancé le conteneur du projet symfony attacher le conteneur et executer :

	service mariadb start

	echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY 'root' WITH GRANT OPTION;" | mysql -u root -proot

	
	echo "create database messengerDB;" | mysql -u root -proot
	
	ssh-copy-id root@html2pdf
		mdp = password
		
	php bin/console doctrine:schema:update -f
		

