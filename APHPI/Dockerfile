FROM php:8.1-apache

# Installer les dépendances nécessaires pour Symfony
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install intl pdo_mysql zip

# Activer le module Apache rewrite
RUN a2enmod rewrite

# Installer Node.js
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs

# Installer Vue.js
RUN npm install vue

# Copier le code Symfony dans le conteneur
COPY . /var/www/html/

# Définir les permissions pour Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir la variable d'environnement PATH pour inclure Composer
ENV PATH="${PATH}:/root/.composer/vendor/bin"

# Définir le répertoire de travail
WORKDIR /var/www/html

# Exposer le port 80
EXPOSE 80

