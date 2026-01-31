const express = require('express');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');

const router = express.Router();

// Required compliance documents for UK care workers
const REQUIRED_DOCUMENTS = [
  'DBS_CHECK', // Disclosure and Barring Service
  'RIGHT_TO_WORK',
  'PROFESSIONAL_REGISTRATION', // NMC for nurses, etc.
  'HEALTH_CLEARANCE',
  'MANDATORY_TRAINING',
  'LIABILITY_INSURANCE'
];

// Add/update compliance record for a worker
router.post('/', authMiddleware, (req, res) => {
  try {
    const { workerId, documentType, documentNumber, issueDate, expiryDate, status } = req.body;

    if (!workerId || !documentType || !status) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Only the worker themselves can add their own compliance records
    if (workerId !== req.user.userId && req.user.role !== 'admin') {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const recordId = uuidv4();
    const record = db.create('complianceRecords', recordId, {
      workerId,
      documentType,
      documentNumber: documentNumber || '',
      issueDate: issueDate ? new Date(issueDate) : null,
      expiryDate: expiryDate ? new Date(expiryDate) : null,
      status, // 'valid', 'expired', 'pending', 'rejected'
      verifiedAt: status === 'valid' ? new Date() : null
    });

    res.status(201).json({
      message: 'Compliance record created successfully',
      record
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get compliance records for a worker
router.get('/worker/:workerId', authMiddleware, (req, res) => {
  try {
    const { workerId } = req.params;

    // Workers can view their own records, facilities can view records of workers they work with
    if (workerId !== req.user.userId && req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const records = db.list('complianceRecords', { workerId });

    // Check which required documents are present
    const documentTypes = new Set(records.map(r => r.documentType));
    const missingDocuments = REQUIRED_DOCUMENTS.filter(doc => !documentTypes.has(doc));

    // Check for expired documents
    const now = new Date();
    const expiredDocuments = records.filter(r => 
      r.expiryDate && new Date(r.expiryDate) < now
    );

    const isFullyCompliant = missingDocuments.length === 0 && 
                             expiredDocuments.length === 0 &&
                             records.every(r => r.status === 'valid');

    res.json({
      records,
      compliance: {
        isFullyCompliant,
        missingDocuments,
        expiredDocuments: expiredDocuments.map(d => d.documentType),
        completionPercentage: Math.round((documentTypes.size / REQUIRED_DOCUMENTS.length) * 100)
      }
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get compliance status for all workers (facility view)
router.get('/status', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Only facilities can view compliance status' });
    }

    // Get all workers who have worked with this facility
    const bookings = db.list('bookings', { facilityId: req.user.userId });
    const workerIds = [...new Set(bookings.map(b => b.workerId))];

    const workerCompliance = workerIds.map(workerId => {
      const worker = db.read('users', workerId);
      if (!worker) return null;

      const records = db.list('complianceRecords', { workerId });
      const documentTypes = new Set(records.map(r => r.documentType));
      const missingDocuments = REQUIRED_DOCUMENTS.filter(doc => !documentTypes.has(doc));

      const now = new Date();
      const expiredCount = records.filter(r => 
        r.expiryDate && new Date(r.expiryDate) < now
      ).length;

      const isCompliant = missingDocuments.length === 0 && 
                         expiredCount === 0 &&
                         records.every(r => r.status === 'valid');

      return {
        workerId,
        workerName: worker.name,
        isCompliant,
        documentsCount: records.length,
        requiredDocuments: REQUIRED_DOCUMENTS.length,
        expiredCount
      };
    }).filter(Boolean);

    res.json({ workerCompliance });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Update compliance record
router.put('/:id', authMiddleware, (req, res) => {
  try {
    const record = db.read('complianceRecords', req.params.id);
    if (!record) {
      return res.status(404).json({ error: 'Compliance record not found' });
    }

    if (record.workerId !== req.user.userId && req.user.role !== 'admin') {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const updated = db.update('complianceRecords', req.params.id, req.body);
    res.json({
      message: 'Compliance record updated successfully',
      record: updated
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get required documents list
router.get('/required-documents', authMiddleware, (req, res) => {
  res.json({
    documents: REQUIRED_DOCUMENTS.map(doc => ({
      type: doc,
      name: doc.replace(/_/g, ' ').toLowerCase()
        .replace(/\b\w/g, c => c.toUpperCase())
    }))
  });
});

module.exports = router;
