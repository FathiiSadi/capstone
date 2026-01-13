<p align="center">
    <img src="public/assets/logo.png" alt="Qalam Logo" width="300">
</p>

# Qalam - University Scheduling System

**Qalam** is a powerful academic scheduling and resource management system designed to streamline the complex process of university course planning. Built with **Laravel 12** and **Filament 4**, it provides a modern, responsive, and intuitive interface for administrators, instructors, and staff.

## 🚀 Features

- **Smart Scheduling**: Utilizes advanced algorithms (FIFO Allocator, Section Validators) to prevent conflicts and maximize resource utilization.
- **Course & Section Management**: Comprehensive tools to create, update, and manage courses, academic semesters, and course sections.
- **Room & Resource Allocation**: Efficiently assign classrooms and labs based on capacity, equipment, and availability.
- **Instructor Management**: distinct profiles for instructors with preference tracking and teaching load management.
- **Role-Based Access Control**: Secure environment with granular permissions for Admins, Instructors, and Staff (powered by Spatie Permissions).
- **Excel Integration**: Seamlessly import and export course loads and schedule data.
- **Modern Admin Panel**: A beautiful, dark-mode accessible user interface powered by FilamentPHP.

## 🛠 Tech Stack

- **Framework**: [Laravel 12](https://laravel.com)
- **Admin Panel**: [Filament 4](https://filamentphp.com)
- **Language**: PHP 8.2+
- **Database**: MySQL / MariaDB / PostgreSQL
- **Frontend**: Blade, Livewire, Alpine.js, TailwindCSS

## 📦 Installation

Follow these steps to set up the project locally:

1. **Clone the Repository**
   ```bash
   git clone <repository_url>
   cd capstone
   ```

2. **Install CMS Dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configure Environment**
   Copy the example environment file and update your database credentials:
   ```bash
   cp .env.example .env
   ```
   *Edit `.env` and set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.*

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations & Seeds**
   Set up the database schema and populate it with initial data:
   ```bash
   php artisan migrate --seed
   ```
   *Note: Check `database/seeders/UserSeeder.php` for default user credentials.*

6. **Start the Development Server**
   ```bash
   php artisan serve
   ```
   Access the admin panel at: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

## 🧪 Running Tests

To ensure the stability of the application, run the test suite:

```bash
php artisan test
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:
1. Fork the project.
2. Create your feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
