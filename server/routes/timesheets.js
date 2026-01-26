const express = require('express');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');

const router = express.Router();

// Create timesheet for a completed shift
router.post('/', authMiddleware, (req, res) => {
  try {
    const { bookingId, hoursWorked, breakTime, notes } = req.body;

    if (!bookingId || !hoursWorked) {
      return res.status(400).json({ error: 'Booking ID and hours worked required' });
    }

    const booking = db.read('bookings', bookingId);
    if (!booking) {
      return res.status(404).json({ error: 'Booking not found' });
    }

    if (booking.workerId !== req.user.userId && booking.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const shift = db.read('shifts', booking.shiftId);
    const calculatedPay = shift.dynamicRate * hoursWorked;

    const timesheetId = uuidv4();
    const timesheet = db.create('timesheets', timesheetId, {
      bookingId,
      shiftId: booking.shiftId,
      workerId: booking.workerId,
      facilityId: booking.facilityId,
      hoursWorked,
      breakTime: breakTime || 0,
      hourlyRate: shift.dynamicRate,
      totalPay: calculatedPay,
      notes: notes || '',
      status: 'pending', // pending, approved, disputed
      submittedBy: req.user.userId,
      submittedAt: new Date()
    });

    res.status(201).json({
      message: 'Timesheet created successfully',
      timesheet
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get all timesheets for current user
router.get('/', authMiddleware, (req, res) => {
  try {
    let timesheets;

    if (req.user.role === 'worker') {
      timesheets = db.list('timesheets', { workerId: req.user.userId });
    } else if (req.user.role === 'facility') {
      timesheets = db.list('timesheets', { facilityId: req.user.userId });
    } else {
      timesheets = [];
    }

    // Enrich with shift and user details
    const enrichedTimesheets = timesheets.map(ts => {
      const shift = db.read('shifts', ts.shiftId);
      const worker = db.read('users', ts.workerId);
      
      return {
        ...ts,
        shift: shift ? {
          title: shift.title,
          startTime: shift.startTime,
          endTime: shift.endTime
        } : null,
        worker: worker ? {
          name: worker.name
        } : null
      };
    });

    res.json({ timesheets: enrichedTimesheets });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Approve timesheet (facility only)
router.put('/:id/approve', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Only facilities can approve timesheets' });
    }

    const timesheet = db.read('timesheets', req.params.id);
    if (!timesheet) {
      return res.status(404).json({ error: 'Timesheet not found' });
    }

    if (timesheet.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const updated = db.update('timesheets', req.params.id, {
      status: 'approved',
      approvedAt: new Date(),
      approvedBy: req.user.userId
    });

    res.json({
      message: 'Timesheet approved successfully',
      timesheet: updated
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get specific timesheet
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const timesheet = db.read('timesheets', req.params.id);
    if (!timesheet) {
      return res.status(404).json({ error: 'Timesheet not found' });
    }

    if (timesheet.workerId !== req.user.userId && timesheet.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    res.json({ timesheet });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
