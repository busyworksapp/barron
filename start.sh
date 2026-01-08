#!/bin/sh
# Start PHP built-in server with dynamic port from Railway
PORT=${PORT:-8080}
echo "Starting PHP server on 0.0.0.0:$PORT"
php -S 0.0.0.0:$PORT -t .
