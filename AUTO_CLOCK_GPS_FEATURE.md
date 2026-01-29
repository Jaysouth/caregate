# Auto Clock-In/Clock-Out GPS Feature

## Overview

The CareGate platform now supports automatic clock-in and clock-out based on GPS location and duty schedule. This feature ensures workers are properly tracked when on-site and automatically clocked out when they leave or go offline.

## Features

### 1. Auto Clock-In (GPS-Based)

**Requirements:**
- Care facility GPS must be enabled
- Care worker GPS must be enabled
- Both must be at the same location (within 100 meters)
- Current time must be within scheduled shift time

**How it works:**
1. Worker opens the app at the facility location
2. App requests worker's GPS coordinates
3. App sends auto clock-in request with:
   - Worker GPS coordinates
   - Facility GPS coordinates
   - Shift/booking ID
4. System validates:
   - GPS proximity (must be within 100 meters)
   - Shift schedule (within duty hours, allows 15 min early)
   - Worker not already clocked in
5. If valid, worker is auto-clocked in with GPS verification flag

### 2. Auto Clock-Out

**Triggers:**
- **Off-site**: Worker moves more than 500 meters from facility
- **Offline**: App loses connection (no heartbeat for 5 minutes)
- **Switched off**: App closes/device turns off

**How it works:**
1. App sends heartbeat every 2-3 minutes with GPS location
2. If heartbeat includes GPS showing worker >500m away: immediate auto clock-out
3. If no heartbeat received for 5 minutes: background process auto clocks-out
4. Only works during duty schedule hours
5. Timesheet automatically created if booking exists

### 3. Heartbeat System

**Purpose:** Detect when app goes offline or worker leaves site

**Mechanism:**
- App sends heartbeat every 2-3 minutes while clocked in
- Heartbeat includes worker's current GPS coordinates
- Server checks GPS distance from facility
- Server updates last_heartbeat timestamp
- Background cron job runs every 5 minutes to check for stale heartbeats

## API Endpoints

### POST `/wp-json/caregate/v1/clock/auto-in`

Auto clock-in with GPS verification.

**Request Body:**
```json
{
  "workerId": 123,
  "facilityId": 456,
  "shiftId": 789,
  "bookingId": 101,
  "workerLat": 51.5074,
  "workerLng": -0.1278,
  "facilityLat": 51.5075,
  "facilityLng": -0.1279
}
```

**Success Response (201):**
```json
{
  "message": "Auto clocked in successfully via GPS verification",
  "clockRecord": {
    "id": 1,
    "workerId": 123,
    "facilityId": 456,
    "clockInTime": "2026-01-29 10:00:00",
    "gpsVerified": true,
    "distance": 95.5
  },
  "distance": 95.5
}
```

**Error Responses:**
- `400 missing_gps`: GPS coordinates not provided
- `400 outside_schedule`: Not within shift schedule
- `400 gps_too_far`: Worker too far from facility (>100m)
- `400 already_clocked_in`: Worker already has active clock-in

### POST `/wp-json/caregate/v1/clock/heartbeat`

Update heartbeat to prevent auto clock-out.

**Request Body:**
```json
{
  "workerId": 123,
  "workerLat": 51.5074,
  "workerLng": -0.1278
}
```

**Success Response (200):**
```json
{
  "message": "Heartbeat updated",
  "clockId": 1
}
```

**Auto Clock-Out Response (200):**
If worker moved off-site:
```json
{
  "message": "Auto clocked out: off_site",
  "clockRecord": {
    "id": 1,
    "clockOutTime": "2026-01-29 18:05:00",
    "autoClocked Out": true
  }
}
```

## Database Schema

### Clock Records Table Updates

```sql
ALTER TABLE wp_caregate_clock_records ADD COLUMN clock_in_lat DECIMAL(10, 8);
ALTER TABLE wp_caregate_clock_records ADD COLUMN clock_in_lng DECIMAL(11, 8);
ALTER TABLE wp_caregate_clock_records ADD COLUMN clock_out_lat DECIMAL(10, 8);
ALTER TABLE wp_caregate_clock_records ADD COLUMN clock_out_lng DECIMAL(11, 8);
ALTER TABLE wp_caregate_clock_records ADD COLUMN facility_lat DECIMAL(10, 8);
ALTER TABLE wp_caregate_clock_records ADD COLUMN facility_lng DECIMAL(11, 8);
ALTER TABLE wp_caregate_clock_records ADD COLUMN gps_verified BOOLEAN DEFAULT 0;
ALTER TABLE wp_caregate_clock_records ADD COLUMN auto_clocked_out BOOLEAN DEFAULT 0;
ALTER TABLE wp_caregate_clock_records ADD COLUMN last_heartbeat DATETIME;
```

## Configuration

### GPS Proximity Threshold

Default: 100 meters (configurable in code)

```php
// In class-caregate-clock.php
const GPS_PROXIMITY_THRESHOLD = 100; // meters
```

### Off-Site Distance Threshold

Default: 500 meters (worker moving away triggers auto clock-out)

```php
// In auto clock-out logic
if ($distance > 500) {
    // Auto clock-out
}
```

### Heartbeat Timeout

Default: 5 minutes

```php
// In class-caregate-clock.php
const HEARTBEAT_TIMEOUT = 5; // minutes
```

### Schedule Allowance

- **Early clock-in**: 15 minutes before shift start
- **Late clock-out**: 30 minutes after shift end

## Client Implementation

### JavaScript Example (Worker App)

```javascript
// Auto Clock-In
async function autoClockIn(shiftId, facilityId) {
  // Get worker GPS
  const workerPosition = await getCurrentPosition();
  
  // Get facility GPS (from shift data or facility profile)
  const facility = await getFacilityLocation(facilityId);
  
  // Send auto clock-in request
  const response = await fetch('/wp-json/caregate/v1/clock/auto-in', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
      shiftId: shiftId,
      facilityId: facilityId,
      workerLat: workerPosition.latitude,
      workerLng: workerPosition.longitude,
      facilityLat: facility.latitude,
      facilityLng: facility.longitude
    })
  });
  
  const data = await response.json();
  
  if (response.ok) {
    console.log('Auto clocked in!', data);
    startHeartbeat();
  } else {
    console.error('Auto clock-in failed:', data);
    // Show error to user
  }
}

// Heartbeat (send every 2-3 minutes while clocked in)
let heartbeatInterval;

function startHeartbeat() {
  heartbeatInterval = setInterval(async () => {
    const position = await getCurrentPosition();
    
    await fetch('/wp-json/caregate/v1/clock/heartbeat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
        workerLat: position.latitude,
        workerLng: position.longitude
      })
    });
  }, 2 * 60 * 1000); // Every 2 minutes
}

function stopHeartbeat() {
  if (heartbeatInterval) {
    clearInterval(heartbeatInterval);
  }
}

// Get current GPS position
function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      (position) => resolve({
        latitude: position.coords.latitude,
        longitude: position.coords.longitude
      }),
      (error) => reject(error),
      { enableHighAccuracy: true }
    );
  });
}
```

## Background Processing

### Cron Job

A WordPress cron job runs every 5 minutes to check for stale heartbeats:

```php
// Registered in class-caregate-activator.php
wp_schedule_event(time(), 'every_5_minutes', 'caregate_process_auto_clockouts');

// Hooked in class-caregate.php
add_action('caregate_process_auto_clockouts', 'CareGate_Clock::process_auto_clockouts');
```

The process:
1. Find all active clock records (no clock_out_time)
2. Check if last_heartbeat > 5 minutes old
3. Verify within shift schedule
4. Auto clock-out with reason 'offline'
5. Create timesheet if booking exists

## Testing

### Manual Testing

1. **Test Auto Clock-In:**
   - Use Postman or similar to send POST request to `/clock/auto-in`
   - Include GPS coordinates within 100m of facility
   - Verify clock record created with gps_verified=1

2. **Test Heartbeat:**
   - Send periodic POST requests to `/clock/heartbeat`
   - Verify last_heartbeat timestamp updates

3. **Test Off-Site Detection:**
   - Send heartbeat with GPS >500m from facility
   - Verify auto clock-out triggered

4. **Test Offline Detection:**
   - Stop sending heartbeats for 6+ minutes
   - Manually trigger cron: `wp cron event run caregate_process_auto_clockouts`
   - Verify auto clock-out for stale records

### Unit Tests

```php
// Test GPS distance calculation
function test_calculate_gps_distance() {
    $distance = CareGate_Clock::calculate_gps_distance(
        51.5074, -0.1278, // London
        51.5075, -0.1279  // 100m away
    );
    assert($distance < 150 && $distance > 50);
}

// Test schedule validation
function test_is_within_shift_schedule() {
    // Create test shift
    // Test during shift hours
    // Test before/after shift
}
```

## Security Considerations

1. **GPS Spoofing**: System relies on GPS coordinates from client. Could be spoofed.
   - Mitigation: GPS data is logged for audit
   - Mitigation: Manual review of suspicious patterns

2. **Heartbeat Manipulation**: Client could send fake heartbeats
   - Mitigation: GPS distance checked on each heartbeat
   - Mitigation: Facility can review clock records

3. **Schedule Bypass**: System enforces shift schedule
   - Allows 15 min early, 30 min late
   - Can be tightened if needed

## Troubleshooting

### Auto Clock-In Not Working

1. Check GPS permissions in browser/app
2. Verify GPS accuracy (should be <50m)
3. Check shift schedule allows current time
4. Verify facility GPS coordinates are set

### Worker Not Auto-Clocking Out

1. Check heartbeat is being sent
2. Verify cron job is running: `wp cron event list`
3. Check last_heartbeat timestamp in database
4. Manually trigger: `wp cron event run caregate_process_auto_clockouts`

### False Off-Site Detection

1. Increase off-site threshold (currently 500m)
2. Check GPS accuracy settings
3. Review facility GPS coordinates

---

**Last Updated**: January 29, 2026
**Version**: 1.0.0
**Plugin**: CareGate v1.0.0
