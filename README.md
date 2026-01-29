# CareGate - On-Demand Care Staffing Platform

Marketplace connecting Admin, Care Homes, Hospitals, Caregivers, Physicians and Nurses.

## 🚀 Quick Start - WordPress Plugin

This repository contains a complete WordPress plugin for the CareGate on-demand staffing platform.

### Installation

1. **Download the plugin**: `caregate-wordpress-plugin.zip`
2. **Upload to WordPress**: Go to Plugins → Add New → Upload Plugin
3. **Activate the plugin**: The plugin will automatically create database tables and an admin user

### 🔐 Default Admin Login

After activation, log in with these credentials:

- **Email**: `admin@caregate.co.uk`
- **Password**: `CareGate2026!Admin`
- **⚠️ IMPORTANT**: You must change the password on first login

### 📚 Documentation

- **`ADMIN_LOGIN.md`** - Complete admin credentials and dashboard guide (inside plugin ZIP)
- **`PLUGIN_INSTALLATION.md`** - Detailed installation and configuration instructions
- **`ADMIN_SETUP.md`** - Admin dashboard setup and features guide

### Features

- **User Management** - Separate roles for workers and facilities
- **Shift Matching** - Intelligent matching based on skills and location
- **Dynamic Pricing** - Automatic rate adjustments for urgency and skill level
- **UK Compliance** - Built-in tracking for DBS, NMC, and required documents
- **Automated Billing** - Digital timesheets and HMRC-compliant invoicing
- **Payroll System** - UK tax calculations and salary processing
- **Clock In/Out** - Real-time worker tracking system
- **Payments** - Integrated Paystack payment gateway

### Building the Plugin

To rebuild the plugin ZIP with the latest changes:

```bash
npm install
npm run build:plugin
```

This will create `caregate-wordpress-plugin.zip` with all necessary files including admin login details.

## Support

For technical support or questions:
- **Email**: support@caregate.co.uk
- **Documentation**: See included markdown files
- **GitHub**: https://github.com/Jaysouth/caregate

