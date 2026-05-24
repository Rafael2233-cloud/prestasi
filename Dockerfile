FROM php:8.2-apache

# Install ekstensi mysqli buat koneksi database lu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy semua file projek ke folder server Apache
COPY . /var/www/html/

# Paksa Apache pake port dinamis dari Railway
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf