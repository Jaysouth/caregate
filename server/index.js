const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');

// Import routes
const authRoutes = require('./routes/auth');
const shiftRoutes = require('./routes/shifts');
const userRoutes = require('./routes/users');
const facilityRoutes = require('./routes/facilities');
const bookingRoutes = require('./routes/bookings');
const timesheetRoutes = require('./routes/timesheets');
const billingRoutes = require('./routes/billing');
const complianceRoutes = require('./routes/compliance');

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/shifts', shiftRoutes);
app.use('/api/users', userRoutes);
app.use('/api/facilities', facilityRoutes);
app.use('/api/bookings', bookingRoutes);
app.use('/api/timesheets', timesheetRoutes);
app.use('/api/billing', billingRoutes);
app.use('/api/compliance', complianceRoutes);

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', message: 'CareGate API is running' });
});

// Root endpoint with API information
app.get('/', (req, res) => {
  res.json({
    name: 'CareGate API',
    version: '1.0.0',
    description: 'Mobile-first on-demand staffing platform connecting pre-vetted carers and nurses with UK care facilities',
    endpoints: {
      auth: '/api/auth',
      shifts: '/api/shifts',
      users: '/api/users',
      facilities: '/api/facilities',
      bookings: '/api/bookings',
      timesheets: '/api/timesheets',
      billing: '/api/billing',
      compliance: '/api/compliance'
    }
  });
});

// Only start server if not in test environment
if (process.env.NODE_ENV !== 'test') {
  app.listen(PORT, () => {
    console.log(`CareGate server running on port ${PORT}`);
  });
}

module.exports = app;
