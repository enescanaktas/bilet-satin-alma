# Bilet Satın Alma

A PHP 8 web application for ticket sales built for Siber Vatan. This application provides a complete ticket booking system with seat selection, gender-based seat color coding, and PDF ticket generation.

## Features

- **CSRF-Protected Login System**: Secure authentication with CSRF token validation
- **Interactive Seat Selection**: Visual seat map with radio buttons and labels
- **Gender-Based Seat Colors**: 
  - 🟢 Green: Available seats
  - 🔵 Blue: Seats booked by male passengers
  - 🔴 Pink: Seats booked by female passengers
- **SQLite Database**: Lightweight database with PDO for bookings including passenger gender
- **PDF Ticket Generation**: Automatic PDF ticket creation using Dompdf
- **Idempotent Migrations**: Database initialization with migration tracking
- **Docker Support**: Full containerization with Docker and docker-compose
- **Automated Testing**: PHPUnit tests with GitHub Actions CI
- **MVC Architecture**: Clean separation with routes, controllers, views, and assets

## Requirements

- PHP 8.0 or higher
- Composer
- SQLite3
- Docker (optional, for containerized deployment)

## Installation

### Local Setup

1. Clone the repository:
```bash
git clone https://github.com/enescanaktas/bilet-satin-alma.git
cd bilet-satin-alma
```

2. Install dependencies:
```bash
composer install
```

3. Initialize the database:
```bash
php init_db.php
```

4. Start PHP built-in server:
```bash
php -S localhost:8000 -t public
```

5. Access the application at http://localhost:8000

### Docker Setup

1. Build and run with docker-compose:
```bash
docker-compose up -d
```

2. Access the application at http://localhost:8080

3. Stop the container:
```bash
docker-compose down
```

## Default Credentials

- **Username**: admin
- **Password**: admin123

## Database Structure

The application uses SQLite with the following tables:

### Users
- `id`: Primary key
- `username`: Unique username
- `password`: Hashed password
- `created_at`: Timestamp

### Seats
- `id`: Primary key
- `seat_number`: Seat identifier (A1-A10, B1-B10, C1-C10)
- `is_available`: Boolean availability flag
- `created_at`: Timestamp

### Bookings
- `id`: Primary key
- `seat_id`: Foreign key to seats
- `passenger_name`: Name of passenger
- `passenger_gender`: Gender (male/female) - used for seat color coding
- `user_id`: Foreign key to users
- `booking_code`: Unique booking reference
- `created_at`: Timestamp

## Migrations

The database uses an idempotent migration system. Migrations are stored in the `migrations/` directory and tracked in the `migrations` table. Running `init_db.php` multiple times is safe - it will only execute new migrations.

### Creating a New Migration

Create a new PHP file in the `migrations/` directory:

```php
<?php
// migrations/004_add_new_feature.php

return function($pdo) {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN new_column VARCHAR(255)");
    echo "  - New column added\n";
};
```

## Project Structure

```
bilet-satin-alma/
├── public/              # Web root
│   └── index.php       # Application entry point
├── src/                # Application source code
│   ├── Controllers/    # Request handlers
│   ├── Models/         # Database models
│   ├── Database.php    # Database connection
│   ├── Router.php      # URL routing
│   ├── Session.php     # Session management
│   └── CSRF.php        # CSRF protection
├── views/              # HTML templates
│   ├── auth/          # Authentication views
│   ├── seats/         # Seat selection views
│   └── bookings/      # Booking confirmation views
├── assets/            # Static assets
│   ├── css/          # Stylesheets
│   └── js/           # JavaScript files
├── database/          # SQLite database files
├── migrations/        # Database migrations
├── tests/            # PHPUnit tests
│   ├── Unit/        # Unit tests
│   └── Integration/ # Integration tests
├── .github/          # GitHub Actions workflows
├── Dockerfile        # Docker image definition
├── docker-compose.yml # Docker Compose configuration
├── init_db.php       # Database initialization script
├── composer.json     # PHP dependencies
└── phpunit.xml       # PHPUnit configuration
```

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

Run specific test suites:

```bash
# Unit tests only
vendor/bin/phpunit --testsuite Unit

# Integration tests only
vendor/bin/phpunit --testsuite Integration
```

## API Routes

- `GET /` - Redirects to login
- `GET /login` - Display login form
- `POST /login` - Process login (CSRF protected)
- `GET /logout` - Logout user
- `GET /seats` - Display seat selection (requires authentication)
- `POST /booking/create` - Create new booking (CSRF protected)
- `GET /booking/{code}` - Display booking confirmation
- `GET /booking/{code}/pdf` - Download PDF ticket

## CI/CD

The project includes GitHub Actions workflows for:

- Running PHPUnit tests
- Validating composer.json
- Building Docker images
- Testing Docker containers

The CI pipeline runs on every push and pull request to the main branch.

## Security Features

- **CSRF Protection**: All form submissions are protected with CSRF tokens
- **Password Hashing**: Passwords are hashed using PHP's `password_hash()`
- **Session Security**: Session regeneration on login
- **SQL Injection Protection**: Prepared statements with PDO
- **XSS Protection**: HTML escaping in all views

## License

This project is licensed under the terms specified in the LICENSE file.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Support

For issues and questions, please use the GitHub issue tracker.
