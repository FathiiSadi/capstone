# AWS Production Deployment Guide

This guide will help you deploy your Laravel application to AWS without Docker.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [AWS RDS Database Setup](#aws-rds-database-setup)
3. [EC2 Instance Setup](#ec2-instance-setup)
4. [Server Configuration](#server-configuration)
5. [Application Deployment](#application-deployment)
6. [Queue Worker Setup](#queue-worker-setup)
7. [Scheduler Setup](#scheduler-setup)
8. [SSL Certificate Setup](#ssl-certificate-setup)
9. [Monitoring and Maintenance](#monitoring-and-maintenance)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- AWS Account
- AWS RDS MySQL instance (or create one following the steps below)
- EC2 instance with Ubuntu 22.04 LTS or Amazon Linux 2023
- Domain name (optional but recommended)
- SSH access to your EC2 instance

---

## AWS RDS Database Setup

### Step 1: Create RDS MySQL Instance

1. Log in to AWS Console
2. Navigate to **RDS** service
3. Click **Create database**
4. Choose **MySQL** as the engine
5. Select **MySQL 8.0** or higher
6. Choose **Production** template
7. Configure:
   - **DB instance identifier**: `capstone-db` (or your preferred name)
   - **Master username**: `admin` (or your preferred username)
   - **Master password**: Create a strong password (save it securely!)
   - **DB instance class**: `db.t3.micro` (for testing) or `db.t3.small` (for production)
   - **Storage**: 20 GB minimum (General Purpose SSD)
   - **VPC**: Use default VPC or create a new one
   - **Public access**: **Yes** (if EC2 is in different VPC) or **No** (if same VPC)
   - **Security group**: Create new or use existing
   - **Database name**: `capstone`
8. Click **Create database**

### Step 2: Configure Security Group

1. Go to your RDS instance → **Connectivity & security**
2. Click on the **Security group**
3. Add **Inbound rule**:
   - **Type**: MySQL/Aurora
   - **Port**: 3306
   - **Source**: Your EC2 security group or your IP address

### Step 3: Get RDS Endpoint

1. After creation, note the **Endpoint** (e.g., `capstone-db.xxxxx.us-east-1.rds.amazonaws.com`)
2. Note the **Port** (usually 3306)

---

## EC2 Instance Setup

### Step 1: Launch EC2 Instance

1. Navigate to **EC2** service
2. Click **Launch instance**
3. Configure:
   - **Name**: `capstone-server`
   - **AMI**: Ubuntu Server 22.04 LTS or Amazon Linux 2023
   - **Instance type**: `t3.small` or `t3.medium` (minimum 2GB RAM)
   - **Key pair**: Create new or use existing
   - **Network settings**: 
     - Allow HTTP (port 80)
     - Allow HTTPS (port 443)
     - Allow SSH (port 22) from your IP
   - **Storage**: 20 GB minimum
4. Click **Launch instance**

### Step 2: Connect to EC2 Instance

```bash
ssh -i your-key.pem ubuntu@your-ec2-ip
# or for Amazon Linux:
ssh -i your-key.pem ec2-user@your-ec2-ip
```

### Step 3: Update System

```bash
# For Ubuntu
sudo apt update && sudo apt upgrade -y

# For Amazon Linux 2023
sudo dnf update -y
```

### Step 4: Install Required Software

#### For Ubuntu:

```bash
# Install PHP 8.2 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    php8.2-intl php8.2-readline

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and NPM
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Nginx
sudo apt install -y nginx

# Install Git
sudo apt install -y git
```

#### For Amazon Linux 2023:

```bash
# Install PHP 8.2
sudo dnf install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    php8.2-intl php8.2-readline

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and NPM
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo dnf install -y nodejs

# Install Nginx
sudo dnf install -y nginx

# Install Git
sudo dnf install -y git
```

---

## Server Configuration

### Step 1: Configure PHP-FPM

Edit PHP-FPM configuration:

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Update these values:
```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

### Step 2: Configure Nginx

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/capstone
```

Add the following configuration (replace `your-domain.com` with your actual domain):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/capstone/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
# For Ubuntu
sudo ln -s /etc/nginx/sites-available/capstone /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx

# For Amazon Linux (no sites-available/sites-enabled)
sudo mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup
sudo nano /etc/nginx/nginx.conf
# Add the server block above inside http { }
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx
```

---

## Application Deployment

### Step 1: Clone Your Repository

```bash
cd /var/www
sudo git clone https://github.com/your-username/capstone.git
# or upload your files via SCP/SFTP
sudo chown -R $USER:$USER /var/www/capstone
cd capstone
```

### Step 2: Configure Environment

```bash
cp .env.example .env
nano .env
```

Update the following in `.env`:

```env
APP_NAME=Qalam
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration (from RDS)
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint.region.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=capstone
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Other production settings
LOG_CHANNEL=daily
LOG_LEVEL=error
SESSION_ENCRYPT=true
```

### Step 3: Run Deployment Script

```bash
chmod +x deploy-aws.sh
./deploy-aws.sh
```

Or manually:

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage
php artisan storage:link

# Set permissions
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Step 4: Set Proper Permissions

```bash
sudo chown -R www-data:www-data /var/www/capstone
sudo chmod -R 755 /var/www/capstone
sudo chmod -R 775 /var/www/capstone/storage
sudo chmod -R 775 /var/www/capstone/bootstrap/cache
```

---

## Queue Worker Setup

Laravel queues need to run continuously. Set up a supervisor to manage the queue worker.

### Step 1: Install Supervisor

```bash
# Ubuntu
sudo apt install -y supervisor

# Amazon Linux
sudo dnf install -y supervisor
```

### Step 2: Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/capstone-worker.conf
```

Add:

```ini
[program:capstone-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/capstone/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/capstone/storage/logs/worker.log
stopwaitsecs=3600
```

### Step 3: Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start capstone-worker:*
sudo supervisorctl status
```

---

## Scheduler Setup

Laravel's task scheduler needs to run every minute via cron.

### Step 1: Edit Crontab

```bash
sudo crontab -e -u www-data
```

Add this line:

```
* * * * * cd /var/www/capstone && php artisan schedule:run >> /dev/null 2>&1
```

Or for root:

```bash
sudo crontab -e
```

Add:

```
* * * * * cd /var/www/capstone && php artisan schedule:run >> /dev/null 2>&1
```

---

## SSL Certificate Setup

### Option 1: Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx
# or for Amazon Linux
sudo dnf install -y certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal is set up automatically
```

### Option 2: Using AWS Certificate Manager (ACM)

1. Request certificate in ACM
2. Use Application Load Balancer (ALB) with HTTPS listener
3. Point your domain to the ALB

---

## Monitoring and Maintenance

### View Logs

```bash
# Application logs
tail -f /var/www/capstone/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Queue worker logs
tail -f /var/www/capstone/storage/logs/worker.log
```

### Clear Caches

```bash
cd /var/www/capstone
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Update Application

```bash
cd /var/www/capstone
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart capstone-worker:*
```

---

## Troubleshooting

### Database Connection Issues

1. Check RDS security group allows EC2 IP
2. Verify `.env` has correct RDS endpoint
3. Test connection: `php artisan db:show`

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/capstone
sudo chmod -R 755 /var/www/capstone
sudo chmod -R 775 storage bootstrap/cache
```

### 500 Internal Server Error

1. Check logs: `tail -f storage/logs/laravel.log`
2. Check Nginx error log: `sudo tail -f /var/log/nginx/error.log`
3. Verify `.env` file exists and is configured
4. Clear caches: `php artisan config:clear`

### Queue Not Processing

1. Check supervisor: `sudo supervisorctl status`
2. Restart worker: `sudo supervisorctl restart capstone-worker:*`
3. Check logs: `tail -f storage/logs/worker.log`

### Assets Not Loading

1. Run `npm run build`
2. Check `public/build` directory exists
3. Verify Nginx serves static files correctly

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials are secure
- [ ] RDS security group restricts access
- [ ] SSL certificate installed
- [ ] Firewall configured (only ports 80, 443, 22)
- [ ] Regular backups configured
- [ ] Queue worker running via supervisor
- [ ] Scheduler configured via cron
- [ ] Log rotation configured

---

## Backup Strategy

### Database Backup

RDS automatically creates backups, but you can also:

```bash
# Manual backup
mysqldump -h your-rds-endpoint -u username -p database_name > backup.sql
```

### Application Backup

```bash
# Backup files
tar -czf capstone-backup-$(date +%Y%m%d).tar.gz /var/www/capstone
```

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check server logs: `/var/log/nginx/error.log`
3. Review this guide's troubleshooting section

---

**Last Updated**: January 2025
