# CareGate WordPress Plugin

Mobile-first on-demand staffing platform connecting pre-vetted carers and nurses with UK care facilities - now available as a WordPress plugin!

## Description

CareGate is a comprehensive staffing solution for healthcare facilities that need urgent or temporary cover. The plugin provides:

- **Intelligent Matching Algorithm**: Matches workers with shifts based on skills (40%), location (30%), and availability (30%)
- **Dynamic Pricing**: Automatic rate adjustments based on urgency and skill level requirements
- **UK Compliance Tracking**: Built-in tracking for DBS checks, NMC registration, and other required documents
- **Automated Workflows**: Digital timesheets, approval processes, and invoice generation
- **Mobile-First Design**: Responsive interface that works perfectly on all devices

## Installation

### Via WordPress Admin

1. Download the `caregate-plugin` folder
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to CareGate → Settings to configure

### Manual Installation

1. Upload the plugin files to the `/wp-content/plugins/caregate-plugin` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings under CareGate menu

## Usage

### Display the CareGate Interface

Add the shortcode to any page or post:

```
[caregate_app]
```

This will display the full CareGate application interface.

### User Roles

The plugin creates two custom roles:

**Care Worker Role** (`caregate_worker`)
- View available shifts
- Apply for shifts
- Manage bookings
- Submit timesheets
- Track compliance documents

**Care Facility Role** (`caregate_facility`)
- Create and manage shifts
- Review worker applications
- Approve bookings
- Approve timesheets
- Generate invoices

### REST API Endpoints

All endpoints are available at `/wp-json/caregate/v1/`:

**Authentication**
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login user

**Shifts**
- `POST /shifts` - Create shift (facility only)
- `GET /shifts` - List shifts
- `GET /shifts/matches` - Get matched shifts (worker only)
- `GET /shifts/{id}` - Get shift details
- `PUT /shifts/{id}` - Update shift
- `DELETE /shifts/{id}` - Delete shift

**Bookings**
- `POST /bookings` - Apply for shift (worker only)
- `GET /bookings` - List bookings
- `PUT /bookings/{id}/confirm` - Confirm booking (facility only)
- `PUT /bookings/{id}/complete` - Mark completed
- `DELETE /bookings/{id}` - Cancel booking

**Timesheets**
- `POST /timesheets` - Create timesheet
- `GET /timesheets` - List timesheets
- `PUT /timesheets/{id}/approve` - Approve timesheet (facility only)

**Billing**
- `POST /billing/generate` - Generate invoice (facility only)
- `GET /billing` - List invoices
- `GET /billing/{id}` - Get invoice details
- `GET /billing/summary/stats` - Get billing statistics

**Compliance**
- `POST /compliance` - Add compliance record
- `GET /compliance/worker/{id}` - Get worker compliance status
- `GET /compliance/required-documents` - List required documents

## Configuration

### Plugin Settings

Navigate to **CareGate → Settings** to configure:

- **Platform Fee Percentage**: Default 0.15 (15%)
- **Max Matching Distance**: Default 50 km
- **Urgency Premiums**:
  - Within 24 hours: 1.3x (30% premium)
  - Within 48 hours: 1.15x (15% premium)
- **Skill Level Premiums**:
  - Advanced: 1.2x (20% premium)
  - Expert: 1.4x (40% premium)

## Database Tables

The plugin creates the following tables:

- `wp_caregate_shifts` - Shift postings
- `wp_caregate_bookings` - Worker applications
- `wp_caregate_timesheets` - Time tracking
- `wp_caregate_invoices` - Billing records
- `wp_caregate_invoice_timesheets` - Invoice-timesheet relationships
- `wp_caregate_compliance` - Compliance documents
- `wp_caregate_user_meta` - Additional user information

## Features

### For Care Workers

- Browse available shifts with intelligent matching
- See match scores (0-100) based on skills and location
- Apply for shifts with one click
- Track booking status
- Submit digital timesheets
- Manage compliance documents
- View earnings and payment history

### For Care Facilities

- Post shifts with detailed requirements
- Set base rates with automatic dynamic pricing
- Review worker applications with skills and ratings
- Confirm bookings
- Approve timesheets
- Generate invoices automatically
- Track facility spending

### Intelligent Matching

The matching algorithm scores shifts based on:

1. **Skills Match (40%)**: How many required skills the worker has
2. **Location Proximity (30%)**: Distance from worker to facility
3. **Availability (30%)**: Worker's availability for the shift

Only shifts with 50+ match score are shown to workers.

### Dynamic Pricing

Shift rates are calculated automatically:

```
Base Rate × Urgency Multiplier × Skill Level Multiplier = Dynamic Rate
```

Example: £20/hour base × 1.3 (urgent) × 1.2 (advanced) = £31.20/hour

### UK Healthcare Compliance

Tracks 6 mandatory document types:
- DBS Check (Disclosure and Barring Service)
- Right to Work
- Professional Registration (NMC for nurses)
- Health Clearance
- Mandatory Training
- Liability Insurance

Provides completion percentage and expiry tracking.

## Developer Information

### Hooks and Filters

Coming soon: Custom hooks for extending functionality

### Custom Development

The plugin is built with extensibility in mind. All major functions are available via WordPress actions and filters.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support, please visit: https://github.com/Jaysouth/caregate/issues

## Changelog

### 1.0.0
- Initial release
- Full WordPress plugin conversion from Node.js application
- Complete REST API implementation
- Admin dashboard
- Mobile-first frontend
- Intelligent matching algorithm
- Dynamic pricing engine
- Compliance tracking
- Automated billing

## License

GPL v2 or later

## Credits

Converted from the original CareGate Node.js/Express application.

## Screenshots

1. Worker Dashboard - View matched shifts with scores
2. Facility Dashboard - Create and manage shifts
3. Admin Settings - Configure platform parameters
4. Compliance Tracking - Monitor worker documentation
5. Billing Dashboard - View invoices and payments
