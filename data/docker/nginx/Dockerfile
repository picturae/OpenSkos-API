FROM nginx

WORKDIR /app

COPY conf/ ./conf
COPY entrypoint.sh ./

CMD [ "/bin/bash", "entrypoint.sh" ]