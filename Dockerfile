FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libcurl4-openssl-dev \
    libzip-dev \
    libxml2-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (openssl is critical for SSL/HTTPS)
RUN apt-get update && apt-get install -y \
    openssl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    zip \
    dom

# Install Composer with proper PHP path
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install Node.js for Tailwind CSS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies with verbose output for debugging
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --working-dir=/var/www/html \
    --verbose \
    || (echo "Composer install failed - trying with lower memory"; \
        COMPOSER_MEMORY_LIMIT=-1 composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        --working-dir=/var/www/html)

# Install Node dependencies and build CSS
RUN npm install --legacy-peer-deps && npm run build

# Create Firebase service account directory
RUN mkdir -p /var/www/html/config && \
    chown -R www-data:www-data /var/www/html

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Create .htaccess if it doesn't exist
RUN if [ ! -f /var/www/html/.htaccess ]; then \
    echo '<IfModule mod_rewrite.c>' > /var/www/html/.htaccess && \
    echo '    RewriteEngine On' >> /var/www/html/.htaccess && \
    echo '    RewriteBase /' >> /var/www/html/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> /var/www/html/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-d' >> /var/www/html/.htaccess && \
    echo '    RewriteRule ^(.*)$ index.php [QSA,L]' >> /var/www/html/.htaccess && \
    echo '</IfModule>' >> /var/www/html/.htaccess; \
    fi

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
