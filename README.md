# TaskFlow – Personal Task Manager

A full-stack task manager built with **Laravel 13**, **Vue 3**, **Inertia.js** and **SQL**.

Students and freelancers juggle assignments, deadlines and side projects across notes apps and chats. TaskFlow gives every user a private dashboard to capture tasks, set **priority** and **due dates**, track **status**, and quickly find what's urgent.

> 🚧 Work in progress: features are being added step by step (see the roadmap below).

## User stories

1. As a visitor, I can **register and log in** so my tasks are private.
2. As a user, I can **create a task** with a title, description, priority and due date.
3. As a user, I can **view, edit, complete and delete** my tasks.
4. As a user, I can **search and filter** tasks by status and priority, with pagination.
5. As a user, I can only ever see and change **my own** tasks.
6. As a developer, I can manage tasks through a **REST API** using a token.

## Roadmap

- [x] Project setup: Laravel 13 + Vue 3 starter kit, Pest, SQLite
- [x] Authentication: register, login, password reset, profile settings (starter kit)
- [x] `tasks` table: migration with a foreign key to `users`
- [x] `Task` Eloquent model, enums for status/priority, `User hasMany Task` relationship
- [x] Task list page in Vue
- [x] Create task form with validation (Form Request)
- [x] Edit, complete and delete tasks
- [x] Authorization with a Policy (users only access their own tasks)
- [x] Search, status/priority filters and pagination
- [x] Dashboard with task statistics and upcoming deadlines
- [x] Feature tests with Pest
- [x] Seeders and factories for demo data
- [ ] REST API (`/api/tasks`) with API Resources and Sanctum tokens

## Tech stack

| Layer    | Technology                                                       |
| -------- | ---------------------------------------------------------------- |
| Backend  | PHP 8.4, Laravel 13                                              |
| Database | SQLite (development), MySQL compatible, Eloquent ORM, migrations |
| Frontend | Vue 3 (Composition API, TypeScript), Inertia.js, Tailwind CSS    |
| Auth     | Laravel Fortify (starter kit), Sanctum for the API               |
| Testing  | Pest                                                             |
| Tooling  | Vite, Composer, npm                                              |

## Architecture

```
Vue 3 pages (resources/js/pages) ──Inertia──► Laravel routes (routes/web.php)
                                                   │
                                     middleware (auth) → controllers → validation
                                                   │
                                     Eloquent models (app/Models) ──► SQL database
```

## Getting started

**Requirements:** PHP 8.2+, Composer, Node.js 20+ (for example via [Laravel Herd](https://herd.laravel.com)).

```bash
git clone https://github.com/YasithaNV2001/TaskFlow.git
cd TaskFlow
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # creates tables + a demo user with sample tasks
composer run dev
```

Open http://localhost:8000 and log in with the seeded demo account: `test@example.com` / `password`.

### Run the tests

```bash
php artisan test
```
