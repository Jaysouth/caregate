const request = require('supertest');
const app = require('../server/index');

describe('CareGate API Tests', () => {
  let workerToken;
  let facilityToken;
  let workerId;
  let facilityId;
  let shiftId;
  let bookingId;

  // Test authentication
  describe('Authentication', () => {
    test('should register a worker', async () => {
      const response = await request(app)
        .post('/api/auth/register')
        .send({
          email: 'worker@test.com',
          password: 'password123',
          name: 'Test Worker',
          role: 'worker',
          skills: ['Nursing', 'Elderly Care'],
          location: { lat: 51.5074, lng: -0.1278 }
        });

      expect(response.status).toBe(201);
      expect(response.body.token).toBeDefined();
      expect(response.body.user.role).toBe('worker');
      
      workerToken = response.body.token;
      workerId = response.body.user.id;
    });

    test('should register a facility', async () => {
      const response = await request(app)
        .post('/api/auth/register')
        .send({
          email: 'facility@test.com',
          password: 'password123',
          name: 'Test Care Home',
          role: 'facility',
          facilityType: 'Care Home',
          location: { lat: 51.5074, lng: -0.1278 }
        });

      expect(response.status).toBe(201);
      expect(response.body.token).toBeDefined();
      expect(response.body.user.role).toBe('facility');
      
      facilityToken = response.body.token;
      facilityId = response.body.user.id;
    });

    test('should login successfully', async () => {
      const response = await request(app)
        .post('/api/auth/login')
        .send({
          email: 'worker@test.com',
          password: 'password123'
        });

      expect(response.status).toBe(200);
      expect(response.body.token).toBeDefined();
    });

    test('should fail with invalid credentials', async () => {
      const response = await request(app)
        .post('/api/auth/login')
        .send({
          email: 'worker@test.com',
          password: 'wrongpassword'
        });

      expect(response.status).toBe(401);
    });
  });

  // Test shift management
  describe('Shifts', () => {
    test('facility should create a shift', async () => {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      const dayAfter = new Date(tomorrow);
      dayAfter.setHours(tomorrow.getHours() + 8);

      const response = await request(app)
        .post('/api/shifts')
        .set('Authorization', `Bearer ${facilityToken}`)
        .send({
          title: 'Night Shift Nurse',
          description: 'Elderly care facility needs qualified nurse',
          startTime: tomorrow.toISOString(),
          endTime: dayAfter.toISOString(),
          requiredSkills: ['Nursing', 'Elderly Care'],
          location: { lat: 51.5074, lng: -0.1278 },
          baseRate: 20.00,
          skillLevel: 'advanced'
        });

      expect(response.status).toBe(201);
      expect(response.body.shift).toBeDefined();
      expect(response.body.shift.dynamicRate).toBeGreaterThan(20);
      
      shiftId = response.body.shift.id;
    });

    test('worker should get matched shifts', async () => {
      const response = await request(app)
        .get('/api/shifts/matches')
        .set('Authorization', `Bearer ${workerToken}`);

      expect(response.status).toBe(200);
      expect(response.body.matches).toBeDefined();
      expect(Array.isArray(response.body.matches)).toBe(true);
    });

    test('should list all shifts', async () => {
      const response = await request(app)
        .get('/api/shifts')
        .set('Authorization', `Bearer ${workerToken}`);

      expect(response.status).toBe(200);
      expect(response.body.shifts).toBeDefined();
    });
  });

  // Test bookings
  describe('Bookings', () => {
    test('worker should apply for a shift', async () => {
      const response = await request(app)
        .post('/api/bookings')
        .set('Authorization', `Bearer ${workerToken}`)
        .send({ shiftId });

      expect(response.status).toBe(201);
      expect(response.body.booking).toBeDefined();
      expect(response.body.booking.status).toBe('pending');
      
      bookingId = response.body.booking.id;
    });

    test('facility should confirm booking', async () => {
      const response = await request(app)
        .put(`/api/bookings/${bookingId}/confirm`)
        .set('Authorization', `Bearer ${facilityToken}`);

      expect(response.status).toBe(200);
      expect(response.body.booking.status).toBe('confirmed');
    });

    test('should list bookings', async () => {
      const response = await request(app)
        .get('/api/bookings')
        .set('Authorization', `Bearer ${workerToken}`);

      expect(response.status).toBe(200);
      expect(response.body.bookings).toBeDefined();
    });
  });

  // Test compliance
  describe('Compliance', () => {
    test('should add compliance record', async () => {
      const response = await request(app)
        .post('/api/compliance')
        .set('Authorization', `Bearer ${workerToken}`)
        .send({
          workerId,
          documentType: 'DBS_CHECK',
          documentNumber: 'DBS123456',
          issueDate: '2025-01-01',
          expiryDate: '2027-01-01',
          status: 'valid'
        });

      expect(response.status).toBe(201);
      expect(response.body.record).toBeDefined();
    });

    test('should get worker compliance status', async () => {
      const response = await request(app)
        .get(`/api/compliance/worker/${workerId}`)
        .set('Authorization', `Bearer ${workerToken}`);

      expect(response.status).toBe(200);
      expect(response.body.compliance).toBeDefined();
      expect(response.body.records).toBeDefined();
    });

    test('should get required documents', async () => {
      const response = await request(app)
        .get('/api/compliance/required-documents')
        .set('Authorization', `Bearer ${workerToken}`);

      expect(response.status).toBe(200);
      expect(response.body.documents).toBeDefined();
    });
  });

  // Test timesheets
  describe('Timesheets', () => {
    test('should create timesheet', async () => {
      const response = await request(app)
        .post('/api/timesheets')
        .set('Authorization', `Bearer ${workerToken}`)
        .send({
          bookingId,
          hoursWorked: 8,
          breakTime: 0.5,
          notes: 'Shift completed successfully'
        });

      expect(response.status).toBe(201);
      expect(response.body.timesheet).toBeDefined();
      expect(response.body.timesheet.totalPay).toBeGreaterThan(0);
    });
  });

  // Test user profile
  describe('User Profile', () => {
    test('should get current user profile', async () => {
      const response = await request(app)
        .get('/api/users/me')
        .set('Authorization', `Bearer ${workerToken}`);

      expect(response.status).toBe(200);
      expect(response.body.user).toBeDefined();
      expect(response.body.user.password).toBeUndefined();
    });

    test('should update profile', async () => {
      const response = await request(app)
        .put('/api/users/me')
        .set('Authorization', `Bearer ${workerToken}`)
        .send({
          skills: ['Nursing', 'Elderly Care', 'Dementia Care']
        });

      expect(response.status).toBe(200);
      expect(response.body.user.skills).toHaveLength(3);
    });
  });
});
