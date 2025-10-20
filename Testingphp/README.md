# BrickMMO Timesheets Management System

A comprehensive timesheets management application for the BrickMMO project ecosystem. This system enables contributors to efficiently log work hours across various BrickMMO repositories using GitHub OAuth authentication.

## Features

### Core Functionality
- **GitHub OAuth Authentication** - Secure login using GitHub accounts
- **Time Tracking** - Log hours against specific BrickMMO repositories
- **Repository Management** - Import and manage repositories from GitHub
- **Personal Dashboard** - View logged hours and contribution history
- **Public Interface** - Showcase active projects and contributor information
- **Admin Panel** - Manage repositories and view system analytics

### User Features
- Log work hours with date, duration, and description
- View personal contribution history with filtering
- Export time data for reporting
- Responsive design for mobile and desktop

### Admin Features
- Import repositories from GitHub API
- Toggle repository visibility
- View system statistics and analytics
- Manage user accounts
- Monitor contribution patterns

## Technology Stack

- **Backend**: PHP 8.x (vanilla, no frameworks)
- **Frontend**: Tailwind CSS with responsive design
- **Database**: MySQL 8.0+
- **Authentication**: GitHub OAuth 2.0
- **API Integration**: GitHub REST API v3

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- GitHub OAuth App credentials

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd brickmmo-timesheets
   ```

2. **Run the setup script**
   - Navigate to `setup.php` in your browser
   - Follow the guided setup process
   - Configure database connection
   - Set up GitHub OAuth credentials

3. **Manual Setup (Alternative)**

   **Database Setup:**
   ```sql
   -- Create database
   CREATE DATABASE brickmmo_timesheets;
   
   -- Import schema
   mysql -u root -p brickmmo_timesheets < database/schema.sql
   ```

   **Configuration:**
   - Edit `config/config.php` with your database credentials
   - Update GitHub OAuth settings
   - Set `DEVELOPMENT = true` for development mode

4. **GitHub OAuth Setup**
   - Go to [GitHub OAuth Apps](https://github.com/settings/applications/new)
   - Create a new OAuth application
   - Set callback URL to: `your-domain.com/auth/callback.php`
   - Copy Client ID and Secret to config

## Usage

### For Users
1. **Login**: Click "Login" and authenticate with GitHub
2. **Log Hours**: Use the dashboard to log work hours
3. **View History**: Check your contribution history and statistics
4. **Browse Repositories**: Explore public repository information

### For Administrators
1. **Access Admin Panel**: Login as admin user
2. **Import Repositories**: Fetch all BrickMMO repositories from GitHub
3. **Manage Repositories**: Enable/disable repositories for time tracking
4. **View Analytics**: Monitor system usage and contributor activity

## Project Structure

```
brickmmo-timesheets/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── import-repos.php   # Repository import
│   └── toggle-repository.php
├── auth/                  # Authentication files
│   ├── login.php         # GitHub OAuth login
│   ├── callback.php      # OAuth callback handler
│   └── logout.php        # Logout functionality
├── config/               # Configuration files
│   ├── config.php       # Main configuration
│   └── database.php     # Database connection
├── database/            # Database files
│   └── schema.sql       # Database schema
├── index.php           # Public home page
├── dashboard.php       # User dashboard
├── repository.php      # Repository details
├── personal-history.php # User history
├── setup.php          # Setup script
└── README.md          # This file
```

## Configuration

### Environment Variables
Key configuration options in `config/config.php`:

```php
// GitHub OAuth
define('GITHUB_CLIENT_ID', 'your_client_id');
define('GITHUB_CLIENT_SECRET', 'your_client_secret');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'brickmmo_timesheets');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('ITEMS_PER_PAGE', 8);
define('MAX_HOURS_PER_DAY', 16.0);
define('MIN_HOURS_PER_ENTRY', 0.25);
```

### Database Schema
The system uses three main tables:
- `users` - GitHub user information
- `applications` - Repository data
- `hours` - Time tracking entries

## API Integration

### GitHub API Usage
- **Rate Limits**: 5,000 requests/hour (authenticated)
- **Caching**: Repository data cached locally
- **Endpoints Used**:
  - `/orgs/BrickMMO/repos` - Organization repositories
  - `/repos/{owner}/{repo}` - Repository details
  - `/repos/{owner}/{repo}/languages` - Language statistics
  - `/repos/{owner}/{repo}/contributors` - Contributor data

## Security Features

- **CSRF Protection**: All forms protected with tokens
- **Input Validation**: Server-side validation for all inputs
- **SQL Injection Prevention**: Prepared statements only
- **Session Management**: Secure session handling
- **OAuth Security**: GitHub OAuth 2.0 implementation

## Development

### Local Development
1. Set `DEVELOPMENT = true` in config
2. Use XAMPP/WAMP for local server
3. Access via `http://localhost/brickmmo-timesheets`

### Code Standards
- **Naming**: snake_case for database, camelCase for PHP
- **Structure**: Separation of concerns
- **Documentation**: PHPDoc comments
- **Security**: Input sanitization and validation

## Deployment

### Production Setup
1. **Server Requirements**:
   - PHP 8.0+ with MySQL extension
   - MySQL 8.0+
   - HTTPS enabled for OAuth

2. **Security Checklist**:
   - Set `DEVELOPMENT = false`
   - Configure proper file permissions
   - Set up SSL certificates
   - Update OAuth callback URLs

3. **Database Optimization**:
   - Enable query caching
   - Set up regular backups
   - Monitor performance

## Troubleshooting

### Common Issues

**OAuth Login Fails:**
- Check GitHub OAuth app settings
- Verify callback URL matches exactly
- Ensure HTTPS in production

**Database Connection Error:**
- Verify database credentials
- Check MySQL service status
- Ensure database exists

**Repository Import Issues:**
- Check GitHub API rate limits
- Verify organization access
- Review error logs

### Error Logs
Check PHP error logs for detailed error information:
```bash
tail -f /var/log/apache2/error.log
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is part of the BrickMMO ecosystem. See LICENSE file for details.

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation wiki

## Changelog

### Version 1.0.0
- Initial release
- GitHub OAuth authentication
- Time tracking functionality
- Repository management
- Public interface
- Admin dashboard
- Personal history and analytics
