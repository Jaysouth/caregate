# CareGate - On-Demand Care Staffing Platform

CareGate is a mobile-first, on-demand staffing platform connecting pre-vetted carers and nurses with UK care facilities needing urgent or temporary cover. Facilities post shifts and get real-time matches based on skills, location, and availability, while workers access flexible, fairly paid shifts. Built-in compliance, dynamic pricing, automated timesheets, and billing streamline care staffing efficiently.

## Features

### Core Functionality
- **User Management**: Separate roles for healthcare workers (carers, nurses) and facilities (care homes, hospitals)
- **Shift Posting**: Facilities can create and manage shift postings with detailed requirements
- **Intelligent Matching**: Real-time matching algorithm based on:
  - Skills and qualifications
  - Geographic location and proximity
  - Worker availability
  - Match scoring (0-100) to show best fits

### Dynamic Pricing
- Base rate configuration per shift
- Automatic pricing adjustments based on:
  - Urgency (shifts within 24/48 hours get premium rates)
  - Skill level requirements (basic, advanced, expert)
  - Real-time supply/demand

### Compliance Tracking
- Built-in UK healthcare compliance management
- Required documents tracking:
  - DBS (Disclosure and Barring Service) checks
  - Right to Work verification
  - Professional Registration (NMC for nurses)
  - Health Clearance
  - Mandatory Training certificates
  - Liability Insurance
- Automated expiry tracking and alerts
- Compliance dashboard for facilities

### Automated Timesheets
- Digital timesheet submission by workers
- Approval workflow for facilities
- Automatic pay calculation based on dynamic rates
- Hours worked and break time tracking

### Billing System
- Automated invoice generation from approved timesheets
- Platform fee calculation (15% standard)
- Payment tracking and due date management
- Billing summaries and analytics

## Technology Stack

- **Backend**: Node.js with Express.js
- **Database**: In-memory storage (demo) - easily replaceable with PostgreSQL/MongoDB
- **Authentication**: JWT-based authentication
- **API**: RESTful API architecture

## Installation

```bash
# Clone the repository
git clone https://github.com/Jaysouth/caregate.git
cd caregate

# Install dependencies
npm install

# Start the server
npm start

# For development with auto-reload
npm run dev
```

## API Documentation

### Authentication

#### Register
```
POST /api/auth/register
Body: {
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe",
  "role": "worker", // or "facility"
  "skills": ["Nursing", "Elderly Care"], // for workers
  "location": { "lat": 51.5074, "lng": -0.1278 },
  "facilityType": "Care Home" // for facilities
}
```

#### Login
```
POST /api/auth/login
Body: {
  "email": "user@example.com",
  "password": "password123"
}
```

### Shifts

#### Create Shift (Facility only)
```
POST /api/shifts
Headers: { "Authorization": "Bearer <token>" }
Body: {
  "title": "Night Shift Nurse",
  "description": "Elderly care facility needs qualified nurse",
  "startTime": "2026-02-01T20:00:00Z",
  "endTime": "2026-02-02T08:00:00Z",
  "requiredSkills": ["Nursing", "Elderly Care"],
  "location": { "lat": 51.5074, "lng": -0.1278 },
  "baseRate": 20.00,
  "skillLevel": "advanced"
}
```

#### Get Matched Shifts (Worker only)
```
GET /api/shifts/matches
Headers: { "Authorization": "Bearer <token>" }
Returns: Shifts ranked by match score with distance information
```

#### List All Shifts
```
GET /api/shifts?status=open&startDate=2026-02-01
Headers: { "Authorization": "Bearer <token>" }
```

### Bookings

#### Apply for Shift (Worker only)
```
POST /api/bookings
Headers: { "Authorization": "Bearer <token>" }
Body: {
  "shiftId": "shift-uuid"
}
```

#### Confirm Booking (Facility only)
```
PUT /api/bookings/:id/confirm
Headers: { "Authorization": "Bearer <token>" }
```

#### Complete Booking
```
PUT /api/bookings/:id/complete
Headers: { "Authorization": "Bearer <token>" }
```

### Timesheets

#### Submit Timesheet
```
POST /api/timesheets
Headers: { "Authorization": "Bearer <token>" }
Body: {
  "bookingId": "booking-uuid",
  "hoursWorked": 8,
  "breakTime": 0.5,
  "notes": "Shift completed successfully"
}
```

#### Approve Timesheet (Facility only)
```
PUT /api/timesheets/:id/approve
Headers: { "Authorization": "Bearer <token>" }
```

### Billing

#### Generate Invoice (Facility only)
```
POST /api/billing/generate
Headers: { "Authorization": "Bearer <token>" }
Body: {
  "timesheetIds": ["ts-uuid-1", "ts-uuid-2"],
  "billingPeriod": "2026-02"
}
```

#### Get Billing Summary
```
GET /api/billing/summary/stats
Headers: { "Authorization": "Bearer <token>" }
```

### Compliance

#### Add Compliance Record
```
POST /api/compliance
Headers: { "Authorization": "Bearer <token>" }
Body: {
  "workerId": "worker-uuid",
  "documentType": "DBS_CHECK",
  "documentNumber": "DBS123456",
  "issueDate": "2025-01-01",
  "expiryDate": "2027-01-01",
  "status": "valid"
}
```

#### Get Worker Compliance Status
```
GET /api/compliance/worker/:workerId
Headers: { "Authorization": "Bearer <token>" }
```

#### Get Required Documents
```
GET /api/compliance/required-documents
Headers: { "Authorization": "Bearer <token>" }
```

## User Roles

### Workers (Carers/Nurses)
- Create profile with skills and qualifications
- Browse available shifts
- View matched shifts based on skills, location, and availability
- Apply for shifts
- Submit timesheets
- Track earnings
- Manage compliance documents

### Facilities (Care Homes/Hospitals)
- Create shift postings with requirements
- Review worker applications
- Confirm bookings
- Approve timesheets
- Generate and manage invoices
- View worker compliance status
- Track spending

## Compliance Requirements

All healthcare workers must maintain valid documentation:

1. **DBS Check** - Disclosure and Barring Service background check
2. **Right to Work** - UK work authorization
3. **Professional Registration** - NMC registration for nurses, relevant registration for other roles
4. **Health Clearance** - Occupational health clearance
5. **Mandatory Training** - Required training certificates (Manual Handling, Safeguarding, etc.)
6. **Liability Insurance** - Professional indemnity insurance

The system tracks expiry dates and provides compliance dashboards to ensure all workers are properly vetted and current.

## Dynamic Pricing Algorithm

Shift rates are calculated dynamically:

- **Base Rate**: Set by facility for each shift
- **Urgency Premium**: 
  - Within 24 hours: +30%
  - Within 48 hours: +15%
- **Skill Level Premium**:
  - Basic: 0%
  - Advanced: +20%
  - Expert: +40%

Example: £20/hour base rate + urgent (24h) + advanced skill = £31.20/hour

## Security

- JWT-based authentication
- Password hashing with bcrypt
- Role-based access control
- Sensitive data protection

## Future Enhancements

- Real-time notifications (push notifications)
- Mobile applications (iOS/Android)
- Payment gateway integration (Stripe/PayPal)
- Review and rating system
- Geolocation services
- Advanced analytics and reporting
- Calendar integration
- Background check automation
- Video interviews
- Training course marketplace

## License

ISC

## Support

For issues and questions, please create an issue in the GitHub repository.
