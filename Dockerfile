FROM php:8.2-apache

# 1. Install & aktifkan ekstensi mysqli buat koneksi database lu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# 2. Aktifkan mod_rewrite Apache (buat jaga-jaga urusan routing)
RUN a2enmod rewrite

# 3. Paksa Apache pake port yang dikasih sama Railway secara dinamis
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 4. Copy seluruh file projek lu ke folder Apache
COPY . /var/www/html/