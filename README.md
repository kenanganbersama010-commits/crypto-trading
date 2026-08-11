# Crypto Trading Platform

## Tech Stack

- Laravel 12
- PHP 8.2
- MySQL
- Blade
- Tailwind CSS
- Alpine.js
- Vite

## Installation

Clone repository:

```
git clone https://github.com/kenanganbersama010-commits/crypto-trading.git
cd crypto-trading
```

Install PHP dependencies:

```
composer install
```

Install frontend dependencies:

```
npm install
```

Copy environment file:

```
copy .env.example .env
```

Generate application key:

```
php artisan key:generate
```

Configure database credentials in `.env`, then run migrations as needed:

```
php artisan migrate
```

Run frontend build (development):

```
npm run dev
```

Run Laravel:

```
php artisan serve
```

## Git Workflow

Before starting work:

```
git pull origin main
```

After finishing changes:

```
git status
git add .
git commit -m "description of changes"
git push origin main
```

For collaborative work, use feature branches to avoid overwriting each other's changes, e.g. `feature/admin-users`, `feature/deposit-approval`.

Do not commit credentials or secrets. `.env` is excluded via `.gitignore` — only `.env.example` (with placeholder values) is tracked.
