# Auto Clock-In/Clock-Out System Documentation

## Overview

The Auto Clock-In/Clock-Out system automatically manages worker attendance based on GPS location and duty schedule. Workers are automatically clocked in when they arrive at the facility during their scheduled shift, and automatically clocked out when they leave or go offline.

---

## Features

### Auto Clock-In
Automatically clocks in workers when ALL conditions are met:
- ✅ Worker GPS is enabled and accurate
- ✅ Worker is within facility geofence (100m radius by default)
- ✅ Current time is within scheduled shift time
- ✅ Worker has confirmed booking for the shift
- ✅ Worker is not already clocked in

### Auto Clock-Out
Automatically clocks out workers when ANY condition is met:
- ✅ Worker leaves facility location (off-site)
- ✅ Web app goes offline or loses internet connection
- ✅ Browser tab/window is closed
- ✅ Shift scheduled end time is reached
- ⚠️ Only operates during scheduled duty hours

---

## How It Works

### GPS Geofencing

**Geofence Parameters:**
- **Radius**: 100 meters (configurable)
- **GPS Accuracy Threshold**: 20 meters
- **Polling Interval**: 60 seconds

**Distance Calculation:**
Uses the Haversine formula to calculate accurate distance between worker and facility GPS coordinates:

```javascript
distance = haversine(worker_location, facility_location)
if (distance <= 0.1 km) { // 100 meters
    // Worker is within geofence
}
```

### Schedule Validation

The system only operates during scheduled shift times:

1. **Retrieves Active Shift**: Finds confirmed bookings for the worker
2. **Time Check**: Validates current time is between shift start and end
3. **Status Check**: Ensures shift status is 'open' and booking is 'confirmed'
4. **Clock Status**: Checks worker is not already clocked in

```sql
SELECT * FROM bookings b
INNER JOIN shifts s ON b.shift_id = s.id
WHERE b.worker_id = ?
  AND b.status = 'confirmed'
  AND s.start_time <= NOW()
  AND s.end_time >= NOW()
  AND s.status = 'open'
```

---

## User Flow

### 1. Enable GPS (First Time)

When a worker logs in for the first time:

1. System requests GPS permission
2. Worker clicks "Allow" in browser prompt
3. GPS coordinates are captured
4. Auto-clock monitoring starts
5. Notification: "✓ GPS Enabled - Auto clock-in/out is now active"

### 2. Arrive at Facility

When a worker approaches the facility during their shift:

1. System continuously monitors GPS location
2. Detects worker is within 100m of facility
3. Validates shift schedule exists and is active
4. Automatically clocks in the worker
5. Notification: "✓ Auto Clocked In - You have been clocked in at the facility"
6. Clock record created with method='auto'

### 3. During Shift

While the worker is on shift:

1. System checks location every 60 seconds
2. Validates worker is still within geofence
3. Monitors online/offline status
4. No action required from worker
5. Worker can manually clock out if needed

### 4. Leave Facility

When a worker leaves the facility:

1. System detects worker is outside 100m geofence
2. Automatically clocks out the worker
3. Notification: "✓ Auto Clocked Out - Left facility location"
4. Clock record updated with clock_out_time

### 5. Go Offline

If the worker's device loses internet connection:

1. Browser detects offline status
2. System triggers auto clock-out
3. Notification: "✓ Auto Clocked Out - App went offline"
4. Attempt to send data when back online

### 6. Close Browser

If the worker closes the browser or tab:

1. `beforeunload` event is triggered
2. System sends beacon to server
3. Auto clock-out is attempted
4. Uses `navigator.sendBeacon()` for reliability

---

## API Endpoints

### Check Auto Clock-In Conditions
```
POST /wp-json/caregate/v1/auto-clock/check-in
```

**Request Body:**
```json
{
  "location": {
    "lat": 51.5074,
    "lng": -0.1278,
    "accuracy": 15
  },
  "gpsEnabled": true
}
```

**Response:**
```json
{
  "shouldClockIn": true,
  "shift": {
    "id": 123,
    "bookingId": 456,
    "facilityId": 789,
    "startTime": "2026-01-29 09:00:00",
    "endTime": "2026-01-29 17:00:00"
  }
}
```

### Perform Auto Clock-In
```
POST /wp-json/caregate/v1/auto-clock/clock-in
```

**Request Body:**
```json
{
  "shiftId": 123,
  "bookingId": 456,
  "facilityId": 789,
  "location": {
    "lat": 51.5074,
    "lng": -0.1278
  }
}
```

**Response:**
```json
{
  "message": "Clocked in successfully",
  "clockRecord": {
    "id": 1,
    "workerId": 10,
    "facilityId": 789,
    "clockInTime": "2026-01-29 09:05:32",
    "clockInMethod": "auto",
    "status": "in_progress"
  }
}
```

### Check Auto Clock-Out Conditions
```
POST /wp-json/caregate/v1/auto-clock/check-out
```

**Request Body:**
```json
{
  "location": {
    "lat": 51.5080,
    "lng": -0.1290
  },
  "gpsEnabled": true,
  "isOnline": true
}
```

**Response:**
```json
{
  "shouldClockOut": true,
  "reason": "Left facility location",
  "distance": 150,
  "clockRecordId": 1
}
```

### Perform Auto Clock-Out
```
POST /wp-json/caregate/v1/auto-clock/clock-out
```

**Request Body:**
```json
{
  "reason": "Left facility location"
}
```

**Response:**
```json
{
  "message": "Clocked out successfully",
  "clockRecord": {
    "id": 1,
    "clockOutTime": "2026-01-29 17:02:15",
    "clockOutMethod": "auto",
    "hoursWorked": 7.95,
    "status": "completed"
  }
}
```

### Get Auto-Clock Configuration
```
GET /wp-json/caregate/v1/auto-clock/config
```

**Response:**
```json
{
  "geofenceRadius": 100,
  "gpsAccuracyThreshold": 20,
  "pollingInterval": 60
}
```

---

## JavaScript Integration

### Initialize Auto-Clock System

```javascript
// After user logs in as worker
if (currentUser.role === 'worker') {
    const autoClockSystem = new AutoClockSystem(API_BASE, authToken);
    
    // Request notification permission
    await AutoClockSystem.requestNotificationPermission();
    
    // System automatically starts monitoring
}
```

### Listen for Auto-Clock Events

```javascript
// Listen for auto clock-in
window.addEventListener('autoClockIn', (event) => {
    console.log('Auto clocked in:', event.detail);
    // Update UI to show clocked-in status
    updateClockStatus('clocked_in');
});

// Listen for auto clock-out
window.addEventListener('autoClockOut', (event) => {
    console.log('Auto clocked out:', event.detail);
    // Update UI to show clocked-out status
    updateClockStatus('clocked_out');
});
```

### Manual Control

```javascript
// Stop auto-clock monitoring
autoClockSystem.stopMonitoring();

// Restart auto-clock monitoring
autoClockSystem.startMonitoring();

// Check current location
console.log(autoClockSystem.currentLocation);
// { lat: 51.5074, lng: -0.1278, accuracy: 15 }
```

---

## UI Components

### GPS Status Indicator

```html
<div class="gps-status active">
    <span class="gps-icon">📍</span>
    <span class="gps-text">GPS Enabled</span>
</div>
```

### Geofence Status

```html
<div class="geofence-status within">
    <span class="status-icon">✓</span>
    <span class="status-text">At facility (35m away)</span>
</div>
```

### Auto-Clock Notifications

```html
<div id="auto-clock-notification" class="notification">
    <strong>✓ Auto Clocked In</strong><br>
    You have been clocked in at the facility
</div>
```

### Clock Status Display

```html
<div class="clock-status">
    <div class="status-badge clocked-in">
        <span class="badge-icon">🟢</span>
        <span class="badge-text">Clocked In (Auto)</span>
    </div>
    <div class="clock-time">09:05 AM</div>
    <div class="facility-name">Sunshine Care Home</div>
</div>
```

---

## Configuration

### Backend Configuration (WordPress)

Geofence and accuracy settings can be adjusted in the `CareGate_Auto_Clock` class:

```php
class CareGate_Auto_Clock {
    private static $geofence_radius = 100; // meters
    private static $gps_accuracy_threshold = 20; // meters
}
```

### Frontend Configuration

Polling interval and other settings in `AutoClockSystem`:

```javascript
this.config = {
    geofenceRadius: 100, // meters
    gpsAccuracyThreshold: 20, // meters
    pollingInterval: 60 // seconds
};
```

---

## Security & Privacy

### GPS Permission
- GPS permission is requested, not required
- Workers can decline GPS and use manual clock-in/out
- GPS coordinates are only used for geofencing
- Location data is not tracked or stored beyond clock records

### Data Storage
- Only clock-in/out times and locations are stored
- Location data is stored as JSON in clock_records table
- No continuous tracking between clock events
- Data is used only for attendance verification

### Manual Override
- Workers can manually clock in/out at any time
- Manual clock overrides auto-clock
- Facility staff can also manually enter clock records
- Provides flexibility when GPS is unavailable

---

## Troubleshooting

### GPS Permission Denied

**Problem**: Worker denied GPS permission
**Solution**: 
1. Open browser settings
2. Find site permissions
3. Allow location access
4. Refresh the page

### Auto Clock-In Not Working

**Problem**: Worker at facility but not auto clocked in
**Check**:
1. GPS permission is granted
2. Current time is within shift schedule
3. Worker has confirmed booking
4. Worker is not already clocked in
5. GPS accuracy is < 20m
6. Distance to facility is < 100m

### Auto Clock-Out Not Working

**Problem**: Worker left but not auto clocked out
**Check**:
1. GPS is still enabled
2. App is still online
3. Worker is actually clocked in
4. Distance from facility > 100m

### Location Accuracy Low

**Problem**: GPS accuracy > 20m
**Solution**:
1. Ensure device has clear sky view
2. Wait for GPS to stabilize (30-60 seconds)
3. Move away from buildings/obstacles
4. Use manual clock if GPS unavailable

---

## Testing

### Test Auto Clock-In

1. Create a shift for today's date/time
2. Confirm worker booking
3. Set facility location (GPS coordinates)
4. Navigate worker to facility location (or simulate)
5. Verify auto clock-in triggers
6. Check clock_records table

### Test Auto Clock-Out

1. Worker should be clocked in
2. Move worker outside 100m geofence
3. Verify auto clock-out triggers
4. Or turn off internet connection
5. Or close browser tab
6. Check clock_records table for clock_out_time

### Test Schedule Validation

1. Create shift with future start time
2. Worker arrives at facility early
3. Verify auto clock-in does NOT trigger
4. Wait until shift start time
5. Verify auto clock-in NOW triggers

---

## Monitoring & Logs

### Clock Records

All clock events are logged in the `wp_caregate_clock_records` table:

```sql
SELECT 
    id,
    worker_id,
    facility_id,
    clock_in_time,
    clock_in_method,  -- 'auto' or 'manual'
    clock_out_time,
    clock_out_method, -- 'auto' or 'manual'
    notes,            -- Reason for auto clock-out
    location,         -- GPS coordinates JSON
    status            -- 'in_progress' or 'completed'
FROM wp_caregate_clock_records
ORDER BY clock_in_time DESC;
```

### Browser Console Logs

Enable console logging for debugging:

```javascript
// Shows GPS updates
console.log('Location updated:', currentLocation);

// Shows auto-clock checks
console.log('Checking auto-clock conditions...');

// Shows results
console.log('Should clock in:', shouldClockIn);
console.log('Should clock out:', shouldClockOut);
```

---

## Best Practices

### For Workers

1. **Enable GPS**: Allow location access for auto-clock to work
2. **Keep App Open**: Don't close browser during shift
3. **Stay Online**: Maintain internet connection
4. **Verify Status**: Check clock-in/out confirmations
5. **Manual Backup**: Use manual clock if auto-clock fails

### For Facilities

1. **Set Location**: Ensure facility GPS coordinates are accurate
2. **Schedule Shifts**: Create shifts with proper start/end times
3. **Confirm Bookings**: Confirm worker bookings before shift
4. **Monitor Accuracy**: Check geofence radius is appropriate
5. **Review Logs**: Monitor auto-clock events in records

### For Administrators

1. **Configure Radius**: Adjust geofence based on facility size
2. **Set Polling**: Balance frequency with battery/data usage
3. **Monitor Logs**: Review clock_records for issues
4. **Test System**: Verify auto-clock works before deployment
5. **Educate Users**: Train workers on GPS permission

---

## Future Enhancements

Potential improvements for future versions:

- [ ] Configurable geofence radius per facility
- [ ] Multiple geofences for large facilities
- [ ] Indoor positioning (WiFi/Bluetooth)
- [ ] Break time detection
- [ ] Movement speed detection (driving vs walking)
- [ ] Battery optimization modes
- [ ] Historical location tracking (opt-in)
- [ ] Geofence visualization on map
- [ ] Admin dashboard for monitoring
- [ ] Email/SMS alerts for auto-clock events

---

## Support

For issues or questions:
- Check troubleshooting section above
- Review browser console for errors
- Verify GPS permissions are granted
- Test with manual clock-in/out
- Contact system administrator

---

## Summary

The Auto Clock-In/Clock-Out system provides hands-free attendance management using GPS location and duty schedules. Workers are automatically clocked in when they arrive at the facility during their scheduled shift, and automatically clocked out when they leave or go offline.

**Key Benefits:**
- ✅ Hands-free attendance tracking
- ✅ Accurate location verification
- ✅ Prevents forgotten clock-ins/outs
- ✅ Works only during scheduled shifts
- ✅ Respects duty time table
- ✅ Handles offline scenarios
- ✅ Manual override available

**Requirements:**
- GPS-enabled device
- Location permission granted
- Internet connection
- Scheduled shift with confirmed booking
- Facility GPS coordinates set
