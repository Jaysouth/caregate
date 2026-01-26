# CareGate WordPress Plugin - Conversion Guide

## Overview

The CareGate platform has been successfully converted from a standalone Node.js/Express application to a WordPress plugin. This guide explains the conversion and how to use the plugin.

## What Changed

### Architecture Conversion

**Before (Node.js/Express)**
- Standalone Express.js server
- In-memory database
- Custom routing
- Separate frontend

**After (WordPress Plugin)**
- WordPress plugin architecture
- WordPress database tables
- WordPress REST API
- Integrated frontend via shortcode

### Key Components Mapping

| Original | WordPress Equivalent |
|----------|---------------------|
| `server/routes/*.js` | `includes/class-caregate-*.php` |
| `server/models/database.js` | WordPress `$wpdb` + custom tables |
| `server/middleware/auth.js` | WordPress `current_user_can()` + REST API permissions |
| `public/index.html` | `[caregate_app]` shortcode |
| `public/css/styles.css` | `public/css/caregate-public.css` |
| `public/js/app.js` | `public/js/caregate-public.js` |

## Installation

### Quick Install

1. Copy the `caregate-plugin` folder to `/wp-content/plugins/`
2. Activate via WordPress admin
3. Add `[caregate_app]` shortcode to a page

### Detailed Steps

```bash
# 1. Copy plugin to WordPress
cp -r caregate-plugin /path/to/wordpress/wp-content/plugins/

# 2. Set permissions
chmod -R 755 /path/to/wordpress/wp-content/plugins/caregate-plugin

# 3. Activate in WordPress admin or via WP-CLI
wp plugin activate caregate
```

## Database Setup

The plugin automatically creates these tables on activation:

- `wp_caregate_shifts`
- `wp_caregate_bookings`
- `wp_caregate_timesheets`
- `wp_caregate_invoices`
- `wp_caregate_invoice_timesheets`
- `wp_caregate_compliance`
- `wp_caregate_user_meta`

## Configuration

### Admin Settings

Navigate to **WordPress Admin → CareGate → Settings**

Configure:
- Platform fee percentage (default: 0.15)
- Maximum matching distance (default: 50 km)
- Urgency pricing multipliers
- Skill level pricing multipliers

### User Roles

Two custom roles are created:

**caregate_worker**
- Can view and apply for shifts
- Can submit timesheets
- Can manage compliance documents

**caregate_facility**
- Can create and manage shifts
- Can approve bookings
- Can generate invoices

### Assigning Roles

**Via Admin**:
1. Go to Users → All Users
2. Edit user
3. Change role to "Care Worker" or "Care Facility"

**Programmatically**:
```php
$user_id = get_current_user_id();
$user = new WP_User($user_id);
$user->set_role('caregate_worker'); // or 'caregate_facility'
```

## Using the Plugin

### Display Interface

Add the shortcode to any page:

```
[caregate_app]
```

This renders the complete CareGate application interface.

### REST API Integration

All endpoints are available at: `/wp-json/caregate/v1/`

Example API call:
```javascript
fetch('/wp-json/caregate/v1/shifts', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## Feature Comparison

All features from the original Node.js application are preserved:

✅ **Intelligent Matching Algorithm**
- Skills-based matching (40% weight)
- Location proximity (30% weight)
- Availability checking (30% weight)

✅ **Dynamic Pricing**
- Urgency premiums (24h: +30%, 48h: +15%)
- Skill level premiums (Advanced: +20%, Expert: +40%)

✅ **UK Compliance Tracking**
- DBS checks
- NMC registration
- Right to Work
- Health clearance
- Mandatory training
- Liability insurance

✅ **Automated Workflows**
- Digital timesheets
- Approval processes
- Invoice generation
- Platform fee calculation (15%)

✅ **Mobile-First Design**
- Responsive layout
- Touch-friendly interfaces
- Works on all devices

## Development

### File Structure

```
caregate-plugin/
├── caregate.php              # Main plugin file
├── includes/                 # Core functionality
│   ├── class-caregate.php
│   ├── class-caregate-api.php
│   ├── class-caregate-auth.php
│   ├── class-caregate-shifts.php
│   ├── class-caregate-bookings.php
│   ├── class-caregate-timesheets.php
│   ├── class-caregate-billing.php
│   └── class-caregate-compliance.php
├── admin/                    # Admin interface
│   ├── class-caregate-admin.php
│   ├── css/
│   ├── js/
│   └── partials/
├── public/                   # Frontend
│   ├── class-caregate-public.php
│   ├── css/caregate-public.css
│   ├── js/caregate-public.js
│   └── partials/
├── README.md
└── readme.txt               # WordPress.org format
```

### Extending the Plugin

**Add custom REST endpoint**:
```php
add_action('rest_api_init', function() {
    register_rest_route('caregate/v1', '/custom', array(
        'methods' => 'GET',
        'callback' => 'my_custom_function',
        'permission_callback' => '__return_true'
    ));
});
```

**Add custom user meta**:
```php
// In includes/class-caregate-auth.php
CareGate_Auth::update_user_meta($user_id, 'custom_field', $value);
```

## Migration from Node.js Version

If you're migrating from the Node.js version:

1. **Data Migration**: Export data from Node.js in-memory storage
2. **Import to WordPress**: Use WP-CLI or custom import script
3. **Update API Calls**: Change endpoint URLs from `/api/` to `/wp-json/caregate/v1/`
4. **Authentication**: Update from JWT to WordPress nonces/cookies

## Troubleshooting

### REST API Not Working

**Check permalink structure**:
1. Go to Settings → Permalinks
2. Choose any option except "Plain"
3. Save changes

**Check .htaccess**:
Ensure WordPress .htaccess rules are present.

### Database Tables Not Created

Deactivate and reactivate the plugin:
```bash
wp plugin deactivate caregate
wp plugin activate caregate
```

### Shortcode Not Displaying

1. Check if page is published
2. Verify plugin is activated
3. Check browser console for JavaScript errors

## Support

For issues or questions:
- GitHub: https://github.com/Jaysouth/caregate/issues
- Documentation: See README.md in plugin folder

## Comparison: Node.js vs WordPress

| Feature | Node.js Version | WordPress Plugin |
|---------|----------------|------------------|
| Installation | npm install | Plugin upload/activate |
| Database | In-memory | MySQL (persistent) |
| Authentication | JWT tokens | WordPress sessions |
| API | Custom Express | WordPress REST API |
| Frontend | Standalone SPA | Shortcode integration |
| Admin | None | WordPress admin pages |
| User Management | Custom | WordPress users |
| Deployment | Node.js server | Any WordPress host |
| Scalability | Custom setup | WordPress ecosystem |

## Benefits of WordPress Version

✅ **Easier deployment** - Works on any WordPress hosting
✅ **Integrated user management** - Uses WordPress users
✅ **Admin interface** - Built-in WordPress admin
✅ **Persistent data** - MySQL database storage
✅ **Plugin ecosystem** - Compatible with other WordPress plugins
✅ **Security** - Leverages WordPress security features
✅ **Updates** - Standard WordPress update mechanism

## Next Steps

1. ✅ Install and activate plugin
2. ✅ Configure settings
3. ✅ Add shortcode to a page
4. ✅ Test user registration
5. ✅ Create test shifts
6. ✅ Test booking workflow
7. ✅ Configure compliance tracking
8. ✅ Test timesheet submission
9. ✅ Test invoice generation

## License

GPL v2 or later - Same as WordPress
