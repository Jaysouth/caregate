# CareGate WordPress Plugin - Complete Guide & Shortcodes

## 📦 Plugin Overview

**Plugin Name:** CareGate - On-Demand Care Staffing Platform  
**Version:** 1.0.0  
**Author:** CareGate Team  
**Requires WordPress:** 5.0 or higher  
**Requires PHP:** 7.4 or higher  
**License:** GPLv2 or later  

---

## 🚀 Installation

### Method 1: Manual Installation

1. **Download the plugin folder:**
   - Copy the entire `caregate-plugin/` directory from this repository

2. **Upload to WordPress:**
   ```bash
   cp -r caregate-plugin /path/to/wordpress/wp-content/plugins/
   ```

3. **Activate via WordPress Admin:**
   - Go to **WordPress Admin → Plugins**
   - Find "CareGate - On-Demand Care Staffing Platform"
   - Click **Activate**

4. **Database Setup:**
   - Tables are automatically created on activation
   - 12 MySQL tables will be created with `wp_caregate_` prefix

### Method 2: WordPress Admin Upload

1. **Zip the plugin folder:**
   ```bash
   cd caregate-plugin
   zip -r caregate-plugin.zip .
   ```

2. **Upload via WordPress:**
   - Go to **WordPress Admin → Plugins → Add New**
   - Click **Upload Plugin**
   - Choose `caregate-plugin.zip`
   - Click **Install Now**
   - Click **Activate Plugin**

### Method 3: FTP Upload

1. **Connect via FTP/SFTP**
2. **Navigate to:** `/wp-content/plugins/`
3. **Upload:** `caregate-plugin/` folder
4. **Activate:** Via WordPress Admin → Plugins

---

## 🎯 All Available Shortcodes

### 1. Main Application Shortcode

**Shortcode:** `[caregate_app]`

**Description:** Displays the complete CareGate application interface including login, registration, and dashboards for both care workers and care facilities.

**Usage:**
```
[caregate_app]
```

**Where to Use:**
- Create a new page: **Pages → Add New**
- Add the shortcode to the page content
- Publish the page
- Users can now access the full CareGate platform on this page

**Features Included:**
- ✅ Login/Register tabs with multi-step registration
- ✅ Care Worker dashboard (matched shifts, bookings, compliance)
- ✅ Care Facility dashboard (create shifts, manage applications)
- ✅ Real-time shift matching with distance calculation
- ✅ Booking management
- ✅ Timesheet submission
- ✅ Compliance tracking
- ✅ Mobile-first responsive design

**Example Page Setup:**
1. Create page: **CareGate Platform**
2. Add shortcode: `[caregate_app]`
3. Publish
4. URL: `https://yoursite.com/caregate-platform/`

---

### 2. Worker Dashboard Shortcode

**Shortcode:** `[caregate_worker_dashboard]`

**Description:** Displays only the care worker dashboard (for logged-in workers).

**Usage:**
```
[caregate_worker_dashboard]
```

**Features:**
- Matched shifts based on skills, location, and availability
- Current bookings
- Compliance status and document tracking
- Worker profile information

**Access:** Requires user to be logged in with `caregate_worker` role

---

### 3. Facility Dashboard Shortcode

**Shortcode:** `[caregate_facility_dashboard]`

**Description:** Displays only the care facility dashboard (for logged-in facilities).

**Usage:**
```
[caregate_facility_dashboard]
```

**Features:**
- Create new shifts
- View posted shifts
- Manage shift applications
- Approve/confirm bookings
- Timesheet approvals

**Access:** Requires user to be logged in with `caregate_facility` role

---

### 4. Shift Listings Shortcode

**Shortcode:** `[caregate_shifts]`

**Description:** Displays available shifts in a list/card format.

**Usage:**
```
[caregate_shifts]
```

**Optional Parameters:**
```
[caregate_shifts limit="10" facility_id="123"]
```

**Parameters:**
- `limit` - Number of shifts to display (default: 20)
- `facility_id` - Filter by specific facility ID
- `skill` - Filter by required skill

**Example:**
```
[caregate_shifts limit="5" skill="Nursing"]
```

---

### 5. Registration Form Shortcode

**Shortcode:** `[caregate_register]`

**Description:** Displays the multi-step registration form with role selection.

**Usage:**
```
[caregate_register]
```

**Features:**
- 3-step registration process
- Care Worker or Care Facility selection
- UK standard required fields
- Real-time validation
- Progress indicator

**Parameters:**
```
[caregate_register role="worker"]
```

**Parameters:**
- `role` - Pre-select role: "worker" or "facility" (optional)

**Examples:**
```
[caregate_register]
[caregate_register role="worker"]
[caregate_register role="facility"]
```

---

### 6. Login Form Shortcode

**Shortcode:** `[caregate_login]`

**Description:** Displays only the login form.

**Usage:**
```
[caregate_login]
```

**Features:**
- Email and password fields
- reCAPTCHA integration (if enabled)
- 2FA/OTP verification
- "Remember me" option
- Redirect after login

**Parameters:**
```
[caregate_login redirect="/dashboard/"]
```

**Parameters:**
- `redirect` - URL to redirect after successful login

---

### 7. Matched Shifts Shortcode

**Shortcode:** `[caregate_matched_shifts]`

**Description:** Shows shifts matched to the logged-in worker based on skills, location, and availability.

**Usage:**
```
[caregate_matched_shifts]
```

**Features:**
- Match score (0-100)
- Distance calculation in km
- Dynamic pricing display
- Skills match indicator
- Apply button

**Access:** Requires logged-in care worker

---

### 8. My Bookings Shortcode

**Shortcode:** `[caregate_my_bookings]`

**Description:** Displays user's current bookings and booking history.

**Usage:**
```
[caregate_my_bookings]
```

**Parameters:**
```
[caregate_my_bookings status="confirmed"]
```

**Parameters:**
- `status` - Filter by status: "pending", "confirmed", "completed", "cancelled"

**Features:**
- Booking status badges
- Shift details
- Cancel booking option
- Complete shift option

---

### 9. Compliance Tracker Shortcode

**Shortcode:** `[caregate_compliance]`

**Description:** Shows compliance status and document tracking for care workers.

**Usage:**
```
[caregate_compliance]
```

**Features:**
- 6 UK mandatory documents tracking
- Expiry date monitoring
- Completion percentage
- Upload document links
- Color-coded status indicators

**Access:** Requires logged-in care worker

---

### 10. Shift Creation Form Shortcode

**Shortcode:** `[caregate_create_shift]`

**Description:** Form for care facilities to create new shifts.

**Usage:**
```
[caregate_create_shift]
```

**Features:**
- Shift title and description
- Date and time selection
- Required skills selection
- Base rate input
- Dynamic pricing preview

**Access:** Requires logged-in care facility

---

### 11. Statistics Dashboard Shortcode

**Shortcode:** `[caregate_stats]`

**Description:** Displays platform statistics.

**Usage:**
```
[caregate_stats]
```

**Optional Parameters:**
```
[caregate_stats user_type="worker"]
```

**Parameters:**
- `user_type` - "worker" or "facility" for role-specific stats

**Statistics Shown:**
- Total shifts
- Active bookings
- Completed shifts
- Average rating
- Total earnings (for workers)
- Total spent (for facilities)

---

### 12. Clock In/Out Widget Shortcode

**Shortcode:** `[caregate_clock_widget]`

**Description:** Quick clock in/out widget for care workers.

**Usage:**
```
[caregate_clock_widget]
```

**Features:**
- Current status display
- Clock in button
- Clock out button
- Current shift hours
- Break time tracking

**Access:** Requires logged-in care worker

---

### 13. Timesheet Submission Shortcode

**Shortcode:** `[caregate_timesheet]`

**Description:** Form for workers to submit timesheets.

**Usage:**
```
[caregate_timesheet]
```

**Features:**
- Select completed shift
- Clock in/out times (auto-filled if used clock system)
- Break time
- Notes
- Submit for approval

**Access:** Requires logged-in care worker

---

### 14. Invoice List Shortcode

**Shortcode:** `[caregate_invoices]`

**Description:** Displays invoices for facilities or workers.

**Usage:**
```
[caregate_invoices]
```

**Parameters:**
```
[caregate_invoices type="received" status="unpaid"]
```

**Parameters:**
- `type` - "sent" or "received"
- `status` - "draft", "sent", "paid"

**Access:** Requires logged-in user

---

### 15. Search Shifts Shortcode

**Shortcode:** `[caregate_search_shifts]`

**Description:** Advanced shift search form with filters.

**Usage:**
```
[caregate_search_shifts]
```

**Features:**
- Date range filter
- Location/distance filter
- Skills filter
- Rate range filter
- Facility type filter
- Sort options

---

### 16. User Profile Shortcode

**Shortcode:** `[caregate_profile]`

**Description:** Displays and allows editing of user profile.

**Usage:**
```
[caregate_profile]
```

**Features:**
- View profile information
- Edit personal details
- Update professional information
- Change password
- Upload documents

**Access:** Requires logged-in user

---

## ⚙️ Configuration

### Initial Setup

1. **Activate Plugin**
2. **Go to:** WordPress Admin → **CareGate → Settings**
3. **Configure:**

#### Platform Settings
- Platform fee: 15% (default)
- Max matching distance: 50 km
- Urgency multipliers: 24h (1.3x), 48h (1.15x)
- Skill multipliers: Advanced (1.2x), Expert (1.4x)

#### Security Settings
- Enable 2FA: Yes/No
- reCAPTCHA Site Key: [Your key]
- reCAPTCHA Secret Key: [Your secret]
- SMS API Key: [Optional]
- SMS API URL: [Optional]

#### Agency Details (for invoicing)
- Agency Name
- Address
- Postcode
- Phone
- Email
- VAT Number
- Company Number

#### Payroll Settings
- Agency Fee: 10% (default)

---

## 🎨 Styling & Customization

### Override Default Styles

Create a file in your theme: `caregate-custom.css`

```css
/* Override CareGate styles */
.caregate-app {
    /* Your custom styles */
}

.caregate-shift-card {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
```

Enqueue in your theme's `functions.php`:
```php
add_action('wp_enqueue_scripts', 'caregate_custom_styles', 20);
function caregate_custom_styles() {
    wp_enqueue_style('caregate-custom', 
        get_stylesheet_directory_uri() . '/caregate-custom.css',
        array('caregate-public')
    );
}
```

---

## 🔌 Admin Menu Structure

After activation, the following menu items are available:

**CareGate →**
- **Dashboard** - Overview statistics
- **UK Invoicing** - Create and manage invoices to facilities
- **Payroll** - Calculate and send salary payments to workers
- **Timesheet Clock** - Manual clock entry and live monitoring
- **Settings** - Configure all platform settings

---

## 📊 Database Tables Created

The plugin creates 12 tables on activation:

1. `wp_caregate_shifts` - Shift listings
2. `wp_caregate_bookings` - Shift applications and bookings
3. `wp_caregate_timesheets` - Time tracking records
4. `wp_caregate_invoices` - Invoice records
5. `wp_caregate_invoice_timesheets` - Invoice-timesheet relationships
6. `wp_caregate_compliance` - Compliance documents
7. `wp_caregate_user_meta` - Extended user metadata
8. `wp_caregate_otp` - 2FA OTP codes
9. `wp_caregate_payroll` - Salary payment records
10. `wp_caregate_clock_records` - Clock in/out records
11. `wp_caregate_uk_invoices` - UK standard invoices
12. `wp_caregate_invoice_items` - Invoice line items

---

## 🔐 User Roles

### Care Worker (`caregate_worker`)
**Capabilities:**
- View matched shifts
- Apply for shifts
- Clock in/out
- Submit timesheets
- Upload compliance documents
- View profile and bookings

### Care Facility (`caregate_facility`)
**Capabilities:**
- Create shifts
- View shift applications
- Confirm bookings
- Approve timesheets
- Receive invoices
- Manual clock entry for workers

---

## 🌐 REST API Endpoints

All endpoints are available at: `/wp-json/caregate/v1/`

### Authentication
- `POST /auth/register` - Register new user
- `POST /auth/login` - User login
- `POST /auth/verify-otp` - Verify 2FA code
- `POST /auth/resend-otp` - Resend OTP code

### Shifts
- `GET /shifts` - List shifts
- `POST /shifts` - Create shift
- `GET /shifts/{id}` - Get shift details
- `PUT /shifts/{id}` - Update shift
- `DELETE /shifts/{id}` - Delete shift
- `GET /shifts/matches` - Get matched shifts

### Bookings
- `GET /bookings` - List bookings
- `POST /bookings` - Apply for shift
- `PUT /bookings/{id}/confirm` - Confirm booking
- `PUT /bookings/{id}/complete` - Complete booking
- `DELETE /bookings/{id}` - Cancel booking

### Timesheets
- `GET /timesheets` - List timesheets
- `POST /timesheets` - Submit timesheet
- `PUT /timesheets/{id}/approve` - Approve timesheet

### Billing
- `GET /billing` - List invoices
- `POST /billing/generate` - Generate invoice
- `GET /billing/{id}` - Get invoice
- `GET /billing/summary/stats` - Get billing stats

### Compliance
- `GET /compliance/worker/{id}` - Get worker compliance
- `POST /compliance` - Add compliance document
- `GET /compliance/required-documents` - List required documents

### Payroll
- `GET /payroll/worker/{id}` - Get worker details
- `POST /payroll/calculate` - Calculate pay with UK tax
- `POST /payroll` - Create payroll record
- `GET /payroll` - List payroll records
- `POST /payroll/{id}/send` - Send salary slip

### Clock System
- `POST /clock/in` - Clock in
- `POST /clock/out` - Clock out
- `GET /clock/status` - Get current status
- `GET /clock/records` - List clock records
- `POST /clock/manual` - Manual clock entry (admin)

### UK Invoices
- `GET /uk-invoices` - List invoices
- `POST /uk-invoices` - Create invoice
- `GET /uk-invoices/{id}` - Get invoice
- `PUT /uk-invoices/{id}` - Update invoice
- `POST /uk-invoices/{id}/send` - Send invoice
- `POST /uk-invoices/{id}/paid` - Mark as paid

---

## 📱 Mobile Responsiveness

All shortcodes render mobile-first responsive content:

- ✅ Touch-friendly interfaces
- ✅ Optimized for iOS and Android
- ✅ Responsive breakpoints: 320px, 768px, 1024px, 1200px
- ✅ Progressive Web App (PWA) ready

---

## 🔄 Updates & Maintenance

### Automatic Updates
- Configure automatic updates in WordPress Admin
- Updates available via WordPress.org (if published)

### Manual Updates
1. Deactivate plugin
2. Replace plugin folder with new version
3. Reactivate plugin
4. Database will auto-upgrade if needed

---

## 🆘 Support & Documentation

### Getting Help
- **Documentation:** See `caregate-plugin/README.md`
- **WordPress Conversion Guide:** `WORDPRESS_CONVERSION.md`
- **Security Guide:** `SECURITY.md`
- **Deployment Guide:** `DEPLOYMENT.md`

### Common Issues

**Issue:** Shortcode displays as text
**Solution:** Ensure plugin is activated

**Issue:** 404 on API endpoints
**Solution:** Go to Settings → Permalinks and click "Save Changes"

**Issue:** Database tables not created
**Solution:** Deactivate and reactivate plugin

**Issue:** CSS not loading
**Solution:** Clear WordPress cache and browser cache

---

## 📝 Example Page Setups

### Setup 1: Single Page Application

**Page:** CareGate Platform  
**Shortcode:** `[caregate_app]`  
**URL:** `/caregate/`

This displays the complete platform on one page.

### Setup 2: Multiple Pages

**Page 1:** Register  
**Shortcode:** `[caregate_register]`  
**URL:** `/register/`

**Page 2:** Login  
**Shortcode:** `[caregate_login redirect="/dashboard/"]`  
**URL:** `/login/`

**Page 3:** Worker Dashboard  
**Shortcode:** `[caregate_worker_dashboard]`  
**URL:** `/dashboard/`

**Page 4:** Facility Dashboard  
**Shortcode:** `[caregate_facility_dashboard]`  
**URL:** `/facility-dashboard/`

**Page 5:** Browse Shifts  
**Shortcode:** `[caregate_shifts limit="20"]`  
**URL:** `/shifts/`

### Setup 3: Public Facing

**Page 1:** Home (marketing page)  
**Page 2:** Browse Shifts  
**Shortcode:** `[caregate_search_shifts]`

**Page 3:** Register  
**Shortcode:** `[caregate_register]`

**Page 4:** Platform (after login)  
**Shortcode:** `[caregate_app]`

---

## 🎉 Quick Start Checklist

- [ ] Install and activate plugin
- [ ] Configure settings (CareGate → Settings)
- [ ] Add agency details for invoicing
- [ ] Configure 2FA and reCAPTCHA (optional)
- [ ] Create page with `[caregate_app]` shortcode
- [ ] Test registration for both roles
- [ ] Create test shift
- [ ] Test matching algorithm
- [ ] Test booking workflow
- [ ] Configure permalinks if needed

---

## 📞 Technical Requirements

**Minimum Requirements:**
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- HTTPS (recommended for production)

**Recommended:**
- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+
- SSL certificate
- CDN for assets

---

## 🚀 Production Deployment

1. **Install on production WordPress**
2. **Configure HTTPS/SSL**
3. **Set up reCAPTCHA** (Google)
4. **Configure SMS provider** (Twilio/AWS SNS)
5. **Set agency details**
6. **Test all workflows**
7. **Monitor performance**

---

## 📋 Summary

**Total Shortcodes:** 16+
**API Endpoints:** 45+
**Database Tables:** 12
**User Roles:** 2
**Admin Pages:** 6

**Main Shortcode:** `[caregate_app]` - Complete platform in one shortcode

**Installation:** Copy to `/wp-content/plugins/` → Activate → Configure → Use shortcodes

**Ready to use!** 🎉
