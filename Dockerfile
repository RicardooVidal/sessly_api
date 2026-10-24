FROM ubuntu:latest
LABEL authors="ricardo"

ENTRYPOINT ["top", "-b"]
