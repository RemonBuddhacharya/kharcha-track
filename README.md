# Kharcha Track

A modern expense tracking application built with Laravel 12, Livewire, and MaryUI.

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js & NPM/Yarn
- MySQL/PostgreSQL

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd kharcha-track
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
yarn install
```

4. Set up environment file:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure your database in `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kharcha_track
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

7. Build assets:
```bash
yarn build
```

## Development

1. Start the development server:
```bash
php artisan serve
```

2. Watch for asset changes:
```bash
yarn dev
```

3. Run both simultaneously:
```bash
composer dev
```

## Features

- Modern UI with MaryUI and Tailwind CSS
- Real-time updates with Livewire
- Role-based access control with Spatie Permissions
- Material Design components
- Dark/Light theme support
- Responsive design

## Testing

Run the test suite:
```bash
php artisan test
```

## Code Style

This project follows PSR-12 coding standards. To format your code:
```bash
./vendor/bin/pint
```

## Security

If you discover any security-related issues, please email [your-email] instead of using the issue tracker.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
