const express = require('express');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');
const config = require('../config');

const router = express.Router();

// Generate invoice from approved timesheets
router.post('/generate', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Only facilities can generate invoices' });
    }

    const { timesheetIds, billingPeriod } = req.body;

    if (!timesheetIds || timesheetIds.length === 0) {
      return res.status(400).json({ error: 'At least one timesheet required' });
    }

    // Verify all timesheets are approved
    const timesheets = timesheetIds.map(id => db.read('timesheets', id));
    const unapproved = timesheets.filter(ts => !ts || ts.status !== 'approved');
    
    if (unapproved.length > 0) {
      return res.status(400).json({ error: 'All timesheets must be approved' });
    }

    // Calculate totals
    const subtotal = timesheets.reduce((sum, ts) => sum + ts.totalPay, 0);
    const platformFee = subtotal * config.PLATFORM_FEE_PERCENTAGE;
    const total = subtotal + platformFee;

    const invoiceId = uuidv4();
    const invoice = db.create('invoices', invoiceId, {
      facilityId: req.user.userId,
      timesheetIds,
      billingPeriod: billingPeriod || new Date().toISOString().slice(0, 7),
      subtotal,
      platformFee,
      total,
      status: 'pending', // pending, paid, overdue
      dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
      paidAt: null
    });

    res.status(201).json({
      message: 'Invoice generated successfully',
      invoice
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get all invoices for current user
router.get('/', authMiddleware, (req, res) => {
  try {
    let invoices;

    if (req.user.role === 'facility') {
      invoices = db.list('invoices', { facilityId: req.user.userId });
    } else if (req.user.role === 'worker') {
      // Workers can see invoices related to their timesheets
      const allInvoices = db.list('invoices');
      invoices = allInvoices.filter(inv => {
        const timesheets = inv.timesheetIds.map(id => db.read('timesheets', id));
        return timesheets.some(ts => ts && ts.workerId === req.user.userId);
      });
    } else {
      invoices = [];
    }

    res.json({ invoices });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get specific invoice
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const invoice = db.read('invoices', req.params.id);
    if (!invoice) {
      return res.status(404).json({ error: 'Invoice not found' });
    }

    // Check authorization
    if (req.user.role === 'facility' && invoice.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    // Enrich with timesheet details
    const timesheets = invoice.timesheetIds.map(id => {
      const ts = db.read('timesheets', id);
      if (!ts) return null;
      
      const shift = db.read('shifts', ts.shiftId);
      const worker = db.read('users', ts.workerId);
      
      return {
        id: ts.id,
        hoursWorked: ts.hoursWorked,
        hourlyRate: ts.hourlyRate,
        totalPay: ts.totalPay,
        shift: shift ? { title: shift.title, date: shift.startTime } : null,
        worker: worker ? { name: worker.name } : null
      };
    }).filter(Boolean);

    res.json({
      invoice: {
        ...invoice,
        timesheets
      }
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Mark invoice as paid (facility only)
router.put('/:id/pay', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Only facilities can mark invoices as paid' });
    }

    const invoice = db.read('invoices', req.params.id);
    if (!invoice) {
      return res.status(404).json({ error: 'Invoice not found' });
    }

    if (invoice.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const updated = db.update('invoices', req.params.id, {
      status: 'paid',
      paidAt: new Date()
    });

    res.json({
      message: 'Invoice marked as paid',
      invoice: updated
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get billing summary
router.get('/summary/stats', authMiddleware, (req, res) => {
  try {
    let stats;

    if (req.user.role === 'facility') {
      const invoices = db.list('invoices', { facilityId: req.user.userId });
      const totalBilled = invoices.reduce((sum, inv) => sum + inv.total, 0);
      const totalPaid = invoices.filter(inv => inv.status === 'paid')
        .reduce((sum, inv) => sum + inv.total, 0);
      const totalPending = invoices.filter(inv => inv.status === 'pending')
        .reduce((sum, inv) => sum + inv.total, 0);

      stats = {
        totalInvoices: invoices.length,
        totalBilled,
        totalPaid,
        totalPending
      };
    } else if (req.user.role === 'worker') {
      const timesheets = db.list('timesheets', { workerId: req.user.userId });
      const totalEarned = timesheets.filter(ts => ts.status === 'approved')
        .reduce((sum, ts) => sum + ts.totalPay, 0);
      const pendingPay = timesheets.filter(ts => ts.status === 'pending')
        .reduce((sum, ts) => sum + ts.totalPay, 0);

      stats = {
        totalTimesheets: timesheets.length,
        totalEarned,
        pendingPay,
        totalHoursWorked: timesheets.reduce((sum, ts) => sum + ts.hoursWorked, 0)
      };
    } else {
      stats = {};
    }

    res.json({ stats });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
