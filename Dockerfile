FROM php:8.3-cli

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Make start script executable
RUN chmod +x /app/start.sh

# Expose port (Railway will set $PORT)
EXPOSE 8080

# Start PHP built-in server using entrypoint script
CMD ["/app/start.sh"]
