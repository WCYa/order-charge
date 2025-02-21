# order-charge

## Quick Start

1. Use a non-root account
2. Install composer on the host
3. Use the following commands to start the development server with Laravel Sail
```shell
git https://github.com/WCYa/order-charge.git
cd order-charge
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan key:generate
```
4. Connect to development server: http://localhost
