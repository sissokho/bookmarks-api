# Bookmarks API

This is a bookmarks REST API built with Laravel. It allows you to perform various actions on bookmarks like creating a bookmark, marking a bookmark as favorite, archiving a bookmark, etc.

## Installation

> Requires PHP 8.1+

### Using Laravel Sail

1. Clone this repository

    ```bash
    git clone https://github.com/sissokho/bookmarks-api.git
    ```

1. Install composer dependencies

    ```bash
    docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs
    ```

1. Copy .env.example to .env file

    ```bash
    cp .env.example .env
    ```

1. Set your `DB_HOST` environment variable within your `.env` file to `mysql`:

    ```bash
    DB_HOST=mysql
    ```

1. Start Sail

    ```bash
    ./vendor/bin/sail up

    # or you may start Sail in "detached" mode if you want to start all of the Docker containers in the background:
    ./vendor/bin/sail up -d
    ```

1. Generate app key

    ```bash
    ./vendor/bin/sail artisan key:generate
    ```

1. Run database migrations

    ```bash
    ./vendor/bin/sail artisan migrate
    ```

### Using php artisan serve

1. Clone this repository

    ```bash
    git clone https://github.com/sissokho/bookmarks-api.git
    ```

1. Install composer dependencies

    ```bash
    composer install
    ```

1. Copy .env.example to .env file

    ```bash
    cp .env.example .env
    ```

1. Create a database

1. Setup a working email driver like [Mailtrap](https://mailtrap.io/). This [tutorial](https://mailtrap.io/blog/send-email-in-laravel) tutorial shows how to set it up with Laravel.

1. Fill in the database and email environment variables in `.env` file

1. Generate app key

    ```bash
    php artisan key:generate
    ```

1. Run database migrations

    ```bash
    php artisan migrate
    ```

1. Run Server

    ```bash
    php artisan serve
    ```

## Documentation

You can find the documentation of the API [here](https://documenter.getpostman.com/view/13085025/2s946h9sVt).
