# Production Deployment Checklist

Use this checklist to ensure your application is properly configured for production on AWS.

## Pre-Deployment

### Environment Configuration
- [ ] `.env` file created from `.env.example`
- [ ] `APP_ENV=production` set
- [ ] `APP_DEBUG=false` set
- [ ] `APP_KEY` generated (run `php artisan key:generate`)
- [ ] `APP_URL` set to your production domain (https://your-domain.com)

### Database Configuration
- [ ] AWS RDS MySQL instance created
- [ ] RDS endpoint noted
- [ ] `DB_CONNECTION=mysql` set
- [ ] `DB_HOST` set to RDS endpoint
- [ ] `DB_PORT` set (usually 3306)
- [ ] `DB_DATABASE` set
- [ ] `DB_USERNAME` set
- [ ] `DB_PASSWORD` set (strong password)
- [ ] RDS security group allows EC2 instance access
- [ ] Database connection tested (`php artisan db:show`)

### Server Configuration
- [ ] EC2 instance launched
- [ ] PHP 8.2+ installed with required extensions
- [ ] Composer installed
- [ ] Node.js and NPM installed
- [ ] Nginx installed and configured
- [ ] Application files deployed to `/var/www/capstone`
- [ ] Proper file permissions set (www-data:www-data)

## Deployment Steps

### Application Setup
- [ ] `.env` file configured with production values
- [ ] `deploy-aws.sh` script executed successfully
- [ ] Composer dependencies installed (`composer install --no-dev`)
- [ ] NPM dependencies installed and built (`npm ci --production && npm run build`)
- [ ] Database migrations run (`php artisan migrate --force`)
- [ ] Configuration cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Storage linked (`php artisan storage:link`)

### Web Server
- [ ] Nginx configuration file created
- [ ] Nginx configuration tested (`sudo nginx -t`)
- [ ] Nginx service running (`sudo systemctl status nginx`)
- [ ] Site accessible via HTTP
- [ ] SSL certificate installed (Let's Encrypt or ACM)
- [ ] Site accessible via HTTPS
- [ ] HTTP redirects to HTTPS

### Queue Worker
- [ ] Supervisor installed
- [ ] Supervisor configuration file created (`/etc/supervisor/conf.d/capstone-worker.conf`)
- [ ] Queue worker started (`sudo supervisorctl start capstone-worker:*`)
- [ ] Queue worker status verified (`sudo supervisorctl status`)
- [ ] Queue worker logs accessible (`tail -f storage/logs/worker.log`)

### Scheduler
- [ ] Cron job configured for Laravel scheduler
- [ ] Cron job runs every minute
- [ ] Scheduler tasks executing correctly

## Security

### Application Security
- [ ] `APP_DEBUG=false` in production
- [ ] `SESSION_ENCRYPT=true` set
- [ ] Strong `APP_KEY` generated
- [ ] `.env` file not accessible via web (check Nginx config)
- [ ] `storage` and `bootstrap/cache` directories have correct permissions (775)
- [ ] Sensitive files excluded from version control

### Server Security
- [ ] Firewall configured (UFW or Security Groups)
- [ ] Only necessary ports open (22, 80, 443)
- [ ] SSH key-based authentication enabled
- [ ] Root login disabled
- [ ] Regular security updates scheduled
- [ ] Fail2ban installed (optional but recommended)

### Database Security
- [ ] RDS security group restricts access to EC2 only
- [ ] Database credentials are strong
- [ ] Database backups enabled on RDS
- [ ] SSL connection to RDS (if required)

## Performance

### Optimization
- [ ] Configuration cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Composer autoloader optimized
- [ ] Assets built and minified
- [ ] OPcache enabled (recommended)

### Monitoring
- [ ] Application logs accessible (`storage/logs/laravel.log`)
- [ ] Nginx access logs monitored
- [ ] Nginx error logs monitored
- [ ] Queue worker logs monitored
- [ ] Server resources monitored (CPU, Memory, Disk)

## Backup & Recovery

### Database Backups
- [ ] RDS automated backups enabled
- [ ] Backup retention period set (7+ days recommended)
- [ ] Manual backup procedure documented
- [ ] Backup restoration tested

### Application Backups
- [ ] Application files backup procedure in place
- [ ] `.env` file backed up securely
- [ ] Backup restoration procedure documented

## Testing

### Functionality
- [ ] Application loads correctly
- [ ] Database queries work
- [ ] File uploads work (if applicable)
- [ ] Email sending works (if applicable)
- [ ] Queue jobs process correctly
- [ ] Scheduled tasks run correctly
- [ ] User authentication works
- [ ] All routes accessible

### Performance
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] No memory leaks
- [ ] Server resources adequate

## Post-Deployment

### Documentation
- [ ] Deployment procedure documented
- [ ] Environment variables documented
- [ ] Backup/restore procedures documented
- [ ] Troubleshooting guide available

### Maintenance
- [ ] Update procedure documented
- [ ] Monitoring alerts configured (optional)
- [ ] Regular maintenance schedule established

## Quick Verification Commands

```bash
# Check application status
cd /var/www/capstone
php artisan about

# Test database connection
php artisan db:show

# Check queue worker
sudo supervisorctl status

# View recent logs
tail -n 50 storage/logs/laravel.log

# Check Nginx status
sudo systemctl status nginx

# Test Nginx configuration
sudo nginx -t

# Check disk space
df -h

# Check memory usage
free -h
```

## Common Issues & Solutions

### Issue: 500 Internal Server Error
- [ ] Check `storage/logs/laravel.log`
- [ ] Verify `.env` file exists and is configured
- [ ] Check file permissions
- [ ] Clear caches: `php artisan config:clear`

### Issue: Database Connection Failed
- [ ] Verify RDS endpoint in `.env`
- [ ] Check RDS security group
- [ ] Test connection: `php artisan db:show`

### Issue: Assets Not Loading
- [ ] Run `npm run build`
- [ ] Check `public/build` directory exists
- [ ] Verify Nginx serves static files

### Issue: Queue Not Processing
- [ ] Check supervisor: `sudo supervisorctl status`
- [ ] Restart worker: `sudo supervisorctl restart capstone-worker:*`
- [ ] Check logs: `tail -f storage/logs/worker.log`

---

**Last Updated**: January 2025
