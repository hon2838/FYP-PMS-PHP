# Paperwork Management System

A secure web-based paperwork management system for managing academic paperwork and approval workflows within an environment.

## Features

### User Management & Access Control
- Role-based access control (Admin, Staff, HOD, Dean)
- Department-based permissions
- User profile management with avatar support
- Activity tracking and session management
- Two-factor authentication (2FA) support
- Password recovery with secure reset links

### Document Management
- Secure document upload and storage
- Multiple file format support (PDF, DOC, DOCX)
- File size and type validation
- Document version control
- Automated file organization
- Export capabilities (PDF, Excel)

### Workflow Management
- Multi-level approval process
- Automated status tracking
- Real-time notifications
- Comments and feedback system
- Revision history tracking
- Bulk actions support

### Notifications & Communication
- Email notifications via SMTP
- Telegram bot integration
- Browser notifications
- Custom notification preferences
- Read receipts tracking

### Security Features
- Password hashing with bcrypt
- Session security measures
- CSRF protection
- XSS prevention
- SQL injection protection
- Rate limiting
- Audit logging
- File upload validation
- Two-factor authentication
- IP-based access control

### UI/UX Features
- Responsive design
- Dark/Light theme support
- Real-time updates
- Search and filter capabilities
- Mobile-friendly interface
- Accessibility compliance
- Interactive dashboards

## Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- SSL certificate (required for production)
- Minimum 2GB RAM
- 10GB storage space

### Additional Requirements
- SMTP server for email notifications
- Telegram Bot API token
- Composer package manager
- Node.js (optional, for development)

## Installation

1. **Clone the repository:**
```bash
git clone <repository-url>
cd soc-pms
```

2. **Create database and import schema:**
```bash
mysql -u root -p
CREATE DATABASE soc_pms;
USE soc_pms;
source database/soc_pms.sql
```

3. **Configure environment:**
```bash
cp .env.example .env
# Edit .env with your configuration
```

Required configurations:
```ini
# Database
DB_HOST=localhost
DB_NAME=soc_pms
DB_USER=your_user
DB_PASS=your_password

# Email
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_email
SMTP_PASS=your_password

# Security
APP_KEY=generate_random_32_char_string
SESSION_LIFETIME=120
SECURE_COOKIES=true
```

4. **Set up file permissions:**
```bash
chmod 755 -R storage/
chmod 755 -R uploads/
chmod 755 -R exports/
chown -R www-data:www-data storage/ uploads/ exports/
```

5. **Install dependencies:**
```bash
composer install
composer dump-autoload
```

6. **Security configuration:**
```apache
# Apache (.htaccess)
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set X-Content-Type-Options "nosniff"
```

## Default User Accounts

The system includes these default accounts:

| Role  | Username | Default Password | Access Level |
|-------|----------|-----------------|--------------|
| Admin | admin@soc.edu | 12345678 | Full System Access |
| HOD   | hod@soc.edu   | 12345678 | Department Management |
| Staff | staff@soc.edu | 12345678 | Basic Access |
| Dean  | dean@soc.edu  | 12345678 | Final Approval |

⚠️ **CRITICAL**: Change these passwords immediately after installation unless you are only using for testing purposes!

## Maintenance Tasks

### Daily
- Check error logs
- Monitor failed login attempts
- Review file uploads
- Verify email notifications

### Weekly
- Review audit logs
- Backup database
- Check disk space
- Monitor system performance

### Monthly
- Update dependencies
- Review user accounts
- Analyze usage patterns
- Test backup restoration

## Troubleshooting

Common issues and solutions:

1. **Upload fails**
   - Check folder permissions
   - Verify PHP upload limits
   - Review file size restrictions

2. **Email not sending**
   - Verify SMTP settings
   - Check error logs
   - Test email credentials

3. **Performance issues**
   - Enable PHP opcache
   - Optimize MySQL queries
   - Configure caching

## Support

For technical support:
- Create an issue in the repository
- Email: support@soc.edu
- Documentation: `/docs`

## Contributing

1. Fork the repository
2. Create feature branch
3. Follow coding standards:
   - PSR-12 for PHP
   - ESLint for JavaScript
   - Document all changes
4. Submit pull request

```