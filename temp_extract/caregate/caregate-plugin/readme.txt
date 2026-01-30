=== CareGate - On-Demand Care Staffing ===
Contributors: caregate
Tags: healthcare, staffing, nurses, carers, booking
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mobile-first on-demand staffing platform connecting pre-vetted carers and nurses with UK care facilities.

== Description ==

CareGate is a comprehensive staffing solution for healthcare facilities that need urgent or temporary cover. The plugin provides intelligent shift matching, dynamic pricing, compliance tracking, and automated billing workflows.

= Key Features =

* **Intelligent Matching**: Matches workers with shifts based on skills, location, and availability
* **Dynamic Pricing**: Automatic rate adjustments for urgent shifts and skill levels
* **UK Compliance**: Built-in tracking for DBS checks, NMC registration, and required documents
* **Automated Workflows**: Digital timesheets and invoice generation
* **Mobile-First**: Responsive design that works on all devices

= User Roles =

* **Care Workers**: Browse shifts, apply for bookings, submit timesheets
* **Care Facilities**: Create shifts, approve bookings, manage billing

= REST API =

Full REST API available for integration with mobile apps and external systems.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/caregate-plugin`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings under CareGate menu
4. Add `[caregate_app]` shortcode to any page

== Frequently Asked Questions ==

= How do I display the CareGate interface? =

Use the shortcode `[caregate_app]` on any page or post.

= Can I customize the pricing multipliers? =

Yes! Go to CareGate → Settings to configure all pricing parameters.

= What user roles are created? =

The plugin creates two roles: Care Worker and Care Facility, each with specific capabilities.

= Is this GDPR compliant? =

The plugin stores user data in WordPress tables. You are responsible for ensuring GDPR compliance based on your usage.

== Screenshots ==

1. Worker Dashboard with matched shifts
2. Facility Dashboard for creating shifts
3. Admin Settings page
4. Compliance tracking interface
5. Billing and invoicing

== Changelog ==

= 1.0.0 =
* Initial release
* Complete WordPress plugin conversion
* REST API implementation
* Admin dashboard
* Mobile-first frontend
* Intelligent matching algorithm
* Dynamic pricing engine
* Compliance tracking
* Automated billing

== Upgrade Notice ==

= 1.0.0 =
Initial release of CareGate WordPress plugin.
