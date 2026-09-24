# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within OpenBooks SG, please report it responsibly.

**Do NOT open a public GitHub issue for security vulnerabilities.**

Instead, please email the maintainers directly. Include:

1. A description of the vulnerability
2. Steps to reproduce the issue
3. Potential impact and affected versions
4. Any suggested fix, if available

We will acknowledge receipt within 48 hours and aim to release a fix within 7 days for critical issues.

## Security Best Practices for Self-Hosting

When deploying OpenBooks SG in production:

- Always run `php artisan key:generate` to set a unique `APP_KEY`
- Use strong, unique passwords for admin accounts
- Enable two-factor authentication for all admin users
- Set `APP_ENV=production` and `APP_DEBUG=false`
- Use HTTPS with a valid TLS certificate
- Keep PHP, MySQL, and all dependencies up to date
- Restrict database access to the application server only
- Regularly back up your database and test restoration
