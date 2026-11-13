# Laravel Task Manager

A lightweight task management web app built with **Laravel 13**, **PHP 8.3+**, **MySQL**, **Tailwind CSS**, and **SortableJS**. Organize tasks by project, manage them inline, and drag-and-drop rows to automatically recalculate priority order.

## Requirements

- PHP 8.3+
- Composer
- Node.js & NPM
- MySQL 8.0+ (or MariaDB)

## Setup & Installation

### 1. Clone and Install Dependencies
```bash
git clone https://github.com/arifhussain88/task-manager.git
cd task-manager
composer install
npm install
```

### 2. Configure Environment
Copy the example environment file and generate your app key:
```bash
cp .env.example .env
php artisan key:generate
```

Update your MySQL credentials in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_0900_ai_ci
```

### 3. Set Up the MySQL Database
Create an empty `task_manager` database in MySQL:
```sql
CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

Then populate the tables and sample data using **either** option below:
- **Option A (Import SQL dump):** Import the `task_manager.sql` file located in the project root into your `task_manager` database via phpMyAdmin, TablePlus, or the MySQL CLI:
  ```bash
  mysql -u root -p task_manager < task_manager.sql
  ```
- **Option B (Artisan migrations & seeder):**
  ```bash
  php artisan migrate --seed
  ```

### 4. Build Frontend Assets & Start the Server
Compile Tailwind CSS and SortableJS locally, then launch Laravel's dev server:
```bash
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000` in your browser.

## How to Use

- **Projects:** Use the **Select Project** dropdown to filter tasks by project, or create a new project using the **New Project** input.
- **Create Tasks:** Type a task name and click **+ Add Task**. New tasks are added to the bottom of the active project's priority list.
- **Edit & Delete:** Click **Edit** on any row to rename a task inline, or **Delete** to remove it (remaining task priorities automatically re-index so there are no gaps).
- **Drag-and-Drop Reorder:** Grab any task row and drag it up or down. Dropping a row immediately updates priority numbers (`#1` at the top, `#2` next, and so on) and saves the new order to MySQL.

## Running Tests

Run the automated feature test suite (uses an isolated in-memory SQLite database so your MySQL data stays untouched):
```bash
php artisan test
```
