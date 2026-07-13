# Laravel 12 Swoole - Vue3 template

<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
  <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Overview

Laravel 12 as API backend using PHP 8.4.10 with a Swoole server, with role-based access & auth + a Vue3, Tailwind, DaisyUI frontend. 
Complete with a docker-compose.yml containing MariaDB & Redis & Nginx to deploy on a server.

### Optimize images

```php
ImageOptimizer::optimize(storage_path('app/public/' . $pathToImage));
```

### Development Setup

1. Clone the repository
2. Copy `.env.example` to `.env` and fill in real values (DB/Redis passwords, mail
   credentials, Nexi API key, etc.). **Never put real secrets directly into
   `docker-compose.yml`** - it's a tracked file; `docker-compose.yml` reads
   everything from this `.env` automatically (Docker Compose loads a `.env`
   file in the project root for `${VAR}` substitution).
3. Generate an application key locally and put it in `.env` as `APP_KEY=...`:
   `php artisan key:generate --show`
4. Run `docker compose up -d --build`
5. Run storage link: `docker compose exec kvsport php artisan storage:link`
6. Run migrations: `docker compose exec kvsport php artisan migrate`
7. Run seeders: `docker compose exec kvsport php artisan db:seed`
8. Start the Vite dev server: `npm run dev`
9. Access at `127.0.0.1:7654` (or the host port mapped to it in `docker-compose.yml`)

### Environment variables

All variables are documented with safe placeholder values in `.env.example`.
The ones worth calling out:

- `APP_KEY` - encrypts sessions, cookies, and any `encrypted` model cast (e.g.
  2FA secrets). Generate a fresh one per environment; never reuse the same key
  across dev/staging/production, and never commit a real one.
- `DB_PASSWORD` / `REDIS_PASSWORD` - required (`docker-compose.yml` will
  refuse to start without them, by design, rather than silently falling back
  to an insecure default).
- `MAIL_*` - real SMTP credentials for outgoing reservation/verification
  emails. Treat these as sensitive as a database password.
- `NEXI_API_KEY` / `NEXI_BASE_URL` - card-payment gateway credentials; keep
  the sandbox base URL until you're actually ready to take real payments.

### Production deployment notes

- Rotate `APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD`, and all `MAIL_*`
  credentials before going live if this checkout was ever used with shared/
  example values - and never reuse whatever was used for local development.
- `docker-compose.yml`'s `kvsportwebserver` (nginx) service expects real TLS
  certificates mounted from `./ssl` - it is not started by default in local
  dev (only `kvsport`, `kvsportmysql`, and `kvsportredis` are needed there).
- Set `APP_DEBUG=false` and `APP_ENV=production` in `.env` for any
  publicly-reachable deployment.
