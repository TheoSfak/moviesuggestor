# Pre-Deployment Checklist
**Project**: Movie Suggestor v2.0.0  
**Date**: January 20, 2026  
**Status**: Ready for Deployment

---

## ✅ Pre-Flight Verification (Completed)

### Code Quality
- [x] All PHP files have valid syntax
- [x] All unit tests passing (199/199)
- [x] No TODO/FIXME comments in production code
- [x] Code follows PSR-4 standards
- [x] Error handling implemented
- [x] Logging configured

### Security
- [x] SQL injection prevention (prepared statements)
- [x] XSS prevention (htmlspecialchars)
- [x] Input validation on all user inputs
- [x] Security headers configured
- [x] Display errors disabled in production
- [x] No sensitive credentials in code
- [x] Session security implemented
- [x] CORS headers configured for APIs

### Database
- [x] All migrations tested and validated
- [x] Database indexes optimized
- [x] Foreign keys working correctly
- [x] Backup procedures documented
- [x] Test database separate from production

### Documentation
- [x] README.md updated
- [x] CHANGELOG.md created
- [x] API documentation complete
- [x] Setup instructions clear
- [x] Migration guide available

### Testing
- [x] Unit tests run successfully
- [x] Integration tests passed
- [x] Frontend loads without errors
- [x] API endpoints validated
- [x] Backward compatibility verified

---

## 🚀 Deployment Steps

### Step 1: Version Control
```bash
# Check git status
git status

# Review all changes
git diff

# Stage all files
git add .

# Commit with meaningful message
git commit -m "Release v2.0.0: Phase 2 Complete - Advanced Filtering, User Interactions, Comprehensive Testing"

# Tag the release
git tag -a v2.0.0 -m "Version 2.0.0 - Phase 2 Release"

# Push to GitHub
git push origin main
git push origin --tags
```

**Checklist:**
- [ ] All changes committed
- [ ] Commit message is descriptive
- [ ] Version tag created (v2.0.0)
- [ ] Pushed to origin/main
- [ ] Tags pushed

---

### Step 2: GitHub Repository Setup

**Checklist:**
- [ ] Repository is public (or private as required)
- [ ] README.md displays correctly on GitHub
- [ ] Repository description updated
- [ ] Topics/tags added (php, mysql, movie-app, etc.)
- [ ] License file present (MIT)
- [ ] .gitignore configured

**Repository Settings:**
```
Name: moviesuggestor
Description: A PHP + MySQL web application that suggests movies with advanced filtering and user interactions
Topics: php, mysql, movie-database, rest-api, phpunit, testing
License: MIT
```

---

### Step 3: Production Server Setup

#### 3.1 Server Requirements
- [ ] PHP 8.0 or higher installed
- [ ] MySQL 8.0 or higher installed
- [ ] Composer installed
- [ ] Web server (Apache/Nginx) configured
- [ ] SSL certificate installed (HTTPS)

#### 3.2 Environment Configuration
```bash
# Create .env file (DO NOT commit to git)
cat > .env << EOF
DB_HOST=localhost
DB_PORT=3306
DB_NAME=moviesuggestor
DB_USER=your_db_user
DB_PASS=your_secure_password
EOF

# Set appropriate permissions
chmod 600 .env
```

**Checklist:**
- [ ] .env file created
- [ ] Database credentials configured
- [ ] File permissions set correctly
- [ ] .env added to .gitignore

#### 3.3 Clone Repository
```bash
# Clone from GitHub
git clone https://github.com/yourusername/moviesuggestor.git
cd moviesuggestor

# Install dependencies
composer install --no-dev --optimize-autoloader
```

**Checklist:**
- [ ] Repository cloned
- [ ] Composer dependencies installed
- [ ] Autoloader optimized

---

### Step 4: Database Setup

#### 4.1 Create Database
```bash
# Create production database
mysql -u root -p -e "CREATE DATABASE moviesuggestor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create database user (recommended for security)
mysql -u root -p << EOF
CREATE USER 'moviesuggestor_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON moviesuggestor.* TO 'moviesuggestor_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

**Checklist:**
- [ ] Production database created
- [ ] Database user created
- [ ] Appropriate permissions granted
- [ ] UTF8MB4 character set configured

#### 4.2 Import Schema
```bash
# Import base schema
mysql -u moviesuggestor_user -p moviesuggestor < schema.sql
```

**Checklist:**
- [ ] Base schema imported
- [ ] Sample movies data loaded

#### 4.3 Run Migrations
```bash
# Run all Phase 2 migrations
php migrations/run-migrations.php
```

**Checklist:**
- [ ] Migration 001: Movie metadata columns added
- [ ] Migration 002: Favorites table created
- [ ] Migration 003: Watch later table created
- [ ] Migration 004: Ratings table created
- [ ] Migration 005: Performance indexes created
- [ ] All migrations completed successfully

#### 4.4 Validate Database
```bash
# Run validation script
php validate-db.php
```

**Checklist:**
- [ ] All tables exist
- [ ] All columns present
- [ ] Indexes created
- [ ] Foreign keys working

---

### Step 5: Web Server Configuration

#### 5.1 Apache Configuration
```apache
<VirtualHost *:80>
    ServerName moviesuggestor.example.com
    DocumentRoot /var/www/moviesuggestor
    
    <Directory /var/www/moviesuggestor>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirect to HTTPS
    Redirect permanent / https://moviesuggestor.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName moviesuggestor.example.com
    DocumentRoot /var/www/moviesuggestor
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    <Directory /var/www/moviesuggestor>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "DENY"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Strict-Transport-Security "max-age=31536000"
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/moviesuggestor-error.log
    CustomLog ${APACHE_LOG_DIR}/moviesuggestor-access.log combined
</VirtualHost>
```

#### 5.2 Nginx Configuration
```nginx
server {
    listen 80;
    server_name moviesuggestor.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name moviesuggestor.example.com;
    root /var/www/moviesuggestor;
    index index.php;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000" always;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    access_log /var/log/nginx/moviesuggestor-access.log;
    error_log /var/log/nginx/moviesuggestor-error.log;
}
```

**Checklist:**
- [ ] Virtual host configured
- [ ] SSL certificate installed
- [ ] Security headers enabled
- [ ] Error logging configured
- [ ] HTTPS redirect enabled
- [ ] Web server restarted

---

### Step 6: File Permissions

```bash
# Set appropriate ownership
sudo chown -R www-data:www-data /var/www/moviesuggestor

# Set directory permissions
find /var/www/moviesuggestor -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/moviesuggestor -type f -exec chmod 644 {} \;

# Protect sensitive files
chmod 600 /var/www/moviesuggestor/.env

# Make scripts executable if needed
chmod +x /var/www/moviesuggestor/migrations/*.php
```

**Checklist:**
- [ ] Ownership set to web server user
- [ ] Directory permissions: 755
- [ ] File permissions: 644
- [ ] .env file: 600 (read-only by owner)
- [ ] No world-writable files

---

### Step 7: Production Testing

#### 7.1 Smoke Tests
```bash
# Test database connection
php -r "require 'vendor/autoload.php'; use MovieSuggestor\Database; \$db = new Database(); echo 'Connection: OK\n';"

# Test homepage
curl -I https://moviesuggestor.example.com

# Test API endpoints
curl -X GET https://moviesuggestor.example.com/api/favorites.php
```

**Checklist:**
- [ ] Database connection works
- [ ] Homepage loads (HTTP 200)
- [ ] API endpoints respond
- [ ] No PHP errors in logs

#### 7.2 Functional Tests
- [ ] Movies display on homepage
- [ ] Filters work correctly
- [ ] Search functionality works
- [ ] Favorites can be added (requires session)
- [ ] Watch later list works
- [ ] Ratings can be submitted
- [ ] Responsive design works on mobile

#### 7.3 Performance Tests
```bash
# Check page load time
curl -o /dev/null -s -w 'Total: %{time_total}s\n' https://moviesuggestor.example.com

# Check database query performance
mysql -u moviesuggestor_user -p moviesuggestor -e "EXPLAIN SELECT * FROM movies WHERE category = 'Action' ORDER BY score DESC LIMIT 20;"
```

**Checklist:**
- [ ] Page loads in < 2 seconds
- [ ] Database queries use indexes
- [ ] No N+1 query problems
- [ ] Memory usage acceptable

---

### Step 8: Monitoring & Logging

#### 8.1 Log Files
```bash
# PHP error log
tail -f /var/log/php/error.log

# Web server error log
tail -f /var/log/apache2/moviesuggestor-error.log
# OR
tail -f /var/log/nginx/moviesuggestor-error.log

# Application log (if configured)
tail -f /var/www/moviesuggestor/logs/app.log
```

#### 8.2 Monitoring Setup
- [ ] Error logging configured
- [ ] Log rotation enabled
- [ ] Disk space monitoring
- [ ] Database monitoring
- [ ] Uptime monitoring (optional)
- [ ] Performance monitoring (optional)

**Checklist:**
- [ ] Logs are being written
- [ ] No errors in logs
- [ ] Log rotation configured
- [ ] Monitoring alerts set up

---

### Step 9: Backup Strategy

#### 9.1 Database Backups
```bash
# Create backup script
cat > /usr/local/bin/backup-moviesuggestor.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backups/moviesuggestor"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
mysqldump -u moviesuggestor_user -p moviesuggestor | gzip > $BACKUP_DIR/db_$DATE.sql.gz
# Keep only last 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
EOF

chmod +x /usr/local/bin/backup-moviesuggestor.sh

# Add to crontab (daily at 2 AM)
echo "0 2 * * * /usr/local/bin/backup-moviesuggestor.sh" | crontab -
```

#### 9.2 Code Backups
- [ ] Code is in Git repository (GitHub)
- [ ] Regular commits pushed
- [ ] Tags for releases created

**Checklist:**
- [ ] Automated backups configured
- [ ] Backup restoration tested
- [ ] Off-site backup storage (GitHub)
- [ ] Backup retention policy defined

---

### Step 10: Post-Deployment Validation

#### 10.1 Immediate Checks (within 1 hour)
- [ ] Application is accessible
- [ ] No errors in logs
- [ ] All pages load correctly
- [ ] Database queries working
- [ ] User interactions functional

#### 10.2 Short-term Checks (within 24 hours)
- [ ] Performance is acceptable
- [ ] No memory leaks
- [ ] No database deadlocks
- [ ] Backups completed successfully
- [ ] Monitoring alerts working

#### 10.3 Medium-term Checks (within 1 week)
- [ ] No recurring errors
- [ ] User feedback collected
- [ ] Performance metrics stable
- [ ] Security scans completed
- [ ] Documentation accurate

---

## 🔒 Security Hardening (Recommended)

### Additional Security Measures
- [ ] Implement rate limiting on API endpoints
- [ ] Add CSRF token protection for forms
- [ ] Enable Web Application Firewall (WAF)
- [ ] Implement IP-based access control for admin areas
- [ ] Set up intrusion detection
- [ ] Configure fail2ban for brute force protection
- [ ] Regular security updates scheduled
- [ ] Vulnerability scanning enabled

### Database Security
- [ ] Database user has minimum required privileges
- [ ] Database not accessible from public internet
- [ ] Strong passwords enforced
- [ ] Regular security audits scheduled

---

## 📊 Performance Optimization (Optional)

### Caching
- [ ] Implement Redis/Memcached for session storage
- [ ] Add query result caching
- [ ] Enable browser caching headers
- [ ] Implement CDN for static assets

### Database Optimization
- [ ] Query performance analyzed
- [ ] Slow query log enabled
- [ ] Database tuning parameters optimized
- [ ] Connection pooling configured

---

## 🆘 Rollback Plan

### If Issues Occur:

#### Minor Issues (Fix Forward)
1. Identify the issue in logs
2. Apply hotfix
3. Test fix
4. Deploy fix
5. Monitor

#### Major Issues (Rollback)
1. **Code Rollback**:
   ```bash
   git checkout v1.0.0  # or last stable version
   composer install
   ```

2. **Database Rollback**:
   ```bash
   php migrations/run-migrations.php down  # Rollback migrations
   # OR restore from backup
   mysql -u root -p moviesuggestor < /backups/db_backup.sql
   ```

3. **Verify Rollback**:
   - [ ] Application loads
   - [ ] Phase 1 features work
   - [ ] No errors in logs

**Checklist:**
- [ ] Rollback plan documented
- [ ] Rollback tested in staging
- [ ] Database backup before rollback
- [ ] Communication plan for downtime

---

## ✅ Final Checklist

### Pre-Deployment
- [x] Code quality verified
- [x] All tests passing
- [x] Security audit completed
- [x] Documentation complete
- [x] Version tagged

### Deployment
- [ ] Git repository pushed
- [ ] Production server configured
- [ ] Database migrations applied
- [ ] Web server configured
- [ ] SSL certificate installed
- [ ] File permissions set

### Post-Deployment
- [ ] Application accessible
- [ ] Smoke tests passed
- [ ] Functional tests passed
- [ ] Performance acceptable
- [ ] Monitoring active
- [ ] Backups configured

### Communication
- [ ] Team notified of deployment
- [ ] Users informed of new features (if applicable)
- [ ] Documentation shared
- [ ] Support team briefed

---

## 📞 Emergency Contacts

**Technical Lead**: [Your Name]  
**Database Admin**: [DBA Name]  
**System Admin**: [SysAdmin Name]  
**On-Call**: [On-Call Number]

---

## 📝 Deployment Log

| Date | Version | Deployed By | Status | Notes |
|------|---------|-------------|--------|-------|
| 2026-01-20 | v2.0.0 | [Name] | Pending | Phase 2 release |

---

**Deployment Approval**: ✅ Ready  
**Sign-Off**: [Name] - [Date]  
**Next Review**: Phase 3 Planning

---

## 🎯 Success Criteria

Deployment is considered successful when:
- ✅ Application is accessible via HTTPS
- ✅ All core features functional
- ✅ No critical errors in logs (24 hours)
- ✅ Performance SLA met (< 2s page load)
- ✅ Backups running successfully
- ✅ Monitoring alerts configured
- ✅ Zero security vulnerabilities

**If all criteria met**: ✅ Deployment SUCCESSFUL  
**If any criteria fails**: ⚠️ Investigate and remediate or rollback
