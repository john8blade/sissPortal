# Usar imagem PHP 5.6 com Apache
FROM php:5.6-apache

# Configurar repositórios arquivados para Debian Stretch
RUN echo "deb http://archive.debian.org/debian stretch main" > /etc/apt/sources.list && \
    echo "deb-src http://archive.debian.org/debian stretch main" >> /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security stretch/updates main" >> /etc/apt/sources.list && \
    echo "deb-src http://archive.debian.org/debian-security stretch/updates main" >> /etc/apt/sources.list

# Instalar dependências do sistema operacional (GD e ZIP)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    --no-install-recommends \
	--allow-unauthenticated

# Instalar extensões PHP necessárias (incluindo GD e ZIP)
RUN docker-php-ext-install mysqli pdo pdo_mysql gd zip

# Instalar o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar todo o projeto para o contêiner
COPY . /var/www/html

# Verificar se o composer.json foi copiado corretamente (remover após validação)
RUN ls -la /var/www/html

# Definir o diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do Composer
RUN composer install --no-dev --optimize-autoloader

# Ajustar permissões (opcional, mas recomendado)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Se seu projeto usa uma pasta "public" como raiz do site, ajuste o document root do Apache
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Habilitar módulos do Apache (ex: URL reescrita)
RUN a2enmod rewrite expires headers

# Ativar exibição de erros do PHP (útil para desenvolvimento)
RUN echo "display_errors = On" >> /usr/local/etc/php/php.ini

# Expor a porta 80 do contêiner
EXPOSE 80