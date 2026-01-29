FROM nginx:alpine

# Suppression de la configuration par défaut
RUN rm /etc/nginx/conf.d/default.conf

# Copie de notre configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/

# Création du répertoire de travail
WORKDIR /var/www/html

# Exposition du port
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
