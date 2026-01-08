FROM php:8.3-cli

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Expose port (Railway will set $PORT)
EXPOSE 8080

# Start PHP built-in server
# Use shell form to properly expand environment variables
CMD sh -c "php -S 0.0.0.0:${PORT:-8080} -t ."
