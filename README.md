# 3C Management System

A PHP-based MVC architecture backend system for managing 3C (Computer, Communication, Consumer Electronics) product sales, inventory, and customer service. This system provides a complete backend infrastructure for e-commerce operations specializing in electronics and technology products.

## Project Structure

The project follows an MVC architecture with the following structure:

- `/app`: Contains the main application code
  - `/Controllers`: Controller classes handling HTTP requests
  - `/Models`: Model classes for database operations
- `/core`: Core framework classes
  - `Router.php`: Handles URL routing
  - `Controller.php`: Base controller class
  - `DB.php`: Database connection management
- `/public`: Web-accessible files
  - `index.php`: Application entry point
  - `.htaccess`: URL rewriting rules
- `/routes`: Route definitions
  - `web.php`: Defines API routes
- `/database`: Database setup scripts
  - `init.sql`: Database initialization script

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Composer (for dependency management)

## Installation

1. Clone the repository to your web server directory:
   ```
   git clone https://github.com/yourusername/3Cmanage.git
   ```

2. Navigate to the project directory and install dependencies:
   ```
   cd 3Cmanage
   composer install
   ```

3. Create a database and run the initialization script:
   ```
   mysql -u root -p < database/init.sql
   ```

4. Create a `.env` file in the root directory based on the example:
   ```
   cp .env.example .env
   ```
   Then edit the `.env` file with your database credentials.

5. Configure your web server to point to the `public` directory.

6. Make sure the Apache mod_rewrite is enabled.

## API Endpoints

### Guest Routes (No login required)
- `GET /products` - View all products with specifications
- `GET /products/{id}` - View a specific product with specs
- `POST /register` - Register an account
- `POST /login` - Login to account

### Customer Routes (Login required)
- `POST /logout` - Logout
- `GET /my/addresses` - List all addresses
- `POST /my/addresses` - Add a new address
- `PUT /my/addresses/{id}` - Update an address
- `DELETE /my/addresses/{id}` - Delete an address
- `POST /my/addresses/{id}/set-default` - Set an address as default
- `POST /orders` - Place a new order
- `GET /orders` - View order history
- `GET /orders/{id}` - View specific order details
- `GET /my-devices` - List all devices owned
- `GET /my-devices/warranty/{warrantyId}` - Check warranty details for a device

### Store Admin Routes (Login and store_admin role required)
- `GET /admin/products` - List all products
- `POST /admin/products` - Add a new product
- `GET /admin/products/{id}` - View product details
- `PUT /admin/products/{id}` - Update a product
- `DELETE /admin/products/{id}` - Delete a product
- `POST /admin/products/{id}/adjust-stock` - Manually adjust product stock
- `GET /admin/products/{id}/inventory-logs` - View product inventory logs
- `GET /admin/orders` - List all customer orders
- `GET /admin/orders/{id}` - View specific order details
- `PUT /admin/orders/{id}/status` - Update order status

## System Features

The 3C Management System provides the following key features:

### Public/Guest Features
- Browse and search products by various criteria
- View detailed product information and specifications
- Register for a customer account

### Customer Features
- User authentication (login/logout)
- Address management (add, edit, delete, set default)
- Order placement and management
- Order history viewing
- Product warranty tracking

### Admin Features
- Product management (add, update, delete products)
- Inventory management with change logging
- Order management and fulfillment
- Order status updates

## Default Admin Account

The system is initialized with a default admin account:
- Username: `admin`
- Email: `admin@example.com`
- Password: `password`

## API Documentation

For detailed API documentation, see [API_DOCS.md](API_DOCS.md)
