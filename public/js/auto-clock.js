/**
 * Auto Clock-in/Clock-out System based on GPS and duty schedule
 * 
 * Features:
 * - Auto clock-in when worker arrives at facility during scheduled shift
 * - Auto clock-out when worker leaves facility or goes offline
 * - GPS geofencing with configurable radius
 * - Only operates during scheduled duty time
 */

class AutoClockSystem {
    constructor(apiBase, authToken) {
        this.apiBase = apiBase;
        this.authToken = authToken;
        this.config = {
            geofenceRadius: 100, // meters
            gpsAccuracyThreshold: 20, // meters
            pollingInterval: 60 // seconds
        };
        this.gpsEnabled = false;
        this.currentLocation = null;
        this.watchId = null;
        this.pollIntervalId = null;
        this.lastClockStatus = null;
        this.isOnline = navigator.onLine;
        
        this.init();
    }

    async init() {
        // Get configuration from API
        await this.loadConfig();
        
        // Request GPS permission
        await this.requestGPSPermission();
        
        // Start monitoring if GPS enabled
        if (this.gpsEnabled) {
            this.startMonitoring();
        }
        
        // Setup online/offline listeners
        this.setupOnlineMonitoring();
        
        // Setup visibility listener for page close/minimize
        this.setupVisibilityMonitoring();
    }

    async loadConfig() {
        try {
            const response = await fetch(`${this.apiBase}/api/auto-clock/config`, {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                this.config = await response.json();
                console.log('Auto-clock config loaded:', this.config);
            }
        } catch (error) {
            console.error('Failed to load auto-clock config:', error);
        }
    }

    async requestGPSPermission() {
        if (!navigator.geolocation) {
            console.warn('Geolocation not supported');
            return false;
        }

        try {
            // Try to get position to trigger permission request
            await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.gpsEnabled = true;
                        this.currentLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        };
                        console.log('GPS enabled. Current location:', this.currentLocation);
                        this.showNotification('✓ GPS Enabled', 'Auto clock-in/out is now active');
                        resolve(position);
                    },
                    (error) => {
                        console.warn('GPS permission denied:', error);
                        this.showNotification('⚠ GPS Disabled', 'Auto clock-in/out requires GPS');
                        reject(error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
            
            return true;
        } catch (error) {
            return false;
        }
    }

    startMonitoring() {
        console.log('Starting auto-clock monitoring...');
        
        // Start GPS watch
        if (navigator.geolocation) {
            this.watchId = navigator.geolocation.watchPosition(
                (position) => {
                    this.currentLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                    console.log('Location updated:', this.currentLocation);
                },
                (error) => {
                    console.error('GPS error:', error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 30000,
                    maximumAge: 0
                }
            );
        }

        // Start polling for auto-clock checks
        this.pollIntervalId = setInterval(() => {
            this.checkAutoClockConditions();
        }, this.config.pollingInterval * 1000);

        // Initial check
        this.checkAutoClockConditions();
    }

    stopMonitoring() {
        console.log('Stopping auto-clock monitoring...');
        
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        
        if (this.pollIntervalId) {
            clearInterval(this.pollIntervalId);
            this.pollIntervalId = null;
        }
    }

    async checkAutoClockConditions() {
        if (!this.gpsEnabled || !this.currentLocation) {
            return;
        }

        try {
            // Check if should clock in
            await this.checkClockIn();
            
            // Check if should clock out
            await this.checkClockOut();
        } catch (error) {
            console.error('Error checking auto-clock conditions:', error);
        }
    }

    async checkClockIn() {
        const response = await fetch(`${this.apiBase}/api/auto-clock/check-in`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.authToken}`
            },
            body: JSON.stringify({
                location: this.currentLocation,
                gpsEnabled: this.gpsEnabled
            })
        });

        const data = await response.json();
        
        if (data.shouldClockIn) {
            console.log('Conditions met for auto clock-in:', data);
            await this.performAutoClockIn(data.shift);
        } else {
            if (data.reason !== 'Already clocked in') {
                console.log('Auto clock-in not triggered:', data.reason);
            }
        }
    }

    async checkClockOut() {
        const response = await fetch(`${this.apiBase}/api/auto-clock/check-out`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.authToken}`
            },
            body: JSON.stringify({
                location: this.currentLocation,
                gpsEnabled: this.gpsEnabled,
                isOnline: this.isOnline
            })
        });

        const data = await response.json();
        
        if (data.shouldClockOut) {
            console.log('Conditions met for auto clock-out:', data);
            await this.performAutoClockOut(data.reason);
        }
    }

    async performAutoClockIn(shift) {
        try {
            const response = await fetch(`${this.apiBase}/api/auto-clock/clock-in`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    shiftId: shift.id,
                    bookingId: shift.bookingId,
                    facilityId: shift.facilityId,
                    location: this.currentLocation
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Auto clocked in:', data);
                this.showNotification('✓ Auto Clocked In', 'You have been clocked in at the facility');
                this.lastClockStatus = 'clocked_in';
                
                // Emit event for dashboard to update
                window.dispatchEvent(new CustomEvent('autoClockIn', { detail: data }));
            } else {
                const error = await response.json();
                console.error('Auto clock-in failed:', error);
            }
        } catch (error) {
            console.error('Error performing auto clock-in:', error);
        }
    }

    async performAutoClockOut(reason) {
        try {
            const response = await fetch(`${this.apiBase}/api/auto-clock/clock-out`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    reason: reason
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Auto clocked out:', data);
                this.showNotification('✓ Auto Clocked Out', `Reason: ${reason}`);
                this.lastClockStatus = 'clocked_out';
                
                // Emit event for dashboard to update
                window.dispatchEvent(new CustomEvent('autoClockOut', { detail: data }));
            } else {
                const error = await response.json();
                console.error('Auto clock-out failed:', error);
            }
        } catch (error) {
            console.error('Error performing auto clock-out:', error);
        }
    }

    setupOnlineMonitoring() {
        window.addEventListener('online', () => {
            console.log('App came online');
            this.isOnline = true;
        });

        window.addEventListener('offline', () => {
            console.log('App went offline');
            this.isOnline = false;
            
            // Attempt to clock out before going offline
            if (this.lastClockStatus === 'clocked_in') {
                this.performAutoClockOut('App went offline');
            }
        });
    }

    setupVisibilityMonitoring() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('Page hidden');
                // Don't auto clock out on page hide, only on actual offline
            } else {
                console.log('Page visible');
                // Resume monitoring
                if (this.gpsEnabled && !this.pollIntervalId) {
                    this.startMonitoring();
                }
            }
        });

        // Handle page unload (browser close/tab close)
        window.addEventListener('beforeunload', () => {
            // Send beacon to attempt clock out
            if (this.lastClockStatus === 'clocked_in' && navigator.sendBeacon) {
                const data = JSON.stringify({
                    reason: 'Browser closed'
                });
                
                navigator.sendBeacon(
                    `${this.apiBase}/api/auto-clock/clock-out`,
                    new Blob([data], { type: 'application/json' })
                );
            }
        });
    }

    showNotification(title, message) {
        // Try to show browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/favicon.ico'
            });
        }
        
        // Also show in-app notification
        const notifEl = document.getElementById('auto-clock-notification');
        if (notifEl) {
            notifEl.innerHTML = `<strong>${title}</strong><br>${message}`;
            notifEl.classList.add('show');
            setTimeout(() => notifEl.classList.remove('show'), 5000);
        } else {
            console.log(`Notification: ${title} - ${message}`);
        }
    }

    // Request notification permission
    static async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            await Notification.requestPermission();
        }
    }
}

// Export for use in main app
window.AutoClockSystem = AutoClockSystem;
