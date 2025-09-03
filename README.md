# Digital Invitations SaaS Platform

A complete, full-stack SaaS platform for creating and managing digital invitations. Built with PHP, MySQL, HTML, CSS, Tailwind CSS, and Bootstrap 5.

## 🚀 Features

### Core Features
- **User Authentication**: Registration, login, logout, password reset with JWT support
- **Role-based Access**: Admin and User roles with different permissions
- **Invitation Management**: Create, customize, and track digital invitations
- **Template System**: 5+ pre-built templates (wedding, birthday, corporate, baby shower, graduation)
- **RSVP Tracking**: Collect and manage guest responses
- **Email Integration**: Send invitations via email using PHPMailer
- **Analytics Dashboard**: Track views, RSVPs, and engagement

### SaaS Features
- **Subscription Tiers**: Free, Basic, and Premium plans with different limits
- **Multi-tenant Architecture**: Isolated user data
- **Payment Integration**: Stripe integration ready (demo mode included)
- **Usage Limits**: Monthly invitation limits based on subscription
- **Premium Templates**: Access control for premium content

### Technical Features
- **Responsive Design**: Mobile-first responsive UI
- **Modern UI**: Tailwind CSS + Bootstrap 5 components
- **RESTful API**: Clean API endpoints for all operations
- **Security**: CSRF protection, input sanitization, prepared statements
- **File Management**: Secure file uploads and QR code generation
- **Database**: MySQL with proper relationships and indexes

## 📋 Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache with mod_rewrite (or Nginx)
- **Composer**: For dependency management
- **Extensions**: PDO, PDO_MySQL, OpenSSL, cURL

## 🛠️ Installation

### Option 1: Automated Installation (Recommended)

1. **Download and extract** the project files to your web server directory

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Run the installer**:
   - Navigate to `http://your-domain.com/install.php`
   - Follow the 5-step installation wizard
   - Configure database connection
   - Set application settings
   - Complete setup

### Option 2: Manual Installation

1. **Clone or download** the repository:
   ```bash
   git clone <repository-url>
   cd digital-invitations-saas
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Configure environment**:
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your database and email settings.

4. **Create database**:
   ```sql
   CREATE DATABASE digital_invitations CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Import database schema**:
   ```bash
   mysql -u your_username -p digital_invitations < database/schema.sql
   ```

6. **Set permissions**:
   ```bash
   chmod 755 public/
   chmod -R 777 uploads/
   ```

## 📁 Project Structure

```
digital-invitations-saas/
├── config/
│   ├── config.php          # Application configuration
│   └── database.php        # Database connection
├── src/
│   ├── Auth.php           # Authentication class
│   ├── User.php           # User management
│   ├── Template.php       # Template management
│   ├── Invitation.php     # Invitation management
│   └── EmailService.php   # Email sending service
├── api/
│   ├── auth.php           # Authentication endpoints
│   ├── invitations.php    # Invitation API
│   ├── templates.php      # Template API
│   └── email.php          # Email API
├── public/
│   ├── index.php          # Homepage
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   ├── dashboard.php      # User dashboard
│   ├── templates.php      # Template gallery
│   ├── create-invitation.php # Invitation creation
│   ├── invitation.php     # Public invitation view
│   ├── subscription.php   # Subscription management
│   ├── admin/             # Admin panel
│   └── assets/            # CSS, JS, images
├── database/
│   └── schema.sql         # Database schema
├── uploads/               # File uploads directory
├── vendor/                # Composer dependencies
├── .env.example           # Environment template
├── .htaccess             # Apache configuration
├── composer.json         # PHP dependencies
├── install.php           # Installation wizard
└── README.md             # This file
```

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```env
# Database
DB_HOST=localhost
DB_NAME=digital_invitations
DB_USER=root
DB_PASS=your_password

# Application
APP_URL=http://your-domain.com
APP_SECRET_KEY=your-secret-key

# Email (PHPMailer)
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password

# Stripe (Optional)
STRIPE_PUBLISHABLE_KEY=pk_test_your_key
STRIPE_SECRET_KEY=sk_test_your_key
```

### Web Server Configuration

#### Apache (.htaccess included)
The included `.htaccess` file handles:
- URL rewriting for clean URLs
- Security headers
- Static file caching
- HTTPS redirect (commented out)

#### Nginx
For Nginx, add this configuration:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/digital-invitations-saas/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location /invitation/ {
        try_files $uri $uri/ /invitation.php?$query_string;
    }
}
```

## 👤 Default Admin Account

After installation, use these credentials to access the admin panel:

- **Email**: admin@example.com
- **Password**: admin123

**⚠️ Important**: Change these credentials immediately after first login!

## 🎨 Templates

The platform includes 5 pre-built templates:

1. **Elegant Wedding** - Classic wedding invitation
2. **Birthday Celebration** - Fun birthday party invite
3. **Corporate Event** - Professional business event
4. **Baby Shower** - Sweet baby shower invitation
5. **Graduation Party** - Celebration graduation invite

### Adding Custom Templates

Templates are stored in the database with HTML and CSS content. Admins can:
- Create new templates via the admin panel
- Use placeholder variables like `{{bride_name}}`, `{{event_date}}`
- Set templates as premium or free
- Organize templates by category

## 💳 Subscription Plans

### Free Plan
- 5 invitations per month
- Basic templates only
- Email sharing
- RSVP tracking

### Basic Plan ($9/month)
- 50 invitations per month
- All templates
- Analytics dashboard
- Priority support

### Premium Plan ($29/month)
- Unlimited invitations
- Custom domain support
- Advanced analytics
- White-label options

## 🔒 Security Features

- **CSRF Protection**: All forms protected against CSRF attacks
- **Input Sanitization**: All user input sanitized and validated
- **Password Hashing**: bcrypt password hashing
- **SQL Injection Prevention**: Prepared statements for all queries
- **Session Management**: Secure session handling
- **File Upload Security**: Validated file uploads with size limits

## 📧 Email Integration

The platform uses PHPMailer for sending invitations:

- **SMTP Configuration**: Supports Gmail, Outlook, custom SMTP
- **Email Templates**: Beautiful HTML email templates
- **Delivery Tracking**: Log all email sending attempts
- **Bulk Sending**: Send to multiple recipients at once

## 📊 Analytics & Reporting

Track important metrics:
- Invitation views and engagement
- RSVP response rates
- User activity and growth
- Template popularity
- Revenue and subscriptions (with Stripe)

## 🔌 API Endpoints

### Authentication
- `POST /api/auth.php?action=register` - User registration
- `POST /api/auth.php?action=login` - User login
- `POST /api/auth.php?action=logout` - User logout
- `GET /api/auth.php?action=me` - Get current user

### Invitations
- `GET /api/invitations.php?action=list` - Get user invitations
- `POST /api/invitations.php?action=create` - Create invitation
- `PUT /api/invitations.php?id={id}` - Update invitation
- `DELETE /api/invitations.php?id={id}` - Delete invitation
- `GET /api/invitations.php?action=view&id={code}` - View invitation
- `POST /api/invitations.php?action=rsvp` - Submit RSVP

### Templates
- `GET /api/templates.php?action=list` - Get templates
- `GET /api/templates.php?action=view&id={id}` - Get template
- `POST /api/templates.php?action=create` - Create template (admin)
- `PUT /api/templates.php?id={id}` - Update template (admin)

### Email
- `POST /api/email.php?action=send&id={id}` - Send invitation
- `GET /api/email.php?action=logs&id={id}` - Get email logs

## 🚀 Deployment

### Production Deployment

1. **Server Setup**:
   - PHP 8.0+ with required extensions
   - MySQL 5.7+
   - SSL certificate (recommended)
   - Proper file permissions

2. **Environment Configuration**:
   - Set `APP_ENV=production` in `.env`
   - Use strong secret keys
   - Configure proper database credentials
   - Set up SMTP for email delivery

3. **Security Checklist**:
   - Remove or rename `install.php`
   - Set proper file permissions
   - Enable HTTPS redirect in `.htaccess`
   - Configure firewall rules
   - Regular security updates

### Docker Deployment (Optional)

Create a `Dockerfile`:
```dockerfile
FROM php:8.1-apache

# Install extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Copy application
COPY . /var/www/html/
COPY .env.example /var/www/html/.env

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

## 🧪 Testing

### Manual Testing Checklist

1. **User Registration/Login**:
   - [ ] Register new user
   - [ ] Login with credentials
   - [ ] Password reset flow
   - [ ] Session management

2. **Invitation Creation**:
   - [ ] Select template
   - [ ] Customize content
   - [ ] Preview functionality
   - [ ] Save invitation

3. **RSVP Process**:
   - [ ] Access invitation via link
   - [ ] Submit RSVP response
   - [ ] View RSVP in dashboard

4. **Email Sending**:
   - [ ] Send test invitation
   - [ ] Verify email delivery
   - [ ] Check email logs

5. **Subscription Management**:
   - [ ] Test plan limits
   - [ ] Upgrade/downgrade
   - [ ] Payment simulation

## 🔧 Troubleshooting

### Common Issues

**Database Connection Failed**:
- Check MySQL service is running
- Verify credentials in `.env`
- Ensure database exists

**Email Not Sending**:
- Check SMTP configuration
- Verify email credentials
- Check server firewall rules
- Enable "Less secure app access" for Gmail

**File Upload Issues**:
- Check directory permissions
- Verify `upload_max_filesize` in php.ini
- Ensure uploads directory exists

**Template Not Loading**:
- Check database connection
- Verify template exists in database
- Check for PHP errors in logs

### Debug Mode

Enable debug mode by setting in `.env`:
```env
APP_ENV=development
```

This enables:
- Error display
- Detailed logging
- Debug information

## 📝 Customization

### Adding New Templates

1. **Create Template**:
   - Design HTML structure with placeholders
   - Style with CSS
   - Add to database via admin panel

2. **Placeholder Variables**:
   - Use `{{variable_name}}` format
   - Common variables: `{{event_date}}`, `{{event_location}}`, `{{custom_message}}`
   - Template-specific: `{{bride_name}}`, `{{company_name}}`, etc.

### Extending Functionality

The codebase is designed for easy extension:
- Add new API endpoints in `/api/`
- Create new page templates in `/public/`
- Extend classes in `/src/`
- Add new database tables as needed

## 📄 License

This project is open source and available under the MIT License.

## 🤝 Support

For support and questions:
- Check the troubleshooting section
- Review the code comments
- Check server error logs
- Ensure all requirements are met

## 🔄 Updates

To update the platform:
1. Backup your database and files
2. Download new version
3. Run `composer update`
4. Check for database migrations
5. Test thoroughly before going live

---

**Built with ❤️ for the digital invitation community**