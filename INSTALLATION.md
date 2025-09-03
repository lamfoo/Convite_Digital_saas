# Installation Guide

## Quick Start (Recommended)

### 1. Download and Setup

1. Download the project files to your web server directory
2. Ensure your server meets the requirements:
   - PHP 8.0+
   - MySQL 5.7+
   - Apache with mod_rewrite (or Nginx)

### 2. Install Dependencies

```bash
composer install
```

### 3. Run Installation Wizard

Navigate to: `http://your-domain.com/install.php`

The installer will guide you through:
1. System requirements check
2. Database configuration
3. Application settings
4. Database table creation
5. Completion

### 4. Access Your Platform

- **Application**: `http://your-domain.com/public/`
- **Admin Login**: admin@example.com / admin123

## Manual Installation

### 1. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Database
DB_HOST=localhost
DB_NAME=digital_invitations
DB_USER=your_username
DB_PASS=your_password

# Application
APP_URL=http://your-domain.com
APP_SECRET_KEY=your-secret-key

# Email (Optional)
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

### 2. Database Setup

Create database:
```sql
CREATE DATABASE digital_invitations CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import schema:
```bash
mysql -u username -p digital_invitations < database/schema.sql
```

### 3. File Permissions

```bash
chmod -R 755 public/
chmod -R 777 uploads/
chmod -R 777 public/assets/
```

### 4. Web Server Configuration

#### Apache
The `.htaccess` file is included and configured.

#### Nginx
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## Local Development

### Using XAMPP

1. Install XAMPP
2. Copy project to `htdocs/digital-invitations/`
3. Start Apache and MySQL
4. Run installer: `http://localhost/digital-invitations/install.php`

### Using Docker

```bash
# Create docker-compose.yml
version: '3.8'
services:
  web:
    image: php:8.1-apache
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: digital_invitations
    ports:
      - "3306:3306"

# Start services
docker-compose up -d
```

## Email Configuration

### Gmail Setup

1. Enable 2-factor authentication
2. Generate app password
3. Use these settings:
   ```env
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=your-app-password
   SMTP_ENCRYPTION=tls
   ```

### Other Providers

#### Outlook/Hotmail
```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
```

#### Custom SMTP
Configure according to your provider's documentation.

## Security Checklist

### Production Deployment

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Use strong, unique secret keys
- [ ] Enable HTTPS redirect in `.htaccess`
- [ ] Remove or rename `install.php`
- [ ] Set proper file permissions
- [ ] Configure firewall rules
- [ ] Regular security updates
- [ ] Database backups
- [ ] Monitor error logs

### File Permissions

```bash
# Application files
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Writable directories
chmod -R 777 uploads/
chmod -R 777 public/assets/qr-codes/
chmod -R 777 public/assets/thumbnails/

# Protect sensitive files
chmod 600 .env
```

## Troubleshooting

### Common Issues

**"Database connection failed"**
- Check MySQL is running
- Verify credentials in `.env`
- Ensure database exists

**"Permission denied"**
- Check file permissions
- Verify web server user ownership
- Check SELinux settings (if applicable)

**"Template not loading"**
- Check database connection
- Verify templates exist in database
- Check PHP error logs

**"Email not sending"**
- Verify SMTP settings
- Check firewall rules
- Enable less secure apps (Gmail)
- Check email logs in database

### Debug Mode

Enable debugging in `.env`:
```env
APP_ENV=development
```

Check error logs:
```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

## Performance Optimization

### Production Settings

1. **PHP Configuration**:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

2. **Database Optimization**:
   - Add indexes for frequently queried columns
   - Use connection pooling
   - Regular maintenance

3. **Caching**:
   - Enable browser caching
   - Use CDN for static assets
   - Implement Redis for session storage

### Monitoring

Set up monitoring for:
- Database performance
- Email delivery rates
- User registration/activity
- Error rates
- Response times

## Backup Strategy

### Database Backup
```bash
mysqldump -u username -p digital_invitations > backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz uploads/ public/assets/
```

### Automated Backups
Set up cron jobs for regular backups:
```bash
# Daily database backup at 2 AM
0 2 * * * mysqldump -u username -p digital_invitations > /backups/db_$(date +\%Y\%m\%d).sql

# Weekly file backup
0 3 * * 0 tar -czf /backups/files_$(date +\%Y\%m\%d).tar.gz /path/to/uploads/
```

## Support

For technical support:
1. Check this installation guide
2. Review the main README.md
3. Check server error logs
4. Verify all requirements are met
5. Test with default configuration

---

**Need help?** Make sure all requirements are met and check the troubleshooting section above.