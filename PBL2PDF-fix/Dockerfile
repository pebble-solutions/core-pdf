FROM ubuntu:22.04

COPY ./setup.sh /

RUN ./setup.sh

RUN mkdir /Work

COPY ./run.sh /Work/

WORKDIR /Work/
