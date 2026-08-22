# BookMyHotel

A five-star hotel reservation platform for four international chains across Asia
and Europe. Guests search live availability, book and pay for stays, review and
cancel them, and earn reward points; hotel managers maintain rates and
promotions; administrators manage the catalogue and view booking analytics.

Built as a Laravel 13 + Inertia (React) single application with server-side
session authentication.

---

## Tech stack

| Layer      | Technology                                                            |
| ---------- | --------------------------------------------------------------------- |
| Backend    | PHP 8.4, Laravel 13                                                    |
| Auth       | Laravel Fortify (session-based), Chisel starter kit                   |
| Frontend   | Inertia.js v3, React 19, TypeScript, Vite, Tailwind CSS v4, shadcn/ui |
| Database   | MySQL 8                                                                |
| Tooling    | Pint, PHPStan (larastan), PHPUnit, ESLint, Prettier, `tsc`            |

The domain logic lives in bounded modules under `app/Modules` — `Availability`
(search, calendar, concurrency-safe booking) and `Payment` (a tokenised gateway
behind a driver interface, with a `FakeGateway` for local/dev use). Controllers
stay thin; validation is in Form Requests, authorisation in policies and a
`role` middleware, and cross-module effects (points, confirmation email) run
through events and listeners.

---

## Prerequisites

- **PHP 8.4+** — although `composer.json` lists `^8.3`, the locked dependencies
  (Laravel 13 / Symfony 8.1 components) require **PHP ≥ 8.4.1**. Developed on
  8.4.11; CI runs on 8.4.
- **Composer 2**
- **Node.js 22+** and **npm**
- **MySQL 8** (two schemas: one for the app, one for the tests)

---

## Getting started

```bash
# 1. Clone
git clone https://github.com/Numair2700/bookMyHotel.git
cd bookMyHotel

# 2. Environment
cp .env.example .env
#   then edit .env and set your DB credentials:
#     DB_DATABASE=bookmyhotel
#     DB_USERNAME=<your mysql user>
#     DB_PASSWORD=<your mysql password>

# 3. PHP dependencies + app key
composer install
php artisan key:generate

# 4. Create the databases (app + tests)
mysql -u root -p -e "CREATE DATABASE bookmyhotel;      CREATE DATABASE bookmyhotel_test;"

# 5. Migrate and seed the demo data
php artisan migrate
php artisan db:seed

# 6. Frontend dependencies + build
npm install
npm run build

# 7. Serve
php artisan serve        # http://localhost:8000
```

Then open **http://localhost:8000**.

> **Shortcut:** `composer setup` runs install → env → key → migrate → npm
> install → build in one step. You still need to create the databases (step 4)
> and seed (`php artisan db:seed`) yourself.

> **Local dev with hot reload:** instead of step 6–7 use `composer dev`, which
> runs the PHP server, the Vite dev server, the queue worker and log tailing
> together. (Requires `npm install` first.)

### Notes on environment

- **Mail & queue:** `MAIL_MAILER=log` and `QUEUE_CONNECTION=database`. The
  booking-confirmation email is queued and written to `storage/logs/laravel.log`
  — no mail server needed. To actually process the queued job, run
  `php artisan queue:work`.
- **Payments:** the default gateway is the in-app **FakeGateway** (no Stripe or
  network calls). Card details are tokenised client-side; use the "simulate
  decline" control on the payment panel to exercise the failure path.

---

## Demo accounts

All seeded users share the password **`password`**. One documented login per
role:

| Role        | Email                      | Lands on / can do                                   |
| ----------- | -------------------------- | --------------------------------------------------- |
| Guest       | `guest@bookmyhotel.test`   | Search, book, pay, review, cancel, rewards. Comes with demo bookings (completed + cancelled) so the review and cancellation flows are ready to try. |
| Manager     | `manager@bookmyhotel.test` | Manage promotions and edit nightly availability/rates. |
| Admin       | `admin@bookmyhotel.test`   | Manage the hotel catalogue, view all reservations, and the analytics dashboard. |

The seed also creates 24 more guests and 3 more managers (`guest2…25@…`,
`manager2…4@bookmyhotel.test`).

---

## Seeded data

- **Catalogue:** 4 chains × 2 hotels = **8 hotels** (one Asian, one European
  each), **3 room types** per hotel (Deluxe King, Executive Suite, Family Room),
  physical rooms, 4 on-site services per hotel, one cancellation policy each and
  two active promotions.
- **Availability:** 180 days of nightly rates and inventory per room type from
  today. Weekend nights (Sat/Sun) are priced **+20%** over the weekday base
  rate.
- **Users:** 30 total — 1 admin, 4 managers, 25 guests.
- **Demo reservations:** pre-seeded bookings for the guest account.

---

## Pricing and analytics — two different numbers

- **Quoted price (shown to guests):** each room type has a base rate; the
  availability calendar stores a rate per night (base on weekdays, +20% on
  weekend nights). Search results and the hotel page show the **average nightly
  rate for the chosen dates**, so a stay spanning a weekend averages above base.
  Each night's rate is copied onto the reservation when it is booked.
- **Average Daily Rate (ADR), on the admin dashboard:** **room revenue ÷ room
  nights**, computed over the nights of confirmed/completed reservations. It is a
  realised, backward-looking figure — distinct from the forward-looking quote.

---

## Running the tests

The test suite uses the separate `bookmyhotel_test` schema (configured in
`phpunit.xml`); it is created in step 4 above.

```bash
php artisan test          # full PHPUnit suite

composer ci:check         # everything CI runs:
                          # eslint + prettier + tsc + pint + phpstan + phpunit
```

---

## Feature overview

- **Guest:** availability search with filters (destination, dates, guests,
  price, room type, sustainability, workspace); hotel property pages;
  concurrency-safe booking; tokenised payment; ancillary service booking;
  reviews; cancellation with policy-based refund; reward points.
- **Manager:** create/withdraw promotions; edit nightly availability and rates.
- **Admin:** manage hotels; view all reservations; analytics dashboard (room
  nights, room revenue, ADR, per-hotel breakdown).

Placeholder photography under `public/images/hotels` is sourced from Pexels
(Pexels License) and mapped to hotels/rooms at runtime — see that folder's
`CREDITS.txt`; no image data is stored in the database.
