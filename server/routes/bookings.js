const express = require('express');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');

const router = express.Router();

// Apply for a shift (worker creates booking)
router.post('/', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'worker') {
      return res.status(403).json({ error: 'Only workers can apply for shifts' });
    }

    const { shiftId } = req.body;

    if (!shiftId) {
      return res.status(400).json({ error: 'Shift ID required' });
    }

    const shift = db.read('shifts', shiftId);
    if (!shift) {
      return res.status(404).json({ error: 'Shift not found' });
    }

    if (shift.status !== 'open') {
      return res.status(400).json({ error: 'Shift is not available' });
    }

    // Check if worker already applied
    const existingBooking = db.search('bookings', 
      b => b.shiftId === shiftId && b.workerId === req.user.userId
    );
    if (existingBooking.length > 0) {
      return res.status(400).json({ error: 'Already applied for this shift' });
    }

    const bookingId = uuidv4();
    const booking = db.create('bookings', bookingId, {
      shiftId,
      workerId: req.user.userId,
      facilityId: shift.facilityId,
      status: 'pending', // pending, confirmed, completed, cancelled
      appliedAt: new Date()
    });

    res.status(201).json({
      message: 'Application submitted successfully',
      booking
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get all bookings for current user
router.get('/', authMiddleware, (req, res) => {
  try {
    let bookings;
    
    if (req.user.role === 'worker') {
      bookings = db.list('bookings', { workerId: req.user.userId });
    } else if (req.user.role === 'facility') {
      bookings = db.list('bookings', { facilityId: req.user.userId });
    } else {
      bookings = [];
    }

    // Enrich bookings with shift and user details
    const enrichedBookings = bookings.map(booking => {
      const shift = db.read('shifts', booking.shiftId);
      const worker = db.read('users', booking.workerId);
      const facility = db.read('users', booking.facilityId);

      return {
        ...booking,
        shift: shift ? {
          id: shift.id,
          title: shift.title,
          startTime: shift.startTime,
          endTime: shift.endTime,
          dynamicRate: shift.dynamicRate
        } : null,
        worker: worker ? {
          id: worker.id,
          name: worker.name,
          rating: worker.rating,
          skills: worker.skills
        } : null,
        facility: facility ? {
          id: facility.id,
          name: facility.name,
          facilityType: facility.facilityType
        } : null
      };
    });

    res.json({ bookings: enrichedBookings });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Confirm/approve booking (facility only)
router.put('/:id/confirm', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Only facilities can confirm bookings' });
    }

    const booking = db.read('bookings', req.params.id);
    if (!booking) {
      return res.status(404).json({ error: 'Booking not found' });
    }

    if (booking.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    // Update booking status
    const updated = db.update('bookings', req.params.id, {
      status: 'confirmed',
      confirmedAt: new Date()
    });

    // Update shift status and assign worker
    db.update('shifts', booking.shiftId, {
      status: 'filled',
      assignedWorkerId: booking.workerId
    });

    res.json({
      message: 'Booking confirmed successfully',
      booking: updated
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Complete booking
router.put('/:id/complete', authMiddleware, (req, res) => {
  try {
    const booking = db.read('bookings', req.params.id);
    if (!booking) {
      return res.status(404).json({ error: 'Booking not found' });
    }

    // Allow both worker and facility to mark as complete
    if (booking.workerId !== req.user.userId && booking.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const updated = db.update('bookings', req.params.id, {
      status: 'completed',
      completedAt: new Date()
    });

    // Update shift status
    db.update('shifts', booking.shiftId, { status: 'completed' });

    // Update worker's completed shifts count
    const worker = db.read('users', booking.workerId);
    if (worker) {
      db.update('users', booking.workerId, {
        completedShifts: (worker.completedShifts || 0) + 1
      });
    }

    res.json({
      message: 'Booking marked as completed',
      booking: updated
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Cancel booking
router.delete('/:id', authMiddleware, (req, res) => {
  try {
    const booking = db.read('bookings', req.params.id);
    if (!booking) {
      return res.status(404).json({ error: 'Booking not found' });
    }

    if (booking.workerId !== req.user.userId && booking.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    db.update('bookings', req.params.id, {
      status: 'cancelled',
      cancelledAt: new Date()
    });

    // If shift was filled, mark it as open again
    const shift = db.read('shifts', booking.shiftId);
    if (shift && shift.status === 'filled') {
      db.update('shifts', booking.shiftId, {
        status: 'open',
        assignedWorkerId: null
      });
    }

    res.json({ message: 'Booking cancelled successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
