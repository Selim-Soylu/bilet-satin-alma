# Resmi PHP 8.2 ve Apache sunucusu içeren temel imaj
FROM php:8.2-apache

# Gerekli sistem paketlerini kur (SQLite geliştirme kütüphanesi gibi)
RUN apt-get update && \
    apt-get install -y \
        libsqlite3-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentilerini kur: PDO, pdo_sqlite, zip
RUN docker-php-ext-install pdo pdo_sqlite zip

# Apache mod_rewrite etkinleştir (URL yönlendirmeleri için)
RUN a2enmod rewrite

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Projenin TÜM dosyalarını imajın İÇİNE kopyala
COPY . /var/www/html/

# --- Apache Yapılandırmasını Düzeltme (KRİTİK ADIM: DocumentRoot ve Dizinden erişim) ---
# Apache'nin varsayılan site yapılandırmasını (000-default.conf) düzenle
# DocumentRoot'u projenin 'public' klasörüne ayarla
# Ayrıca, dizin erişim izinlerini de doğru yapılandır.
RUN sed -i -e 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf && \
    echo '<Directory /var/www/html/public/>' >> /etc/apache2/conf-available/serve-public.conf && \
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/conf-available/serve-public.conf && \
    echo '    AllowOverride All' >> /etc/apache2/conf-available/serve-public.conf && \
    echo '    Require all granted' >> /etc/apache2/conf-available/serve-public.conf && \
    echo '</Directory>' >> /etc/apache2/conf-available/serve-public.conf && \
    a2enconf serve-public # Yeni yapılandırmayı etkinleştir


# --- Veritabanı İçin İzinler (İmajın İÇİNDE) ---
# Kopyalanan 'database' klasörünün ve içindeki dosyanın Apache tarafından yazılabilir olmasını sağla.
RUN chown -R www-data:www-data /var/www/html/database && \
    chmod -R 775 /var/www/html/database

# Apache'nin çalıştığı portu belirt (Standart 80)
EXPOSE 80

# Konteyner başladığında Apache'yi ön planda çalıştır
CMD ["apache2-foreground"]
