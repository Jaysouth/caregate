/**
 * Email Service for CareGate
 * Handles all email communications
 */

const nodemailer = require('nodemailer');

class EmailService {
  constructor() {
    this.transporter = null;
    this.initTransporter();
  }

  initTransporter() {
    // Check if SMTP settings are configured
    const smtpHost = process.env.SMTP_HOST;
    const smtpPort = process.env.SMTP_PORT || 587;
    const smtpUser = process.env.SMTP_USER;
    const smtpPass = process.env.SMTP_PASS;
    const smtpFrom = process.env.SMTP_FROM || 'noreply@caregate.co.uk';

    if (smtpHost && smtpUser && smtpPass) {
      // Production SMTP configuration
      this.transporter = nodemailer.createTransport({
        host: smtpHost,
        port: smtpPort,
        secure: smtpPort === 465, // true for 465, false for other ports
        auth: {
          user: smtpUser,
          pass: smtpPass
        }
      });
      console.log('✅ Email service configured with SMTP');
    } else {
      // Development/fallback: use ethereal email (test email service)
      console.log('⚠️  SMTP not configured. Using test email service (ethereal.email)');
      console.log('   To use real emails, configure SMTP settings in .env file');
      
      // Create test account for development
      this.createTestAccount();
    }

    this.fromEmail = smtpFrom;
  }

  async createTestAccount() {
    try {
      // Note: This requires internet connection
      const testAccount = await nodemailer.createTestAccount();
      
      this.transporter = nodemailer.createTransport({
        host: 'smtp.ethereal.email',
        port: 587,
        secure: false,
        auth: {
          user: testAccount.user,
          pass: testAccount.pass
        }
      });

      console.log('📧 Test Email Account Created:');
      console.log('   User:', testAccount.user);
      console.log('   Pass:', testAccount.pass);
      console.log('   Preview emails at: https://ethereal.email');
    } catch (error) {
      console.error('❌ Failed to create test email account:', error.message);
      console.log('\n⚠️  OFFLINE MODE: Email will be displayed in console only');
      console.log('   To send real emails, configure SMTP settings in .env file');
      console.log('   See .env.example for configuration examples\n');
      
      // Set transporter to null so we handle it gracefully
      this.transporter = null;
    }
  }

  async sendAdminCredentials(email, password, name = 'Administrator') {
    const subject = 'Your CareGate Admin Account - Login Credentials';
    
    const html = `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
    .credentials { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    .footer { text-align: center; color: #6c757d; font-size: 12px; margin-top: 30px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🏥 CareGate Platform</h1>
      <p>Administrator Account Created</p>
    </div>
    
    <div class="content">
      <h2>Welcome, ${name}!</h2>
      
      <p>Your CareGate administrator account has been successfully created. You now have full access to the platform's administrative functions.</p>
      
      <div class="credentials">
        <h3>Login Credentials</h3>
        <p><strong>Email:</strong> ${email}</p>
        <p><strong>Temporary Password:</strong> <code style="background: #e9ecef; padding: 5px 10px; border-radius: 3px; font-size: 16px;">${password}</code></p>
      </div>
      
      <div class="warning">
        <strong>⚠️ Important Security Notice:</strong>
        <p>For security reasons, you <strong>must change your password</strong> on first login. You will not be able to proceed without changing your password.</p>
      </div>
      
      <h3>What's Next?</h3>
      <ol>
        <li>Visit the CareGate platform login page</li>
        <li>Enter your email and temporary password</li>
        <li>Set a new secure password when prompted</li>
        <li>Access your admin dashboard</li>
      </ol>
      
      <h3>Admin Features Available:</h3>
      <ul>
        <li>User Management (Workers & Facilities)</li>
        <li>Shift Management & Oversight</li>
        <li>Booking Management</li>
        <li>UK Invoicing (HMRC Compliant)</li>
        <li>Payroll Processing</li>
        <li>Clock System Monitoring</li>
        <li>Compliance Tracking</li>
        <li>Payment Management</li>
        <li>Reports & Analytics</li>
        <li>Platform Settings</li>
      </ul>
      
      <div style="text-align: center;">
        <a href="http://localhost:3000" class="button">Login to CareGate</a>
      </div>
      
      <div class="warning">
        <strong>🔒 Security Tips:</strong>
        <ul>
          <li>Choose a strong password (min. 8 characters)</li>
          <li>Use a combination of letters, numbers, and symbols</li>
          <li>Never share your password with anyone</li>
          <li>Keep this email secure</li>
        </ul>
      </div>
      
      <div class="footer">
        <p>This is an automated message from CareGate Platform.</p>
        <p>If you did not request this account, please contact support immediately.</p>
        <p>&copy; 2026 CareGate. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
    `;

    const text = `
CareGate Platform - Administrator Account Created

Welcome, ${name}!

Your CareGate administrator account has been successfully created.

Login Credentials:
Email: ${email}
Temporary Password: ${password}

⚠️ IMPORTANT: You must change your password on first login.

What's Next:
1. Visit the CareGate platform login page
2. Enter your email and temporary password
3. Set a new secure password when prompted
4. Access your admin dashboard

Admin Features Available:
- User Management (Workers & Facilities)
- Shift Management & Oversight
- Booking Management
- UK Invoicing (HMRC Compliant)
- Payroll Processing
- Clock System Monitoring
- Compliance Tracking
- Payment Management
- Reports & Analytics
- Platform Settings

Security Tips:
- Choose a strong password (min. 8 characters)
- Use a combination of letters, numbers, and symbols
- Never share your password with anyone
- Keep this email secure

This is an automated message from CareGate Platform.
If you did not request this account, please contact support immediately.

© 2026 CareGate. All rights reserved.
    `;

    return this.sendEmail(email, subject, text, html);
  }

  async sendEmail(to, subject, text, html) {
    if (!this.transporter) {
      console.error('❌ Email transporter not initialized');
      return { success: false, error: 'Email service not configured' };
    }

    try {
      const info = await this.transporter.sendMail({
        from: `"CareGate Platform" <${this.fromEmail}>`,
        to: to,
        subject: subject,
        text: text,
        html: html
      });

      console.log('✅ Email sent successfully!');
      console.log('   Message ID:', info.messageId);
      
      // If using ethereal.email, show preview URL
      if (info.messageId && nodemailer.getTestMessageUrl(info)) {
        const previewUrl = nodemailer.getTestMessageUrl(info);
        console.log('   Preview URL:', previewUrl);
        console.log('\n📧 COPY THIS URL to view the email in your browser:');
        console.log('   ', previewUrl);
      }

      return { 
        success: true, 
        messageId: info.messageId,
        previewUrl: nodemailer.getTestMessageUrl(info)
      };
    } catch (error) {
      console.error('❌ Failed to send email:', error.message);
      return { success: false, error: error.message };
    }
  }

  async verifyConnection() {
    if (!this.transporter) {
      return false;
    }

    try {
      await this.transporter.verify();
      console.log('✅ Email server connection verified');
      return true;
    } catch (error) {
      console.error('❌ Email server connection failed:', error.message);
      return false;
    }
  }
}

// Export singleton instance
module.exports = new EmailService();
