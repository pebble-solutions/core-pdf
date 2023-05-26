# Utilisez une image de base Node.js
FROM node:14

# Définition du répertoire de travail
RUN mkdir /Work

COPY ./run.sh /Work/

WORKDIR /Work/

# Copiez le fichier package.json et package-lock.json
COPY package*.json ./

# Installez les dépendances
RUN npm install --only=production

# Installez les dépendances de Chromium
RUN apt-get update && apt-get install -y \
    ca-certificates \
    fonts-liberation \
    gconf-service \
    libappindicator3-1 \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libc6 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgbm-dev \
    libgcc1 \
    libgconf-2-4 \
    libgdk-pixbuf2.0-0 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    lsb-release \
    wget \
    xdg-utils \
    --no-install-recommends

# Téléchargez et extrayez le binaire Chromium
RUN wget -q -O- https://github.com/puppeteer/puppeteer/raw/main/utils/Install.js | node -


COPY convert.js .

RUN apt --fix-broken -y install

RUN apt-get update && apt-get install -y openssh-server

RUN mkdir /var/run/sshd

RUN echo 'root:password' | chpasswd

RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config

RUN apt-get install -y openssl libssl-dev libssh2-1-dev

RUN service ssh restart