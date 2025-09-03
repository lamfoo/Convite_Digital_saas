# Deployment Guide

This guide covers deploying the Digital Invitations SaaS platform to various hosting environments.

## 🚀 Quick Deployment Options

### Option 1: Shared Hosting (cPanel/DirectAdmin)

1. **Upload Files**:
   - Upload all files to `public_html` or web directory
   - Move `public/` contents to root web directory
   - Move other directories outside web root

2. **Database Setup**:
   - Create MySQL database via hosting panel
   - Import `database/schema.sql`
   - Update `.env` with database credentials

3. **Configuration**:
   ```bash
   cp .env.example .env
   # Edit .env with your hosting details
   ```

4. **Install Dependencies**:
   ```bash
   composer install --no-dev
   ```

### Option 2: VPS/Dedicated Server

#### Ubuntu/Debian Setup

1. **Install Prerequisites**:
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-zip php8.1-xml php8.1-curl composer
   ```

2. **Configure Apache**:
   ```bash
   sudo nano /etc/apache2/sites-available/digital-invitations.conf
   ```
   
   ```apache
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot /var/www/digital-invitations/public
       
       <Directory /var/www/digital-invitations/public>
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/digital-invitations-error.log
       CustomLog ${APACHE_LOG_DIR}/digital-invitations-access.log combined
   </VirtualHost>
   ```

3. **Enable Site**:
   ```bash
   sudo a2ensite digital-invitations
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

#### CentOS/RHEL Setup

1. **Install Prerequisites**:
   ```bash
   sudo yum install httpd mysql-server php php-mysql php-zip php-xml composer
   ```

2. **Configure SELinux** (if enabled):
   ```bash
   sudo setsebool -P httpd_can_network_connect 1
   sudo chcon -R -t httpd_exec_t /var/www/digital-invitations/
   ```

### Option 3: Docker Deployment

1. **Using Docker Compose**:
   ```bash
   docker-compose up -d
   ```

2. **Access Application**:
   - App: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

3. **Run Installation**:
   Navigate to http://localhost:8080/install.php

## 🌐 Cloud Deployment

### AWS EC2

1. **Launch EC2 Instance**:
   - Ubuntu 20.04 LTS
   - t3.micro (free tier eligible)
   - Security group: HTTP (80), HTTPS (443), SSH (22)

2. **Setup Script**:
   ```bash
   #!/bin/bash
   sudo apt update
   sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-zip composer git
   
   # Clone repository
   cd /var/www/
   sudo git clone <your-repo> digital-invitations
   cd digital-invitations
   
   # Install dependencies
   sudo composer install --no-dev
   
   # Set permissions
   sudo chown -R www-data:www-data /var/www/digital-invitations
   sudo chmod -R 755 /var/www/digital-invitations
   sudo chmod -R 777 uploads public/assets
   
   # Configure Apache
   sudo cp deployment/apache-aws.conf /etc/apache2/sites-available/digital-invitations.conf
   sudo a2ensite digital-invitations
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **Database Setup**:
   ```bash
   sudo mysql_secure_installation
   sudo mysql -u root -p < database/schema.sql
   ```

### Google Cloud Platform

1. **App Engine Deployment**:
   Create `app.yaml`:
   ```yaml
   runtime: php81
   
   env_variables:
     DB_HOST: /cloudsql/your-project:region:instance-name
     DB_NAME: digital_invitations
     DB_USER: your-user
     DB_PASS: your-password
   
   automatic_scaling:
     min_instances: 1
     max_instances: 10
   ```

2. **Deploy**:
   ```bash
   gcloud app deploy
   ```

### DigitalOcean Droplet

1. **One-Click LAMP**:
   - Create LAMP droplet
   - SSH into server
   - Follow VPS setup instructions above

2. **App Platform**:
   - Connect GitHub repository
   - Configure build settings
   - Set environment variables
   - Deploy automatically

## 🔒 Production Security

### SSL/HTTPS Setup

#### Let's Encrypt (Free)
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

#### Custom Certificate
```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/digital-invitations/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt
</VirtualHost>
```

### Security Hardening

1. **File Permissions**:
   ```bash
   # Application files
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   
   # Writable directories
   chmod -R 777 uploads/
   chmod -R 777 public/assets/
   
   # Sensitive files
   chmod 600 .env
   chmod 600 config/*.php
   ```

2. **Apache Security**:
   ```apache
   # Disable server signature
   ServerTokens Prod
   ServerSignature Off
   
   # Hide PHP version
   expose_php = Off
   
   # Disable unnecessary modules
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

3. **PHP Security**:
   ```ini
   # php.ini security settings
   expose_php = Off
   display_errors = Off
   log_errors = On
   allow_url_fopen = Off
   allow_url_include = Off
   session.cookie_httponly = 1
   session.cookie_secure = 1
   session.use_only_cookies = 1
   ```

### Firewall Configuration

#### UFW (Ubuntu)
```bash
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw allow mysql
```

#### iptables
```bash
# Allow HTTP and HTTPS
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# Allow SSH
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# Save rules
iptables-save > /etc/iptables/rules.v4
```

## 📊 Monitoring & Maintenance

### Log Management

1. **Application Logs**:
   ```bash
   # View PHP errors
   tail -f /var/log/apache2/error.log
   
   # View access logs
   tail -f /var/log/apache2/access.log
   ```

2. **Database Logs**:
   ```bash
   # MySQL error log
   tail -f /var/log/mysql/error.log
   
   # Slow query log
   tail -f /var/log/mysql/slow.log
   ```

### Health Monitoring

1. **System Monitoring**:
   ```bash
   # Check disk space
   df -h
   
   # Check memory usage
   free -h
   
   # Check CPU usage
   top
   ```

2. **Application Monitoring**:
   ```bash
   # Check Apache status
   sudo systemctl status apache2
   
   # Check MySQL status
   sudo systemctl status mysql
   
   # Check PHP-FPM (if using)
   sudo systemctl status php8.1-fpm
   ```

### Backup Strategy

1. **Database Backup**:
   ```bash
   #!/bin/bash
   # backup-db.sh
   DATE=$(date +%Y%m%d_%H%M%S)
   mysqldump -u root -p digital_invitations > /backups/db_$DATE.sql
   
   # Compress backup
   gzip /backups/db_$DATE.sql
   
   # Remove backups older than 30 days
   find /backups -name "db_*.sql.gz" -mtime +30 -delete
   ```

2. **File Backup**:
   ```bash
   #!/bin/bash
   # backup-files.sh
   DATE=$(date +%Y%m%d_%H%M%S)
   tar -czf /backups/files_$DATE.tar.gz /var/www/digital-invitations/uploads/
   
   # Remove old backups
   find /backups -name "files_*.tar.gz" -mtime +30 -delete
   ```

3. **Automated Backups**:
   ```bash
   # Add to crontab
   crontab -e
   
   # Daily database backup at 2 AM
   0 2 * * * /path/to/backup-db.sh
   
   # Weekly file backup on Sunday at 3 AM
   0 3 * * 0 /path/to/backup-files.sh
   ```

## 🔄 Updates & Maintenance

### Application Updates

1. **Backup First**:
   ```bash
   # Backup database and files
   ./backup-db.sh
   ./backup-files.sh
   ```

2. **Update Code**:
   ```bash
   # Pull latest changes
   git pull origin main
   
   # Update dependencies
   composer install --no-dev
   
   # Run any database migrations
   php database/migrate.php
   ```

3. **Test**:
   ```bash
   # Check for errors
   php -l public/index.php
   
   # Test database connection
   php -r "require 'config/database.php'; new Database();"
   ```

### Security Updates

1. **System Updates**:
   ```bash
   # Ubuntu/Debian
   sudo apt update && sudo apt upgrade
   
   # CentOS/RHEL
   sudo yum update
   ```

2. **PHP Updates**:
   ```bash
   # Check current version
   php -v
   
   # Update PHP packages
   sudo apt update php8.1*
   ```

3. **Dependency Updates**:
   ```bash
   # Update Composer packages
   composer update
   
   # Check for security vulnerabilities
   composer audit
   ```

## 📈 Performance Tuning

### Database Optimization

1. **MySQL Configuration**:
   ```ini
   # /etc/mysql/mysql.conf.d/mysqld.cnf
   [mysqld]
   innodb_buffer_pool_size = 256M
   innodb_log_file_size = 64M
   max_connections = 100
   query_cache_size = 32M
   query_cache_type = 1
   ```

2. **Add Indexes**:
   ```sql
   -- Add performance indexes
   CREATE INDEX idx_invitations_user_created ON invitations(user_id, created_at);
   CREATE INDEX idx_rsvps_invitation ON rsvps(invitation_id);
   CREATE INDEX idx_email_logs_invitation ON email_logs(invitation_id);
   ```

### PHP Optimization

1. **OPcache Configuration**:
   ```ini
   # php.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.interned_strings_buffer=8
   opcache.max_accelerated_files=4000
   opcache.revalidate_freq=60
   opcache.fast_shutdown=1
   ```

2. **Memory Settings**:
   ```ini
   memory_limit = 256M
   max_execution_time = 60
   max_input_vars = 3000
   post_max_size = 32M
   upload_max_filesize = 32M
   ```

### Apache Optimization

1. **Enable Compression**:
   ```apache
   LoadModule deflate_module modules/mod_deflate.so
   
   <Location />
       SetOutputFilter DEFLATE
       SetEnvIfNoCase Request_URI \.(?:gif|jpe?g|png)$ no-gzip dont-vary
       SetEnvIfNoCase Request_URI \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
   </Location>
   ```

2. **Enable Caching**:
   ```apache
   LoadModule expires_module modules/mod_expires.so
   
   <IfModule mod_expires.c>
       ExpiresActive on
       ExpiresByType text/css "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 month"
       ExpiresByType image/png "access plus 1 month"
   </IfModule>
   ```

## 🌍 CDN Integration

### Cloudflare Setup

1. **Add Domain**: Add your domain to Cloudflare
2. **Update DNS**: Point DNS to Cloudflare
3. **Configure Settings**:
   - Enable caching for static assets
   - Set up page rules for API endpoints
   - Enable security features

### AWS CloudFront

1. **Create Distribution**:
   - Origin: Your server's IP/domain
   - Cache behaviors for `/assets/*`
   - Custom error pages

2. **Update Configuration**:
   ```php
   // In config/config.php
   define('CDN_URL', 'https://your-cloudfront-domain.com');
   ```

## 📧 Email Service Setup

### Production Email Services

#### SendGrid
```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=your-sendgrid-api-key
```

#### Mailgun
```env
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USERNAME=your-username@your-domain.com
SMTP_PASSWORD=your-mailgun-password
```

#### Amazon SES
```env
SMTP_HOST=email-smtp.us-east-1.amazonaws.com
SMTP_PORT=587
SMTP_USERNAME=your-ses-username
SMTP_PASSWORD=your-ses-password
```

## 💳 Payment Integration

### Stripe Production Setup

1. **Get Production Keys**:
   - Login to Stripe Dashboard
   - Switch to live mode
   - Copy API keys

2. **Update Environment**:
   ```env
   STRIPE_PUBLISHABLE_KEY=pk_live_your_key
   STRIPE_SECRET_KEY=sk_live_your_key
   ```

3. **Webhook Configuration**:
   - Set webhook URL: `https://your-domain.com/webhooks/stripe`
   - Subscribe to relevant events
   - Update webhook secret in `.env`

## 🔍 Monitoring Setup

### Application Monitoring

1. **Error Tracking**:
   ```php
   // Add to config/config.php
   if (APP_ENV === 'production') {
       // Configure error reporting service
       // e.g., Sentry, Rollbar, etc.
   }
   ```

2. **Performance Monitoring**:
   ```php
   // Add performance tracking
   $start_time = microtime(true);
   
   // ... application code ...
   
   $execution_time = microtime(true) - $start_time;
   error_log("Execution time: " . $execution_time);
   ```

### Server Monitoring

1. **System Metrics**:
   ```bash
   # Install monitoring tools
   sudo apt install htop iotop nethogs
   
   # Monitor resources
   htop           # CPU and memory
   iotop          # Disk I/O
   nethogs        # Network usage
   ```

2. **Log Monitoring**:
   ```bash
   # Monitor Apache logs
   tail -f /var/log/apache2/access.log | grep -E "(4[0-9]{2}|5[0-9]{2})"
   
   # Monitor application errors
   tail -f /var/log/apache2/error.log
   ```

## 📊 Analytics Setup

### Google Analytics

1. **Add Tracking Code**:
   ```html
   <!-- Add to all pages -->
   <script async src="https://www.googletagmanager.com/gtag/js?id=GA_TRACKING_ID"></script>
   <script>
     window.dataLayer = window.dataLayer || [];
     function gtag(){dataLayer.push(arguments);}
     gtag('js', new Date());
     gtag('config', 'GA_TRACKING_ID');
   </script>
   ```

### Custom Analytics

1. **Event Tracking**:
   ```javascript
   // Track invitation views
   gtag('event', 'invitation_view', {
     'invitation_id': invitationId,
     'template_category': category
   });
   
   // Track RSVP submissions
   gtag('event', 'rsvp_submit', {
     'response': response,
     'invitation_id': invitationId
   });
   ```

## 🚨 Disaster Recovery

### Backup Strategy

1. **Automated Backups**:
   ```bash
   #!/bin/bash
   # full-backup.sh
   DATE=$(date +%Y%m%d_%H%M%S)
   BACKUP_DIR="/backups/$DATE"
   
   mkdir -p $BACKUP_DIR
   
   # Database backup
   mysqldump -u root -p digital_invitations > $BACKUP_DIR/database.sql
   
   # File backup
   tar -czf $BACKUP_DIR/files.tar.gz /var/www/digital-invitations/uploads/
   
   # Configuration backup
   cp /var/www/digital-invitations/.env $BACKUP_DIR/
   
   # Upload to cloud storage (optional)
   # aws s3 sync $BACKUP_DIR s3://your-backup-bucket/$DATE/
   ```

2. **Recovery Procedures**:
   ```bash
   #!/bin/bash
   # restore.sh
   BACKUP_DATE=$1
   
   if [ -z "$BACKUP_DATE" ]; then
       echo "Usage: ./restore.sh YYYYMMDD_HHMMSS"
       exit 1
   fi
   
   # Restore database
   mysql -u root -p digital_invitations < /backups/$BACKUP_DATE/database.sql
   
   # Restore files
   tar -xzf /backups/$BACKUP_DATE/files.tar.gz -C /
   
   # Restore configuration
   cp /backups/$BACKUP_DATE/.env /var/www/digital-invitations/
   ```

## 🔧 Troubleshooting Production Issues

### Common Problems

1. **High Memory Usage**:
   ```bash
   # Check memory usage
   ps aux --sort=-%mem | head
   
   # Increase PHP memory limit
   echo "memory_limit = 512M" >> /etc/php/8.1/apache2/php.ini
   ```

2. **Slow Database Queries**:
   ```sql
   -- Enable slow query log
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2;
   
   -- Check slow queries
   SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
   ```

3. **Email Delivery Issues**:
   ```bash
   # Check mail logs
   tail -f /var/log/mail.log
   
   # Test SMTP connection
   telnet smtp.gmail.com 587
   ```

### Performance Issues

1. **Database Optimization**:
   ```sql
   -- Analyze table performance
   ANALYZE TABLE invitations, users, templates, rsvps;
   
   -- Check for missing indexes
   SHOW INDEX FROM invitations;
   ```

2. **Cache Implementation**:
   ```php
   // Add Redis caching
   $redis = new Redis();
   $redis->connect('127.0.0.1', 6379);
   
   // Cache template data
   $cache_key = "template_{$template_id}";
   $template = $redis->get($cache_key);
   
   if (!$template) {
       $template = $templateManager->getTemplate($template_id);
       $redis->setex($cache_key, 3600, serialize($template));
   }
   ```

## 🎯 Go-Live Checklist

### Pre-Launch

- [ ] SSL certificate installed and working
- [ ] Database properly configured and secured
- [ ] Email sending tested and working
- [ ] All forms tested (registration, login, invitation creation)
- [ ] Payment integration tested (if using Stripe)
- [ ] Mobile responsiveness verified
- [ ] Performance testing completed
- [ ] Security scan performed
- [ ] Backup system configured
- [ ] Monitoring tools installed
- [ ] DNS properly configured
- [ ] Error pages (404, 500) working
- [ ] Admin account secured (change default password)
- [ ] Remove install.php or rename it

### Post-Launch

- [ ] Monitor error logs for 24-48 hours
- [ ] Check email delivery rates
- [ ] Verify payment processing (if applicable)
- [ ] Monitor server resources
- [ ] Test user registration flow
- [ ] Verify RSVP functionality
- [ ] Check mobile performance
- [ ] Monitor database performance
- [ ] Set up regular backups
- [ ] Document any custom configurations

---

**Your Digital Invitations SaaS platform is now ready for production use!**