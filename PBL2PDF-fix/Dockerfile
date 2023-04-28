FROM ubuntu:22.04

COPY ./setup.sh /

RUN ./setup.sh

RUN mkdir /Work

COPY ./run.sh /Work/

WORKDIR /Work/

RUN apt --fix-broken -y install

RUN apt-get update && apt-get install -y openssh-server

RUN mkdir /var/run/sshd

RUN echo 'root:password' | chpasswd

RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config

RUN apt-get install -y openssl libssl-dev libssh2-1-dev

RUN service ssh restart

