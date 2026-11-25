FROM php:8.2-cli

# Install PDO MySQL and other extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring

WORKDIR /app
COPY . .

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
