FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/app.conf \
    && echo '    Options Indexes FollowSymLinks' >> /etc/apache2/conf-available/app.conf \
    && echo '    AllowOverride All' >> /etc/apache2/conf-available/app.conf \
    && echo '    Require all granted' >> /etc/apache2/conf-available/app.conf \
    && echo '    DirectoryIndex index.php index.html' >> /etc/apache2/conf-available/app.conf \
    && echo '</Directory>' >> /etc/apache2/conf-available/app.conf \
    && a2enconf app