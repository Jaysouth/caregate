# CareGate WordPress Plugin - Admin Login Details

## Default Admin Credentials

After installing the CareGate WordPress plugin, an admin user is automatically created with the following credentials:

### Login Information
- **Email**: `admin@caregate.co.uk`
- **Password**: `CareGate2026!Admin`
- **Role**: Platform Administrator

### First Login Steps

1. **Access the Admin Panel**
   - Navigate to your WordPress site
   - Add `/wp-admin` to your site URL (e.g., `https://yoursite.com/wp-admin`)
   - Or use the CareGate admin dashboard link on your frontend

2. **Login with Default Credentials**
   - Enter the email: `admin@caregate.co.uk`
   - Enter the password: `CareGate2026!Admin`
   - Click "Login"

3. **Change Your Password (REQUIRED)**
   - **IMPORTANT**: You will be prompted to change your password on first login
   - Choose a strong, unique password
   - This is a mandatory security requirement

### Admin Dashboard Access

Once logged in, you will have access to the complete CareGate admin dashboard with:

#### 1. Overview Tab
- Platform statistics and metrics
- Total shifts, bookings, and users
- Real-time activity monitoring

#### 2. User Management
- Manage workers and facilities
- Approve new registrations
- View user profiles and documents

#### 3. Shifts Management
- Monitor all posted shifts
- View shift details and status
- Manage shift assignments

#### 4. Bookings
- Track all booking statuses
- Approve or reject applications
- Monitor shift fulfillment

#### 5. UK Invoicing
- Create HMRC-compliant invoices
- Manage invoice line items
- Track payment status
- Generate VAT reports

#### 6. Payroll
- Process worker payments
- Calculate UK tax deductions
- Manage payroll schedules
- Export payroll reports

#### 7. Clock System
- Monitor worker clock-ins/outs
- View real-time locations
- Manage attendance records
- Generate timesheet reports

#### 8. Compliance
- Track document expiry dates
- Monitor DBS checks
- Verify professional registrations
- Manage compliance alerts

#### 9. Payments
- View Paystack transactions
- Process facility payments
- Monitor payment status
- Handle payment disputes

#### 10. Reports
- Generate analytics reports
- Export data for accounting
- View performance metrics
- Create custom reports

## WordPress Admin Integration

The CareGate admin user is separate from your WordPress admin user:

- **WordPress Admin**: Manages the website, themes, and plugins
- **CareGate Admin**: Manages the CareGate platform, users, and operations

You can access both with different credentials.

## Security Best Practices

### Immediately After Installation

1. **Change the default password** - This is mandatory on first login
2. **Enable Two-Factor Authentication** - Available in CareGate → Settings → Security
3. **Configure reCAPTCHA** - Protect against automated attacks
4. **Review user permissions** - Ensure proper role assignments

### Ongoing Security

1. **Regular password updates** - Change every 90 days
2. **Monitor login activity** - Check for suspicious access
3. **Keep plugin updated** - Install updates when available
4. **Backup regularly** - Protect your data
5. **Use SSL/HTTPS** - Encrypt all communications

## Troubleshooting

### Cannot Login

**Problem**: "Invalid credentials" error
**Solution**:
1. Verify you're using the correct email: `admin@caregate.co.uk`
2. Check password is exactly: `CareGate2026!Admin` (case-sensitive)
3. Clear browser cache and cookies
4. Try a different browser

### Password Reset

If you've forgotten your password after changing it:

1. Go to the login page
2. Click "Forgot Password"
3. Enter your admin email
4. Check your inbox for reset instructions
5. Follow the link to create a new password

### Admin User Not Created

If the admin user wasn't created during installation:

1. Deactivate the plugin
2. Reactivate the plugin
3. The activation hook will create the admin user
4. Try logging in with default credentials

### Need to Reset to Default Password

**⚠️ Use with caution - only for development/testing**

To reset the admin password to default:
1. Access your WordPress database
2. Go to the `wp_users` table
3. Find the user with email `admin@caregate.co.uk`
4. Use WordPress password hashing to set: `CareGate2026!Admin`

Or run this WP-CLI command:
```bash
wp user update admin@caregate.co.uk --user_pass='CareGate2026!Admin'
```

## Plugin Settings Configuration

After logging in, configure these essential settings:

### CareGate → Settings

1. **Platform Fee**: Set commission percentage (default: 15%)
2. **Max Distance**: Set matching radius in km (default: 50km)
3. **Pricing Multipliers**: Configure urgency and skill premiums

### Security Settings

1. **Enable 2FA**: Turn on two-factor authentication
2. **reCAPTCHA Keys**: Add your Google reCAPTCHA credentials
3. **Session Timeout**: Configure auto-logout timing

### Integration Settings

1. **Twilio SMS**: Configure for OTP delivery
   - Account SID
   - Auth Token
   - From Phone Number

2. **Paystack Payments**: Set up payment gateway
   - Test/Live Public Keys
   - Test/Live Secret Keys
   - Webhook URL configuration

3. **Email Settings**: Configure SMTP for notifications
   - SMTP Host and Port
   - Authentication credentials
   - From email address

### Agency Details (Required)

Configure your agency information for UK invoicing:
- Agency Name
- Full UK Address
- Postcode
- Phone and Email
- VAT Number (if registered)
- Company Number

## Support

For technical support or questions:
- **Email**: support@caregate.co.uk
- **Documentation**: See PLUGIN_INSTALLATION.md
- **GitHub**: https://github.com/Jaysouth/caregate

---

**Last Updated**: January 2026
**Plugin Version**: 1.0.0
**WordPress Compatibility**: 5.0+
