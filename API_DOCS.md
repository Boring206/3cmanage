# 3C Management System API Documentation

This document provides details about the available API endpoints in the 3C Management System.

## Authentication

### Register a New User
- **URL**: `/register`
- **Method**: `POST`
- **Auth Required**: No
- **Body**:
```json
{
  "username": "customer1",
  "email": "customer1@example.com",
  "password": "secure123",
  "name": "Customer One"
}
```
- **Success Response**: 201 Created
```json
{
  "message": "User registered successfully.",
  "user": {
    "id": 2,
    "username": "customer1",
    "email": "customer1@example.com",
    "name": "Customer One",
    "role": "customer",
    "created_at": "2023-05-16 10:00:00"
  }
}
```

### Login
- **URL**: `/login`
- **Method**: `POST`
- **Auth Required**: No
- **Body**:
```json
{
  "email": "customer1@example.com",
  "password": "secure123"
}
```
- **Success Response**: 200 OK
```json
{
  "message": "Login successful.",
  "user": {
    "id": 2,
    "username": "customer1",
    "email": "customer1@example.com",
    "name": "Customer One",
    "role": "customer"
  }
}
```

### Logout
- **URL**: `/logout`
- **Method**: `POST`
- **Auth Required**: Yes
- **Success Response**: 200 OK
```json
{
  "message": "Logged out successfully."
}
```

## Products

### List All Products
- **URL**: `/products`
- **Method**: `GET`
- **Auth Required**: No
- **Query Parameters**: 
  - `page`: Page number (default: 1)
  - `limit`: Items per page (default: 10)
  - `category`: Filter by category
  - `brand`: Filter by brand
  - `search`: Search in name, description, brand, model
  - `min_price`: Minimum price
  - `max_price`: Maximum price
  - `order_by`: Sort field (default: created_at)
  - `order_direction`: ASC or DESC (default: DESC)

- **Success Response**: 200 OK
```json
{
  "data": [
    {
      "id": 1,
      "name": "iPhone 15 Pro",
      "description": "The latest iPhone with A17 Pro chip",
      "category": "Smartphone",
      "brand": "Apple",
      "model_number": "iPhone15Pro",
      "specifications": {
        "screen": "6.1 inch", 
        "memory": "8GB", 
        "storage": "256GB", 
        "color": "Titanium Blue"
      },
      "price": 32900,
      "stock_quantity": 50,
      "image_url": "https://example.com/images/iphone15pro.jpg",
      "default_warranty_months": 12,
      "created_at": "2023-05-16 10:00:00",
      "updated_at": "2023-05-16 10:00:00"
    },
    // More products...
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 45,
    "filters": {
      "categories": ["Smartphone", "Laptop", "Tablet"],
      "brands": ["Apple", "Samsung", "Asus", "Dell"]
    }
  }
}
```

### View Product Details
- **URL**: `/products/{id}`
- **Method**: `GET`
- **Auth Required**: No
- **Success Response**: 200 OK
```json
{
  "data": {
    "id": 1,
    "name": "iPhone 15 Pro",
    "description": "The latest iPhone with A17 Pro chip",
    "category": "Smartphone",
    "brand": "Apple",
    "model_number": "iPhone15Pro",
    "specifications": {
      "screen": "6.1 inch", 
      "memory": "8GB", 
      "storage": "256GB", 
      "color": "Titanium Blue"
    },
    "price": 32900,
    "stock_quantity": 50,
    "image_url": "https://example.com/images/iphone15pro.jpg",
    "default_warranty_months": 12,
    "created_at": "2023-05-16 10:00:00",
    "updated_at": "2023-05-16 10:00:00"
  }
}
```

## Address Management

### List User Addresses
- **URL**: `/my/addresses`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**: 200 OK
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "recipient_name": "Customer One",
      "phone_number": "0912345678",
      "postal_code": "106",
      "city": "Taipei",
      "street": "Xinyi Rd, Section 5, No. 123",
      "country": "Taiwan",
      "is_default": 1,
      "created_at": "2023-05-16 10:00:00",
      "updated_at": "2023-05-16 10:00:00"
    },
    // More addresses...
  ]
}
```

### Add New Address
- **URL**: `/my/addresses`
- **Method**: `POST`
- **Auth Required**: Yes
- **Body**:
```json
{
  "recipient_name": "Customer One",
  "phone_number": "0912345678",
  "postal_code": "106",
  "city": "Taipei",
  "street": "Xinyi Rd, Section 5, No. 123",
  "country": "Taiwan",
  "is_default": 1
}
```
- **Success Response**: 201 Created
```json
{
  "message": "Address added successfully.",
  "address": {
    "id": 1,
    "user_id": 2,
    "recipient_name": "Customer One",
    "phone_number": "0912345678",
    "postal_code": "106",
    "city": "Taipei",
    "street": "Xinyi Rd, Section 5, No. 123",
    "country": "Taiwan",
    "is_default": 1,
    "created_at": "2023-05-16 10:00:00",
    "updated_at": "2023-05-16 10:00:00"
  }
}
```

### Update Address
- **URL**: `/my/addresses/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Body**:
```json
{
  "recipient_name": "Customer One",
  "phone_number": "0912345678",
  "postal_code": "106",
  "city": "Taipei",
  "street": "Xinyi Rd, Section 5, No. 456",
  "country": "Taiwan"
}
```
- **Success Response**: 200 OK
```json
{
  "message": "Address updated successfully.",
  "address": {
    "id": 1,
    "user_id": 2,
    "recipient_name": "Customer One",
    "phone_number": "0912345678",
    "postal_code": "106",
    "city": "Taipei",
    "street": "Xinyi Rd, Section 5, No. 456",
    "country": "Taiwan",
    "is_default": 1,
    "created_at": "2023-05-16 10:00:00",
    "updated_at": "2023-05-16 10:30:00"
  }
}
```

### Delete Address
- **URL**: `/my/addresses/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Success Response**: 200 OK
```json
{
  "message": "Address deleted successfully."
}
```

### Set Default Address
- **URL**: `/my/addresses/{id}/set-default`
- **Method**: `POST`
- **Auth Required**: Yes
- **Success Response**: 200 OK
```json
{
  "message": "Default address set successfully.",
  "address": {
    "id": 1,
    "user_id": 2,
    "recipient_name": "Customer One",
    "phone_number": "0912345678",
    "postal_code": "106",
    "city": "Taipei",
    "street": "Xinyi Rd, Section 5, No. 456",
    "country": "Taiwan",
    "is_default": 1,
    "created_at": "2023-05-16 10:00:00",
    "updated_at": "2023-05-16 10:45:00"
  }
}
```

## Order Management

### Create Order
- **URL**: `/orders`
- **Method**: `POST`
- **Auth Required**: Yes
- **Body**:
```json
{
  "address_id": 1,
  "payment_method": "credit_card",
  "transaction_id": "TXN123456789",
  "notes": "Please deliver in the morning",
  "items": [
    {
      "product_id": 1,
      "quantity": 1
    },
    {
      "product_id": 3,
      "quantity": 2
    }
  ]
}
```
- **Success Response**: 201 Created
```json
{
  "message": "Order placed successfully.",
  "order": {
    "id": 1,
    "user_id": 2,
    "address_id": 1,
    "order_date": "2023-05-16 11:00:00",
    "status": "pending",
    "subtotal_amount": 78700,
    "shipping_fee": 0,
    "discount_amount": 0,
    "total_amount": 78700,
    "payment_method": "credit_card",
    "transaction_id": "TXN123456789",
    "notes": "Please deliver in the morning",
    "created_at": "2023-05-16 11:00:00",
    "updated_at": "2023-05-16 11:00:00"
  },
  "items": [
    {
      "id": 1,
      "order_id": 1,
      "product_id": 1,
      "product_name": "iPhone 15 Pro",
      "brand": "Apple",
      "model_number": "iPhone15Pro",
      "quantity": 1,
      "price_at_purchase": 32900,
      "image_url": "https://example.com/images/iphone15pro.jpg"
    },
    {
      "id": 2,
      "order_id": 1,
      "product_id": 3,
      "product_name": "MacBook Air M2",
      "brand": "Apple",
      "model_number": "MBA-M2-2023",
      "quantity": 2,
      "price_at_purchase": 42900,
      "image_url": "https://example.com/images/macbookair-m2.jpg"
    }
  ]
}
```

### List User Orders
- **URL**: `/orders`
- **Method**: `GET`
- **Auth Required**: Yes
- **Query Parameters**:
  - `page`: Page number (default: 1)
  - `limit`: Items per page (default: 10)
- **Success Response**: 200 OK
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "address_id": 1,
      "order_date": "2023-05-16 11:00:00",
      "status": "pending",
      "subtotal_amount": 78700,
      "shipping_fee": 0,
      "discount_amount": 0,
      "total_amount": 78700,
      "payment_method": "credit_card",
      "transaction_id": "TXN123456789",
      "notes": "Please deliver in the morning",
      "created_at": "2023-05-16 11:00:00",
      "updated_at": "2023-05-16 11:00:00"
    },
    // More orders...
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

### View Order Details
- **URL**: `/orders/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**: 200 OK
```json
{
  "data": {
    "order": {
      "id": 1,
      "user_id": 2,
      "address_id": 1,
      "order_date": "2023-05-16 11:00:00",
      "status": "pending",
      "subtotal_amount": 78700,
      "shipping_fee": 0,
      "discount_amount": 0,
      "total_amount": 78700,
      "payment_method": "credit_card",
      "transaction_id": "TXN123456789",
      "notes": "Please deliver in the morning",
      "created_at": "2023-05-16 11:00:00",
      "updated_at": "2023-05-16 11:00:00"
    },
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 1,
        "product_name": "iPhone 15 Pro",
        "brand": "Apple",
        "model_number": "iPhone15Pro",
        "quantity": 1,
        "price_at_purchase": 32900,
        "image_url": "https://example.com/images/iphone15pro.jpg"
      },
      {
        "id": 2,
        "order_id": 1,
        "product_id": 3,
        "product_name": "MacBook Air M2",
        "brand": "Apple",
        "model_number": "MBA-M2-2023",
        "quantity": 2,
        "price_at_purchase": 42900,
        "image_url": "https://example.com/images/macbookair-m2.jpg"
      }
    ]
  }
}
```

## Admin API Endpoints

These endpoints require authentication with an admin account (role = 'store_admin').

### List All Products (Admin)
- **URL**: `/admin/products`
- **Method**: `GET`
- **Auth Required**: Yes (Admin)
- **Query Parameters**: Same as public product listing
- **Success Response**: 200 OK (Same format as public product listing)

### Add New Product
- **URL**: `/admin/products`
- **Method**: `POST`
- **Auth Required**: Yes (Admin)
- **Body**:
```json
{
  "name": "Samsung Galaxy S24",
  "description": "Latest Samsung flagship phone",
  "category": "Smartphone",
  "brand": "Samsung",
  "model_number": "SM-S24U",
  "specifications": {
    "screen": "6.8 inch",
    "processor": "Snapdragon 8 Gen 3",
    "memory": "12GB",
    "storage": "512GB"
  },
  "price": 28900,
  "stock_quantity": 30,
  "image_url": "https://example.com/images/samsung-s24.jpg",
  "default_warranty_months": 12
}
```
- **Success Response**: 201 Created
```json
{
  "message": "Product created successfully.",
  "product": {
    "id": 5,
    "name": "Samsung Galaxy S24",
    "description": "Latest Samsung flagship phone",
    "category": "Smartphone",
    "brand": "Samsung",
    "model_number": "SM-S24U",
    "specifications": {
      "screen": "6.8 inch",
      "processor": "Snapdragon 8 Gen 3",
      "memory": "12GB",
      "storage": "512GB"
    },
    "price": 28900,
    "stock_quantity": 30,
    "image_url": "https://example.com/images/samsung-s24.jpg",
    "default_warranty_months": 12,
    "created_at": "2023-05-17 09:00:00",
    "updated_at": "2023-05-17 09:00:00"
  }
}
```

### Adjust Product Stock
- **URL**: `/admin/products/{id}/adjust-stock`
- **Method**: `POST`
- **Auth Required**: Yes (Admin)
- **Body**:
```json
{
  "change_in_quantity": 10,
  "reason": "Additional stock received from supplier"
}
```
- **Success Response**: 200 OK
```json
{
  "message": "Stock adjusted successfully.",
  "product": {
    "id": 1,
    "name": "iPhone 15 Pro",
    "stock_quantity": 60,
    "updated_at": "2023-05-17 10:00:00"
    // Other product details...
  }
}
```

### View Inventory Logs
- **URL**: `/admin/products/{id}/inventory-logs`
- **Method**: `GET`
- **Auth Required**: Yes (Admin)
- **Query Parameters**:
  - `page`: Page number (default: 1)
  - `limit`: Items per page (default: 20)
- **Success Response**: 200 OK
```json
{
  "data": [
    {
      "id": 2,
      "product_id": 1,
      "user_id": 1,
      "admin_username": "admin",
      "change_in_quantity": 10, 
      "reason": "Additional stock received from supplier",
      "log_date": "2023-05-17 10:00:00"
    },
    {
      "id": 1,
      "product_id": 1,
      "user_id": 1,
      "admin_username": "admin",
      "change_in_quantity": 50,
      "reason": "Initial stock on product creation",
      "log_date": "2023-05-16 10:00:00"
    }
  ],
  "product": {
    "id": 1,
    "name": "iPhone 15 Pro",
    "current_stock": 60
  },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 2
  }
}
```

### List All Orders (Admin)
- **URL**: `/admin/orders`
- **Method**: `GET`
- **Auth Required**: Yes (Admin)
- **Query Parameters**:
  - `page`: Page number (default: 1)
  - `limit`: Items per page (default: 10)
  - `status`: Filter by order status
  - `user_id`: Filter by user ID
  - `date_from`: Filter by date range start
  - `date_to`: Filter by date range end
  - `order_by`: Sort field (default: created_at)
  - `order_direction`: ASC or DESC (default: DESC)
- **Success Response**: 200 OK
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "customer_username": "customer1",
      "customer_email": "customer1@example.com",
      "order_date": "2023-05-16 11:00:00",
      "status": "pending",
      "total_amount": 78700,
      "payment_method": "credit_card",
      "recipient_name": "Customer One",
      "city": "Taipei",
      // Other order details...
    },
    // More orders...
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

### Update Order Status (Admin)
- **URL**: `/admin/orders/{id}/status`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin)
- **Body**:
```json
{
  "status": "shipped"
}
```
- **Success Response**: 200 OK
```json
{
  "message": "Order status updated successfully.",
  "status": "shipped"
}
```
