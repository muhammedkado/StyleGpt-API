# StyleGPT API

A Laravel-based API service for AI-powered room styling and image transformation.

## Overview

StyleGPT API is a powerful backend service that provides:
- AI-powered room style transformation
- User management with subscription capabilities
- Image processing and storage
- Integration with Paddle for payments
- Public exploration of transformed room designs

## Requirements

- PHP 8.0 or higher
- Composer
- MySQL/MariaDB
- Google Cloud Storage account
- Paddle account for payments
- Replicate API access
- SerpAPI access for product search

## Installation

1. Clone the repository:
```bash
git clone https://github.com/muhammedkado/StyleGpt-API.git
cd StyleGpt-API
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file and configure:
```bash
cp .env.example .env
```

4. Configure the following environment variables:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

PADDLE_API_KEY=your_paddle_api_key
PADDLE_WEBHOOK_SECRET=your_paddle_webhook_secret

REPLICATE_API_TOKEN=your_replicate_token

GOOGLE_CLOUD_PROJECT_ID=your_project_id
GOOGLE_CLOUD_STORAGE_BUCKET=your_bucket_name

SERPAPI_API_KEY=your_serp_api_key
```

5. Run migrations:
```bash
php artisan migrate
```

## Features

### User Management
- User registration and authentication
- Credit system for image transformations
- Subscription management via Paddle

### Image Processing
- AI-powered room style transformation
- Before/after image comparison
- Theme and room type categorization
- Google Cloud Storage integration

### Exploration
- Public gallery of transformed rooms
- Administrative control over published content
- Random exploration of successful transformations

### Payment Integration
- Paddle payment processing
- Subscription management
- Credit purchase system

## API Endpoints

### User Endpoints
- `POST /api/users/create` - Create new user
- `GET /api/users/{uid}` - Get user by UID
- `GET /api/users/{uid}/images` - Get user's images

### Image Generation
- `POST /api/generate` - Generate transformed room image

### Exploration
- `GET /api/explore` - Get random published images
- `GET /api/admin/explore` - Get all published images (admin)
- `POST /api/publish` - Update image publish status
- `POST /api/admin/publish` - Update image explore status

### Payment
- `POST /api/payment/create` - Create payment transaction
- `POST /api/payment/webhook` - Handle Paddle webhooks

### Product Search
- `POST /api/search/product` - Search for similar products

## Database Schema

The system uses several tables:
- `users` - User information and credits
- `images` - Stored transformations and metadata
- Migrations handle:
  - Customer IDs
  - Subscription status
  - Credits
  - Publishing timestamps

## Security

- Webhook signature verification for Paddle
- Google Cloud Storage secure upload
- API authentication
- Database transaction management

## Testing

Run the test suite:
```bash
php artisan test
```
