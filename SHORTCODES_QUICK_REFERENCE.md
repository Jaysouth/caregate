# CareGate Shortcodes - Quick Reference Card

## 🎯 Most Common Shortcodes

### 1. Complete Platform (All-in-One)
```
[caregate_app]
```
**Use this for:** Full platform with login, register, and dashboards  
**Best for:** Single-page application setup

---

### 2. Registration Form (Multi-Step)
```
[caregate_register]
```
**Use this for:** Dedicated registration page  
**Features:** 3-step process, role selection, UK fields

**With pre-selected role:**
```
[caregate_register role="worker"]
[caregate_register role="facility"]
```

---

### 3. Login Form
```
[caregate_login]
```
**Use this for:** Dedicated login page

**With redirect:**
```
[caregate_login redirect="/dashboard/"]
```

---

### 4. Worker Dashboard
```
[caregate_worker_dashboard]
```
**Use this for:** Care worker home page  
**Access:** Logged-in workers only

---

### 5. Facility Dashboard
```
[caregate_facility_dashboard]
```
**Use this for:** Care facility home page  
**Access:** Logged-in facilities only

---

### 6. Browse Shifts
```
[caregate_shifts]
```
**Use this for:** Public shift listings

**With filters:**
```
[caregate_shifts limit="10"]
[caregate_shifts limit="5" skill="Nursing"]
```

---

### 7. Matched Shifts (For Workers)
```
[caregate_matched_shifts]
```
**Use this for:** Personalized shift matches  
**Access:** Logged-in workers only

---

### 8. My Bookings
```
[caregate_my_bookings]
```
**Use this for:** User's booking history

**Filter by status:**
```
[caregate_my_bookings status="confirmed"]
```

---

### 9. Compliance Tracker
```
[caregate_compliance]
```
**Use this for:** Document tracking for workers  
**Access:** Logged-in workers only

---

### 10. Create Shift Form
```
[caregate_create_shift]
```
**Use this for:** Facility to post new shifts  
**Access:** Logged-in facilities only

---

### 11. Clock In/Out Widget
```
[caregate_clock_widget]
```
**Use this for:** Quick clock in/out button  
**Access:** Logged-in workers only

---

### 12. Statistics Dashboard
```
[caregate_stats]
```
**Use this for:** Platform statistics

**Role-specific:**
```
[caregate_stats user_type="worker"]
[caregate_stats user_type="facility"]
```

---

### 13. Search Shifts (Advanced)
```
[caregate_search_shifts]
```
**Use this for:** Advanced shift search with filters

---

### 14. User Profile
```
[caregate_profile]
```
**Use this for:** View/edit profile page  
**Access:** Logged-in users

---

### 15. Invoices
```
[caregate_invoices]
```
**Use this for:** Invoice management

**With filters:**
```
[caregate_invoices type="received" status="unpaid"]
```

---

### 16. Timesheet Submission
```
[caregate_timesheet]
```
**Use this for:** Workers submit hours  
**Access:** Logged-in workers only

---

## 📋 Quick Setup Guide

### Option 1: Single Page (Recommended)
1. Create page: "CareGate"
2. Add: `[caregate_app]`
3. Done! ✅

### Option 2: Multiple Pages
1. **Register:** `[caregate_register]`
2. **Login:** `[caregate_login]`
3. **Dashboard:** `[caregate_worker_dashboard]` or `[caregate_facility_dashboard]`
4. **Shifts:** `[caregate_shifts]`

### Option 3: Public Site
1. **Home:** Marketing content
2. **Browse Shifts:** `[caregate_search_shifts]`
3. **Register:** `[caregate_register]`
4. **Login:** `[caregate_login redirect="/app/"]`
5. **App:** `[caregate_app]`

---

## ⚙️ Installation Steps

1. **Copy plugin:**
   ```bash
   cp -r caregate-plugin /path/to/wordpress/wp-content/plugins/
   ```

2. **Activate:**
   - WordPress Admin → Plugins → Activate "CareGate"

3. **Configure:**
   - Go to: CareGate → Settings
   - Add agency details, enable 2FA, configure reCAPTCHA

4. **Add shortcode:**
   - Create new page
   - Add `[caregate_app]`
   - Publish

5. **Test:**
   - Visit page
   - Register as worker or facility
   - Complete onboarding

---

## 🎨 Customization

### Override Styles
Create `caregate-custom.css` in your theme:
```css
.caregate-app {
    /* Your styles */
}
```

### Modify Templates
Copy template files from plugin to your theme:
```
wp-content/themes/your-theme/caregate/
```

---

## 🔐 User Roles

**Care Worker** (`caregate_worker`)
- View matched shifts
- Apply for shifts
- Clock in/out
- Submit timesheets

**Care Facility** (`caregate_facility`)
- Create shifts
- Manage applications
- Approve timesheets
- View invoices

---

## 📱 Mobile Ready

All shortcodes are mobile-first and responsive:
- ✅ iOS and Android optimized
- ✅ Touch-friendly
- ✅ PWA ready

---

## 🆘 Troubleshooting

**Shortcode shows as text?**
→ Activate the plugin

**404 on API calls?**
→ Settings → Permalinks → Save

**Styles not loading?**
→ Clear cache

**Tables not created?**
→ Deactivate/Reactivate plugin

---

## 📊 Admin Menu

After activation:

**CareGate →**
- Dashboard
- UK Invoicing
- Payroll
- Timesheet Clock
- Settings

---

## 🚀 Go Live Checklist

- [ ] Plugin activated
- [ ] Settings configured
- [ ] HTTPS enabled
- [ ] reCAPTCHA set up
- [ ] SMS provider configured (optional)
- [ ] Agency details added
- [ ] Page created with shortcode
- [ ] Test registration
- [ ] Test shift creation
- [ ] Test booking workflow

---

## 📞 Support

**Documentation:** `PLUGIN_SHORTCODES.md`  
**WordPress Guide:** `WORDPRESS_CONVERSION.md`  
**Security:** `SECURITY.md`  
**Deployment:** `DEPLOYMENT.md`

---

**Quick Start:** Add `[caregate_app]` to any page → Done! 🎉
