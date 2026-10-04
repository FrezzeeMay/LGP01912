# KasKita — image Docker untuk hosting PHP (Render / Railway / Koyeb).
# Supabase dipakai sebagai database, jadi container ini hanya perlu PHP + Apache.
FROM php:8.2-apache

# Driver Postgres untuk PDO (wajib untuk Supabase).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Salin seluruh project ke DocumentRoot Apache.
COPY . /var/www/html/

# Folder upload harus bisa ditulis PHP.
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Render/Railway menyuntik $PORT — hormati via Listen dinamis.
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground

EXPOSE 80
