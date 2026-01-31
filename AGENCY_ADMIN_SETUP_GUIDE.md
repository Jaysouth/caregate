# Agency Admin Setup Guide

## Understanding the Agency Admin Role

The **Agency Admin** is the platform administrator who represents the staffing agency. This role is responsible for:

- Managing the entire CareGate platform
- Linking Care Workers with Care Facilities
- Managing platform settings and configuration
- Processing UK-compliant invoices and payroll
- Monitoring the timesheet clock system
- Overseeing platform operations

---

## ⚠️ Important: Admin is NOT on Public Registration

### Why Admin Doesn't Appear on Registration Form

Looking at the registration screen, you'll notice only two role options:
1. **Care Worker** 👨‍⚕️ - Healthcare professional
2. **Care Facility** 🏥 - Care home or hospital

**The Agency Admin role is intentionally NOT available for public registration.**

### Security Reasons

**Admin has elevated privileges:**
- Full platform management access
- Access to sensitive data (payments, personal info)
- Control over all workers and facilities
- Platform configuration capabilities
- Financial operations (invoicing, payroll)

**Public registration would create security risks:**
- Anyone could create an admin account
- Unauthorized access to sensitive data
- Potential for platform abuse
- Security breach possibilities

### The Correct Approach

✅ **Admin accounts should be created by:**
- System administrators
- Via WordPress admin panel
- Through secure creation scripts
- By authorized personnel only

❌ **Admin accounts should NOT be:**
- Available on public registration form
- Created by anyone visiting the site
- Self-service registration

---

## 🎯 Three User Types in CareGate

### 1. Care Worker 👨‍⚕️

**Purpose:** Healthcare professionals seeking shifts
**Registration:** Public form available
**Access Level:** Limited to worker features

**Capabilities:**
- Apply for shifts
- Clock in/out at facilities
- Submit timesheets
- Upload compliance documents
- View matched shifts
- Receive payments

### 2. Care Facility 🏥

**Purpose:** Care homes and hospitals needing staff
**Registration:** Public form available
**Access Level:** Limited to facility features

**Capabilities:**
- Create and manage shifts
- View applications
- Confirm bookings
- Approve timesheets
- Make payments
- Receive invoices

### 3. Agency Admin 👔

**Purpose:** Platform administrator (the agency)
**Registration:** System-only (secure creation)
**Access Level:** Full platform management

**Capabilities:**
- Manage platform settings
- UK Invoice management
- Payroll processing
- Timesheet clock monitoring
- User management (workers & facilities)
- Reports and analytics
- Platform configuration
- Link workers with facilities

---

## 📋 How to Create Agency Admin Users

### Method 1: WordPress Admin Panel (Recommended)

**For WordPress Plugin Users:**

1. **Login to WordPress Admin**
   ```
   URL: https://your-site.com/wp-admin
   Use your WordPress administrator credentials
   ```

2. **Navigate to Users**
   ```
   Click: Users → Add New
   ```

3. **Fill in User Details**
   ```
   Username: admin_caregate (or your choice)
   Email: admin@caregate.co.uk
   Password: CareGare2026!Admin (or create strong password)
   Send User Notification: (optional)
   ```

4. **Select Role**
   ```
   Role: CareGate Frontend Admin ← This is crucial!
   ```

5. **Add User**
   ```
   Click: Add New User button
   ```

6. **Verify**
   ```
   User should appear in Users → All Users
   Role should show: CareGate Frontend Admin
   ```

### Method 2: Update Existing WordPress User

1. **Go to Users**
   ```
   WordPress Admin → Users → All Users
   ```

2. **Edit User**
   ```
   Find the user you want to make admin
   Click: Edit
   ```

3. **Change Role**
   ```
   Scroll to: Role dropdown
   Select: CareGate Frontend Admin
   ```

4. **Update**
   ```
   Click: Update User
   ```

### Method 3: Node.js Creation Script

**For Node.js Standalone Version:**

1. **Start the Server**
   ```bash
   npm start
   ```

2. **Run Admin Creation Script**
   ```bash
   node scripts/create-admin-manual.js "CareGare2026!Admin"
   ```

3. **Output**
   ```
   ✅ Admin user created successfully!
   Email: admin@caregate.co.uk
   Password: CareGare2026!Admin
   Role: admin
   ```

4. **Login**
   ```
   URL: http://localhost:3000
   Email: admin@caregate.co.uk
   Password: CareGare2026!Admin
   ```

---

## 🎨 Agency Admin Dashboard

### What Agency Admin Sees

When an Agency Admin logs in to the frontend dashboard, they have access to:

### 1. Platform Settings

**Manage Core Configuration:**
- Agency details (name, address, contact info)
- Platform fees and pricing multipliers
- Geofence radius for auto clock-in/out
- Maximum matching distance
- Urgency and skill level premiums

**Integration Settings:**
- Twilio SMS configuration
- Paystack payment gateway
- reCAPTCHA keys
- 2FA enable/disable

### 2. UK Invoice Management

**HMRC-Compliant Invoicing:**
- Create invoices for facilities
- Add line items (description, quantity, rate)
- Automatic VAT calculation (20%)
- Invoice numbering (INV-YYYY-NNNN)
- Send invoices to facilities
- Track invoice status (draft, sent, paid)
- Mark invoices as paid
- View payment history

### 3. Payroll Processing

**UK Tax-Compliant Payroll:**
- Select worker for payment
- View worker details and photo
- Enter payment information:
  - Basic pay (hourly rate)
  - Hours worked
  - Overtime pay
  - Shift differentials
  - Bonuses

**Automatic Calculations:**
- PAYE income tax
- National Insurance (Class 1)
- Pension contributions (5%)
- Health insurance deductions
- Agency fees
- Net pay calculation

**Actions:**
- Generate salary slips
- Send via email with SMS notification
- Process payment via Paystack

### 4. Timesheet Clock System

**Live Monitoring:**
- View currently clocked-in workers
- See completed shifts today
- Track total hours worked
- Auto-refresh every 30 seconds

**Manual Clock Entry:**
- Select worker and facility
- Enter clock in/out times
- Add break time
- Include notes
- Update worker profiles

**Clock Records:**
- Filter by status (in progress/completed)
- View clock method (auto/manual)
- See GPS location data
- Calculate hours worked

### 5. User Management

**View All Users:**
- List all workers
- List all facilities
- Search and filter
- View user details
- Monitor activity

**User Statistics:**
- Total users count
- Workers vs facilities breakdown
- Active users
- Registration trends

### 6. Reports & Analytics

**Platform Statistics:**
- Total shifts created
- Total bookings made
- Platform revenue
- Worker payment totals
- Facility payment totals

**Financial Reports:**
- Invoice summaries
- Payroll summaries
- Payment transactions
- Revenue by period

**Activity Reports:**
- User registrations
- Shift applications
- Booking confirmations
- Clock in/out records

---

## 🔒 Security Considerations

### Access Control

**Agency Admin CANNOT:**
- ❌ Access WordPress wp-admin dashboard
- ❌ Edit WordPress posts or pages
- ❌ Manage WordPress plugins
- ❌ Change WordPress settings
- ❌ See WordPress admin bar

**Agency Admin CAN:**
- ✅ Access CareGate frontend dashboard
- ✅ Manage platform settings
- ✅ Process invoices and payroll
- ✅ Monitor clock system
- ✅ View all users and data
- ✅ Generate reports

### Best Practices

**1. Strong Password**
```
Use minimum 12 characters
Include uppercase, lowercase, numbers, symbols
Example: CareGate2026!Admin#Secure
```

**2. Unique Email**
```
Use official agency email
Example: admin@caregate.co.uk
Not personal email
```

**3. Limited Admin Accounts**
```
Create only necessary admin accounts
One primary admin recommended
Additional admins for backup only
```

**4. Regular Audits**
```
Monitor admin activities
Review actions taken
Check for unusual behavior
Regular password changes
```

**5. Enable Two-Factor Authentication**
```
If 2FA is available, enable it
Adds extra security layer
Protects against unauthorized access
```

---

## 📋 Complete Setup Workflow

### For WordPress Plugin

**Step 1: Install Plugin**
```
1. Download: caregate-wordpress-plugin.zip
2. WordPress Admin → Plugins → Add New
3. Upload Plugin → Choose File
4. Install Now → Activate
5. Plugin activated ✅
```

**Step 2: Verify Role Created**
```
1. Users → Add New
2. Check "Role" dropdown
3. Should see: "CareGate Frontend Admin" ✅
```

**Step 3: Create Agency Admin User**
```
1. Users → Add New
2. Username: admin_caregate
3. Email: admin@caregate.co.uk
4. Password: CareGare2026!Admin
5. Role: CareGate Frontend Admin
6. Add New User ✅
```

**Step 4: Login as Agency Admin**
```
1. Open: https://your-site.com/
   (the page with [caregate_app] shortcode)
2. Email: admin@caregate.co.uk
3. Password: CareGare2026!Admin
4. Login → See Admin Dashboard ✅
```

**Step 5: Configure Platform**
```
1. Admin Dashboard → Settings Tab
2. Fill in:
   - Agency name and details
   - Platform fees (e.g., 15%)
   - Maximum distance (e.g., 50 km)
   - Pricing multipliers
3. Configure integrations:
   - Twilio SMS credentials
   - Paystack API keys
   - reCAPTCHA keys
4. Save Settings ✅
```

**Step 6: Platform Ready**
```
✅ Workers can now register
✅ Facilities can now register
✅ Admin can manage platform
✅ Platform is operational!
```

---

## ✅ Verification Checklist

### After Creating Agency Admin

**Test Role Creation:**
- [ ] Plugin activated without errors
- [ ] "CareGate Frontend Admin" appears in WordPress role dropdown
- [ ] Can create user with this role

**Test wp-admin Blocking:**
- [ ] Login as frontend admin user
- [ ] Try to access: your-site.com/wp-admin
- [ ] Should redirect to home page (not wp-admin)
- [ ] Admin bar should not be visible

**Test Frontend Access:**
- [ ] Login at: your-site.com (frontend)
- [ ] Should see admin dashboard
- [ ] Should have tabs: Settings, Invoices, Payroll, Clock
- [ ] Can access all admin features

**Test Capabilities:**
- [ ] Can view platform settings
- [ ] Can create UK invoices
- [ ] Can process payroll
- [ ] Can monitor timesheet clock
- [ ] Can view all users
- [ ] Can generate reports

---

## 🎯 Common Questions

### Q: Why can't I register as admin on the registration form?

**A:** Admin registration is intentionally disabled for security. Admin accounts have full platform access and should only be created by authorized personnel through WordPress admin panel or secure scripts.

### Q: Can I add admin registration to the public form?

**A:** Not recommended. This would create a serious security vulnerability. Admin accounts should always be created through controlled, secure methods.

### Q: How many admin accounts should I create?

**A:** Start with one primary admin account. Only create additional admin accounts if absolutely necessary for backup or multiple authorized staff.

### Q: Can workers or facilities become admins?

**A:** Yes, but only by changing their role through WordPress admin panel. They cannot self-promote to admin. A WordPress administrator must update their role.

### Q: What if I forget the admin password?

**A:** You can reset it through WordPress admin panel (Users → Edit User → Generate Password) or use the "Forgot Password" feature on the login page.

### Q: Can admin create workers and facilities?

**A:** Admin can view all users but workers and facilities should register themselves through the public registration form. This ensures proper data collection and compliance.

---

## 📚 Related Documentation

### Complete Guide Suite

**Admin Setup:**
- `AGENCY_ADMIN_SETUP_GUIDE.md` (THIS FILE)
- `FRONTEND_ADMIN_ROLE_IMPLEMENTATION.md` - Technical implementation
- `WORDPRESS_ADMIN_SETUP.md` - WordPress user creation
- `ADMIN_SETUP.md` - Configuration guide

**Installation:**
- `PLUGIN_INSTALLATION.md` (13,000+ words)
- `PLUGIN_DOWNLOAD.md` - Download instructions
- `DATABASE_FIX_SUMMARY.md` - Common issues

**Features:**
- `AUTO_CLOCK_DOCUMENTATION.md` - GPS auto-clock system
- `PLUGIN_SHORTCODES.md` (17,000+ words)
- `SHORTCODES_QUICK_REFERENCE.md` (5,000+ words)

**Total:** 110,000+ words of documentation!

---

## ✅ Summary

### Key Takeaways

**1. Three User Types**
- Care Worker (public registration)
- Care Facility (public registration)
- Agency Admin (secure creation only)

**2. Admin Creation**
- Via WordPress admin panel (recommended)
- Via Node.js creation script
- Never via public registration form

**3. Admin Capabilities**
- Full platform management
- UK invoicing
- Payroll processing
- Timesheet clock monitoring
- User management
- Reports and analytics

**4. Security**
- No wp-admin access for frontend admin
- Admin bar hidden
- Controlled creation only
- Elevated privileges protected

**5. Setup Process**
- Install plugin
- Create admin via WordPress
- Login at frontend
- Configure platform
- Platform operational

---

## 🎉 You're All Set!

The Agency Admin role exists and is properly configured. Follow the instructions above to create admin users securely through WordPress admin panel.

**For Support:**
- Review documentation files
- Check troubleshooting guides
- Ensure plugin is properly activated
- Verify role appears in WordPress

**Happy Platform Managing!** 🚀
