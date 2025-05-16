#!/bin/bash
# 3Cmanage/setup.sh

echo "========================================"
echo "3C Management System - Database Setup"
echo "========================================"
echo

# Check if mysql command is available
if ! command -v mysql &> /dev/null; then
    echo "Error: MySQL/MariaDB client not found. Please install it first."
    exit 1
fi

# Prompt for MySQL root password
read -sp "Enter MySQL root password (leave blank if none): " MYSQL_PWD
echo

# Set environment variable for MySQL password
export MYSQL_PWD

# Test MySQL connection
echo "Testing MySQL connection..."
if ! mysql -u root -e "SELECT 1" > /dev/null 2>&1; then
    echo "Error: Could not connect to MySQL server. Check your password and try again."
    exit 1
fi

echo "MySQL connection successful!"
echo

# Create database and apply schema
echo "Creating database and applying schema..."
mysql -u root < database/init.sql

if [ $? -eq 0 ]; then
    echo "Database initialized successfully!"
    echo
    echo "Sample admin credentials:"
    echo "  Username: admin"
    echo "  Password: password"
    echo
    echo "Make sure to update the .env file with correct database credentials."
else
    echo "Error: Failed to initialize database."
    exit 1
fi

# Check for .env file
if [ ! -f ".env" ]; then
    echo "Creating .env file from template..."
    cp .env.example .env
    echo "Please edit .env with appropriate settings."
else
    echo ".env file already exists."
fi

echo
echo "Setup completed!"
echo "========================================"
