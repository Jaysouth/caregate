# CareGate WordPress Plugin - Installation Guide

## 📦 Plugin Package: caregate-wordpress-plugin.zip

This ZIP file contains the complete CareGate on-demand care staffing platform as a WordPress plugin.

---

## 🚀 Quick Installation

### Method 1: WordPress Admin (Recommended)
1. Download `caregate-wordpress-plugin.zip`
2. Log in to WordPress Admin
3. Go to **Plugins → Add New**
4. Click **Upload Plugin**
5. Choose `caregate-wordpress-plugin.zip`
6. Click **Install Now**
7. Click **Activate Plugin**

### Method 2: Manual Upload
1. Download `caregate-wordpress-plugin.zip`
2. Extract the ZIP file
3. Rename extracted folder to `caregate`
4. Upload `caregate` folder to `/wp-content/plugins/`
5. Go to **Plugins** in WordPress Admin
6. Find **CareGate** and click **Activate**

### Method 3: Command Line
```bash
# Navigate to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Download and extract (or upload manually)
unzip caregate-wordpress-plugin.zip -d caregate

# Set permissions
chmod -R 755 caregate

# Activate via WordPress Admin or WP-CLI
wp plugin activate caregate
```

---

## ⚙️ Initial Configuration

### Step 1: Basic Settings
1. Go to **CareGate → Settings** in WordPress Admin
2. Configure basic settings:
   - Platform Fee: 15% (default)
   - Max Distance: 50 km
   - Urgency Multipliers: 1.3 (24h), 1.15 (48h)
   - Skill Multipliers: 1.2 (advanced), 1.4 (expert)

### Step 2: Agency Details (Required for UK Invoicing)
1. In **CareGate → Settings**, scroll to **Agency Details**
2. Fill in:
   - Agency Name: Your agency name
   - Agency Address: Full UK address
   - Agency Postcode: UK postcode
   - Agency Phone: Contact number
   - Agency Email: Contact email
   - VAT Number: Your VAT registration (if applicable)
   - Company Number: Companies House registration

### Step 3: Security Settings

**Enable 2FA (Two-Factor Authentication)**
1. In **CareGate → Settings**, find **Security** section
2. Check **Enable 2FA**
3. Configure OTP settings

**Enable Google reCAPTCHA**
1. Get keys from [Google reCAPTCHA](https://www.google.com/recaptcha/admin)
2. Add to **CareGate → Settings**:
   - reCAPTCHA Site Key
   - reCAPTCHA Secret Key

### Step 4: Twilio SMS Setup

**Get Twilio Credentials:**
1. Create account at [Twilio.com](https://www.twilio.com)
2. Get your Account SID and Auth Token
3. Purchase a UK phone number (+44)

**Configure in WordPress:**
1. Go to **CareGate → Settings → SMS**
2. Add:
   - Twilio Account SID: `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - Twilio Auth Token: Your auth token
   - Twilio From Phone: `+44XXXXXXXXXX`
3. Check **Enable Twilio**
4. Save changes

### Step 5: Paystack Payment Gateway

**Get Paystack API Keys:**
1. Create account at [Paystack.com](https://paystack.com)
2. Go to Settings → API Keys & Webhooks
3. Get Test and Live keys

**Configure in WordPress:**
1. Go to **CareGate → Settings → Payments**
2. Add:
   - Paystack Test Public Key: `pk_test_xxxx`
   - Paystack Test Secret Key: `sk_test_xxxx`
   - Paystack Live Public Key: `pk_live_xxxx` (when ready)
   - Paystack Live Secret Key: `sk_live_xxxx` (when ready)
3. Configure webhook:
   - Webhook URL: `https://yoursite.com/wp-json/caregate/v1/payments/webhook`
   - Add this URL to your Paystack dashboard
4. Toggle **Live Mode** when ready for production
5. Save changes

---

## 🎨 Add to Your Site

### Option 1: Complete Platform (Recommended)
Create a new page and add the main shortcode:

```
[caregate_app]
```

This provides the complete platform:
- Login/Registration (multi-step with UK fields)
- Worker Dashboard (matched shifts, bookings)
- Facility Dashboard (create shifts, manage applications)
- Compliance tracking
- Timesheet management
- All features in one place

### Option 2: Individual Features
Use specific shortcodes on different pages:

**Registration:**
```
[caregate_register]
```

**Login:**
```
[caregate_login]
```

**Worker Dashboard:**
```
[caregate_worker_dashboard]
```

**Facility Dashboard:**
```
[caregate_facility_dashboard]
```

**Search Shifts (Public):**
```
[caregate_search_shifts]
```

**View all 16+ shortcodes** in `PLUGIN_SHORTCODES.md`

---

## 👥 User Roles

The plugin creates two custom user roles:

### Care Worker (`caregate_worker`)
Capabilities:
- Browse and apply for shifts
- View matched shifts based on profile
- Clock in/out (card scan or manual)
- Submit timesheets
- Upload compliance documents
- View bookings and earnings
- Add bank account for payments

### Care Facility (`caregate_facility`)
Capabilities:
- Create and manage shifts
- View shift applications
- Confirm bookings
- Approve timesheets
- Manual clock entry for workers
- View invoices
- Make payments via Paystack

---

## 📊 Admin Features

### CareGate Admin Menu

**Dashboard**
- Overview statistics
- Total shifts, bookings
- Workers currently clocked in
- Today's activity

**UK Invoicing**
- Create HMRC-compliant invoices
- Add line items with VAT (20%)
- Send to care facilities
- Track payment status (draft/sent/paid)
- Payment link integration with Paystack

**Payroll**
- Select worker by ID
- View full profile with photo
- Calculate UK tax (PAYE) and NI
- Automatic deductions (pension, insurance, agency fees)
- Generate salary slips
- Send payments via Paystack

**Timesheet Clock**
- Manual clock entry
- Live status dashboard
- Auto-refresh every 30 seconds
- Currently working workers
- Completed shifts today

**Settings**
- Platform configuration
- Security settings (2FA, reCAPTCHA)
- Twilio SMS configuration
- Paystack payment configuration
- Agency details
- Fee percentages

---

## 🗄️ Database Tables

The plugin automatically creates 15 tables on activation:

**Core Tables:**
- `wp_caregate_shifts` - Shift listings
- `wp_caregate_bookings` - Shift bookings
- `wp_caregate_timesheets` - Timesheet submissions
- `wp_caregate_compliance` - Compliance documents
- `wp_caregate_user_meta` - Extended user profiles

**Financial Tables:**
- `wp_caregate_invoices` - Billing invoices
- `wp_caregate_invoice_timesheets` - Invoice-timesheet links
- `wp_caregate_uk_invoices` - UK format invoices
- `wp_caregate_invoice_items` - Invoice line items
- `wp_caregate_payroll` - Salary payments

**Clock System:**
- `wp_caregate_clock_records` - Clock in/out records

**Payment System:**
- `wp_caregate_payments` - Payment transactions
- `wp_caregate_bank_accounts` - Worker bank accounts

**Security:**
- `wp_caregate_otp` - 2FA OTP codes
- `wp_caregate_sms_log` - SMS delivery tracking

---

## 🔌 REST API Endpoints

53+ API endpoints at `/wp-json/caregate/v1/`:

**Authentication** (5 endpoints)
- `/auth/register` - User registration
- `/auth/login` - User login
- `/auth/verify-otp` - Verify 2FA code
- `/auth/resend-otp` - Resend OTP
- `/config/recaptcha` - Get reCAPTCHA config

**Shifts** (6 endpoints)
- `/shifts` - List/create shifts
- `/shifts/matches` - Get matched shifts
- `/shifts/{id}` - Get/update/delete shift

**Bookings** (5 endpoints)
- `/bookings` - List/create bookings
- `/bookings/{id}/confirm` - Confirm booking
- `/bookings/{id}/complete` - Complete shift

**Timesheets** (3 endpoints)
- `/timesheets` - List/create timesheets
- `/timesheets/{id}/approve` - Approve timesheet

**Payroll** (4 endpoints)
- `/payroll/worker/{id}` - Get worker details
- `/payroll/calculate` - Calculate UK tax/NI
- `/payroll` - Create/list payroll
- `/payroll/{id}/send` - Send salary slip

**Clock System** (5 endpoints)
- `/clock/in` - Clock in
- `/clock/out` - Clock out
- `/clock/status` - Get current status
- `/clock/records` - List clock records
- `/clock/manual` - Manual clock entry

**UK Invoicing** (6 endpoints)
- `/uk-invoices` - Create/list invoices
- `/uk-invoices/{id}` - Get/update invoice
- `/uk-invoices/{id}/send` - Send invoice
- `/uk-invoices/{id}/paid` - Mark as paid

**Payments** (8 endpoints)
- `/payments/initialize` - Create payment link
- `/payments/verify` - Verify payment
- `/payments/transfer` - Send payout
- `/payments/webhook` - Paystack webhook
- `/payments/banks` - List UK banks
- `/payments/add-account` - Add bank account
- `/payments/transactions` - Transaction history
- `/payments/balance` - Check wallet

**Billing & Compliance** (11+ endpoints)

---

## 🧪 Testing

### Test the Installation

1. **Create a test page:**
   - Pages → Add New
   - Title: "CareGate Test"
   - Content: `[caregate_app]`
   - Publish

2. **Visit the page:**
   - Click "Register" tab
   - Select "Care Worker" role
   - Fill in Step 1 (account info)
   - Click "Next" → Step 2 (UK details)
   - Click "Previous" to verify navigation works
   - Complete all 3 steps

3. **Test 2FA (if enabled):**
   - Complete registration
   - Check email/SMS for OTP code
   - Enter code to verify

4. **Test GPS Distance:**
   ```bash
   php tests/gps-accuracy.test.php
   ```
   Expected: 10/10 tests passing

### Test Payments (Sandbox Mode)

**Paystack Test Cards:**
- Success: `4084084084084081`
- Declined: `4111111111111112`
- CVV: Any 3 digits
- Expiry: Any future date

**Test Workflow:**
1. Facility creates shift
2. Worker applies for shift
3. Facility confirms (triggers payment)
4. Use test card
5. Verify webhook confirmation

---

## 🔒 Security Checklist

Before going live:

### Required:
- [ ] Enable HTTPS (SSL certificate)
- [ ] Configure 2FA
- [ ] Set up reCAPTCHA
- [ ] Configure Twilio for SMS
- [ ] Test Paystack in test mode
- [ ] Review user permissions
- [ ] Set strong admin password

### Recommended:
- [ ] Add rate limiting plugin
- [ ] Configure WordPress security plugin
- [ ] Set up automated backups
- [ ] Enable WordPress auto-updates
- [ ] Configure firewall rules
- [ ] Set up monitoring/alerts

### Paystack Live Mode:
- [ ] Thoroughly test in test mode first
- [ ] Add live API keys
- [ ] Update webhook URL
- [ ] Enable live mode toggle
- [ ] Test with real bank account (small amount)
- [ ] Monitor transactions closely

---

## 📚 Documentation

**Complete documentation included:**

1. **PLUGIN_SHORTCODES.md** (17,000+ words)
   - All 16 shortcodes
   - Parameters and options
   - Configuration guide
   - Database schema
   - API endpoints
   - Customization examples

2. **SHORTCODES_QUICK_REFERENCE.md** (5,000+ words)
   - Quick reference card
   - Common use cases
   - Setup options
   - Troubleshooting

3. **WORDPRESS_CONVERSION.md**
   - Conversion from Node.js guide
   - Architecture details
   - Migration instructions

4. **README.md**
   - Plugin overview
   - Feature list
   - Quick start guide

---

## 🆘 Troubleshooting

### Database Tables Not Created
```bash
# Deactivate and reactivate plugin
wp plugin deactivate caregate && wp plugin activate caregate
```

### Shortcode Not Working
- Ensure plugin is activated
- Check page is published
- Try different theme
- Check for JavaScript errors in console

### 2FA OTP Not Received
- Check Twilio configuration
- Verify phone number format (+44...)
- Check SMS log in database
- Verify email delivery working

### Payment Issues
- Verify Paystack keys are correct
- Check webhook URL is accessible (HTTPS)
- Test in test mode first
- Check Paystack dashboard for errors

### GPS Distance Incorrect
```bash
# Run test suite
php tests/gps-accuracy.test.php

# Should show 10/10 tests passing
# If failing, check lat/lng coordinates
```

---

## 🎯 Next Steps

1. **Configure all settings** as described above
2. **Test thoroughly** in a staging environment
3. **Add content** - Create initial shifts/users for testing
4. **Customize styling** - Match your brand colors
5. **Go live** - Switch Paystack to live mode when ready

---

## 📊 Package Contents

**Files in this ZIP:**
- 27 PHP class files
- 20+ admin/public templates
- JavaScript and CSS files
- README and documentation
- Database schema

**Total Size:** ~63 KB (compressed)

**PHP Classes:**
- Core: CareGate, Activator, Deactivator, Loader, API
- Features: Shifts, Bookings, Timesheets, Compliance
- Financial: Billing, UK Invoice, Payroll, Clock
- Integration: Twilio, Paystack, TwoFA, reCAPTCHA
- Auth: Authentication, Authorization

---

## 📝 Version Information

**Plugin Version:** 1.0.0
**WordPress Requirement:** 5.0+
**PHP Requirement:** 7.4+
**Tested up to:** WordPress 6.4
**License:** GPL v2 or later

---

## 🌟 Features Summary

✅ Complete care staffing platform
✅ Multi-step registration (UK standard fields)
✅ Intelligent matching algorithm (99.5%+ GPS accuracy)
✅ Dynamic pricing engine
✅ UK tax calculations (PAYE, NI)
✅ Two-factor authentication
✅ Twilio SMS integration
✅ Paystack payment gateway (send & receive)
✅ HMRC-compliant invoicing
✅ Timesheet clock system
✅ Compliance tracking
✅ Mobile-first responsive design
✅ 16+ shortcodes
✅ 53+ REST API endpoints
✅ 15 database tables
✅ Professional admin interface

---

## 🤝 Support

For issues or questions:
1. Check documentation files
2. Review troubleshooting section
3. Test in staging environment first
4. Check WordPress/PHP error logs

---

## 🚀 Ready to Launch!

Your complete CareGate platform is ready to deploy. Follow the steps above and you'll have a production-ready care staffing platform running on WordPress!

**Happy staffing! 🏥👨‍⚕️**
