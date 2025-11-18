# Educational Platform - Setup Guide

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL/PostgreSQL database
- Node.js and npm (optional, for asset compilation)

## Installation Steps

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 3. Database Configuration

Update your `.env` file with database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Migrations and Seeders

```bash
php artisan migrate
php artisan db:seed
```

This will:
- Create all database tables
- Create roles (administrator, developer, educator, content_editor)
- Create divisions (Primary, Junior Intermediate, High School)

### 5. Google OAuth Setup (Optional)

To enable Google login, add these to your `.env` file:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `http://your-domain.com/auth/google/callback`

### 6. Start the Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## Key Features Implemented

### ✅ Completed Features

1. **School-Based Registration**
   - Schools can register with school name, board, and address
   - Automatic High School membership on registration
   - School owner account creation

2. **Authentication System**
   - Email/password authentication
   - Google OAuth integration (ready, needs credentials)
   - Session management

3. **Divisions of Learning Page**
   - **PRIMARY LOCKED FEATURE** - Only accessible when logged in
   - Shows all three divisions with access status
   - Lock icons for unpurchased divisions
   - Clickable access for purchased divisions

4. **Membership System**
   - High School: Free (included by default)
   - Primary: $399.99/year (paid add-on)
   - Junior Intermediate: $399.99/year (paid add-on)
   - Membership status tracking

5. **Access Control**
   - CheckMembership middleware
   - Division-level access gating
   - Automatic High School access

6. **User Profile**
   - View memberships and expiry dates
   - Gallery of Growth posts
   - Favorites management
   - School information

7. **Public Pages**
   - Home page
   - About Us
   - Gallery of Growth
   - Support page
   - Membership information

8. **Bootstrap 5 UI**
   - Responsive design
   - Modern, clean interface
   - jQuery for interactivity

### 🔄 Features Ready for Future Implementation

1. **Stripe Payment Integration**
   - Structure in place
   - Purchase method ready in MembershipController
   - Needs Stripe API integration

2. **Google Docs Integration**
   - Activity model has `google_doc_id` field
   - Content field ready for caching
   - Needs Google Docs API implementation

3. **Sub-Account System**
   - Database structure in place
   - User model relationships ready
   - Needs UI for profile selection

4. **Admin Dashboard**
   - Roles and permissions set up
   - Needs admin interface implementation

5. **Broken Link Monitoring**
   - Structure ready
   - Needs scheduled job implementation

6. **French Translation**
   - Needs translation system implementation

## Default Roles

The system includes four roles:

1. **administrator** - Full backend access
2. **developer** - Limited backend access (not implemented in Phase 1)
3. **educator** - Teacher role (assigned on registration)
4. **content_editor** - Can edit content

## Database Structure

### Core Tables

- `schools` - School information
- `users` - User accounts (linked to schools)
- `memberships` - School membership records
- `divisions` - Learning divisions (Primary, Junior Intermediate, High School)
- `activities` - Learning activities
- `user_progress` - User progress and favorites
- `gallery_posts` - Gallery of Growth posts

## Routes

### Public Routes
- `/` - Home page
- `/about` - About Us
- `/gallery` - Gallery of Growth
- `/support` - Support page
- `/login` - Login page
- `/register` - Registration page

### Authenticated Routes
- `/divisions-of-learning` - **PRIMARY LOCKED FEATURE**
- `/divisions/{slug}` - Individual division pages
- `/membership` - Membership management
- `/profile` - User profile

## Testing the Application

1. **Register a School**
   - Go to `/register`
   - Fill in school and owner information
   - Upon registration, you'll automatically get High School access

2. **View Divisions of Learning**
   - After login, click "Divisions of Learning" in navigation
   - You should see High School as accessible
   - Primary and Junior Intermediate will show lock icons

3. **Purchase Memberships**
   - Go to `/membership`
   - View available memberships
   - Purchase functionality ready (needs Stripe integration)

## Troubleshooting

### Migration Errors

If you encounter foreign key errors, ensure migrations run in order:
1. Schools table
2. Users table
3. Other tables

### Permission Errors

Run the seeder to create roles:
```bash
php artisan db:seed --class=RoleSeeder
```

### Google OAuth Not Working

1. Verify credentials in `.env`
2. Check redirect URI matches Google Console settings
3. Ensure Google+ API is enabled

## Next Steps

1. Set up Stripe for payment processing
2. Implement Google Docs API integration
3. Build admin dashboard
4. Add sub-account selection UI
5. Implement broken link monitoring
6. Add French translation system

## Support

For issues or questions, refer to the Support page in the application or contact the development team.

