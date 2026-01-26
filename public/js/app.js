// API Configuration
const API_BASE = window.location.origin.includes('localhost') 
    ? 'http://localhost:3000' 
    : window.location.origin;

let currentUser = null;
let authToken = null;

// Utility Functions
function showError(message) {
    const errorEl = document.getElementById('auth-error');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
        setTimeout(() => errorEl.classList.remove('show'), 5000);
    } else {
        alert(message);
    }
}

function showScreen(screenId) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById(screenId).classList.add('active');
}

function showTab(tabName, event) {
    if (tabName === 'login') {
        document.getElementById('login-form').style.display = 'block';
        document.getElementById('register-form').style.display = 'none';
    } else {
        document.getElementById('login-form').style.display = 'none';
        document.getElementById('register-form').style.display = 'block';
    }
    
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
}

function toggleRoleFields() {
    const role = document.getElementById('reg-role').value;
    document.getElementById('worker-fields').style.display = role === 'worker' ? 'block' : 'none';
    document.getElementById('facility-fields').style.display = role === 'facility' ? 'block' : 'none';
}

// API Calls
async function apiCall(endpoint, options = {}) {
    const config = {
        headers: {
            'Content-Type': 'application/json',
            ...(authToken && { 'Authorization': `Bearer ${authToken}` })
        },
        ...options
    };

    const response = await fetch(`${API_BASE}${endpoint}`, config);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || 'Request failed');
    }

    return data;
}

// Authentication
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const data = await apiCall('/api/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        authToken = data.token;
        currentUser = data.user;
        
        if (currentUser.role === 'worker') {
            showWorkerDashboard();
        } else {
            showFacilityDashboard();
        }
    } catch (error) {
        showError(error.message);
    }
});

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = document.getElementById('reg-name').value;
    const email = document.getElementById('reg-email').value;
    const password = document.getElementById('reg-password').value;
    const role = document.getElementById('reg-role').value;

    const payload = {
        name, email, password, role,
        location: { lat: 51.5074, lng: -0.1278 } // Default London location
    };

    if (role === 'worker') {
        const skills = document.getElementById('reg-skills').value.split(',').map(s => s.trim());
        payload.skills = skills;
    } else {
        payload.facilityType = document.getElementById('reg-facility-type').value;
    }

    try {
        const data = await apiCall('/api/auth/register', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        authToken = data.token;
        currentUser = data.user;

        if (currentUser.role === 'worker') {
            showWorkerDashboard();
        } else {
            showFacilityDashboard();
        }
    } catch (error) {
        showError(error.message);
    }
});

function logout() {
    authToken = null;
    currentUser = null;
    showScreen('auth-screen');
}

// Worker Dashboard
async function showWorkerDashboard() {
    showScreen('worker-screen');
    document.getElementById('worker-name').textContent = `Welcome, ${currentUser.name}`;
    document.getElementById('worker-rating').textContent = currentUser.rating || '5.0';
    document.getElementById('worker-shifts').textContent = currentUser.completedShifts || '0';
    
    await loadMatchedShifts();
    await loadWorkerBookings();
    await loadCompliance();
}

async function loadMatchedShifts() {
    try {
        const data = await apiCall('/api/shifts/matches');
        const container = document.getElementById('matches-list');
        
        if (!data.matches || data.matches.length === 0) {
            container.innerHTML = '<div class="empty-state">📋<p>No matched shifts available</p></div>';
            return;
        }

        container.innerHTML = data.matches.map(shift => `
            <div class="shift-card">
                <h4>${shift.title}</h4>
                <p>${shift.description || 'No description'}</p>
                <div class="shift-meta">
                    <span>📅 ${new Date(shift.startTime).toLocaleString()}</span>
                    <span>⏰ ${calculateDuration(shift.startTime, shift.endTime)} hours</span>
                    <span>📍 ${shift.distance}km away</span>
                </div>
                <div>
                    ${shift.requiredSkills.map(s => `<span class="badge badge-skill">${s}</span>`).join('')}
                    <span class="badge badge-match">Match: ${shift.matchScore}%</span>
                </div>
                <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <span class="price">£${shift.dynamicRate}/hr</span>
                    <button class="btn btn-success" style="width: auto; padding: 10px 20px;" onclick="applyForShift('${shift.id}')">Apply</button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading matches:', error);
    }
}

async function applyForShift(shiftId) {
    try {
        await apiCall('/api/bookings', {
            method: 'POST',
            body: JSON.stringify({ shiftId })
        });
        
        alert('Application submitted successfully!');
        await loadMatchedShifts();
        await loadWorkerBookings();
    } catch (error) {
        alert(error.message);
    }
}

async function loadWorkerBookings() {
    try {
        const data = await apiCall('/api/bookings');
        const container = document.getElementById('bookings-list');
        
        if (!data.bookings || data.bookings.length === 0) {
            container.innerHTML = '<div class="empty-state">📋<p>No bookings yet</p></div>';
            return;
        }

        container.innerHTML = data.bookings.map(booking => `
            <div class="booking-card">
                <h4>${booking.shift?.title || 'Shift'}</h4>
                <div class="booking-meta">
                    <span>📅 ${booking.shift?.startTime ? new Date(booking.shift.startTime).toLocaleString() : 'N/A'}</span>
                    <span class="badge badge-${booking.status}">${booking.status}</span>
                </div>
                <div style="margin-top: 10px;">
                    <span class="price">£${booking.shift?.dynamicRate || 0}/hr</span>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading bookings:', error);
    }
}

async function loadCompliance() {
    try {
        const data = await apiCall(`/api/compliance/worker/${currentUser.id}`);
        const container = document.getElementById('compliance-info');
        
        const percentage = data.compliance?.completionPercentage || 0;
        
        container.innerHTML = `
            <h4>Compliance Status</h4>
            <div class="compliance-progress">
                <div class="compliance-bar" style="width: ${percentage}%">${percentage}%</div>
            </div>
            <p>${data.compliance?.isFullyCompliant ? '✅ Fully Compliant' : '⚠️ Action Required'}</p>
            
            ${data.compliance?.missingDocuments?.length > 0 ? `
                <div style="margin-top: 20px;">
                    <h5>Missing Documents:</h5>
                    <div class="document-list">
                        ${data.compliance.missingDocuments.map(doc => `
                            <div class="document-item missing">❌ ${doc.replace(/_/g, ' ')}</div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
            
            ${data.records?.length > 0 ? `
                <div style="margin-top: 20px;">
                    <h5>Uploaded Documents:</h5>
                    <div class="document-list">
                        ${data.records.map(rec => `
                            <div class="document-item valid">✅ ${rec.documentType.replace(/_/g, ' ')}</div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        `;
    } catch (error) {
        console.error('Error loading compliance:', error);
    }
}

function switchWorkerTab(tab, event) {
    document.querySelectorAll('#worker-screen .tab').forEach(t => t.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    document.getElementById('worker-matches').style.display = tab === 'matches' ? 'block' : 'none';
    document.getElementById('worker-bookings').style.display = tab === 'bookings' ? 'block' : 'none';
    document.getElementById('worker-compliance').style.display = tab === 'compliance' ? 'block' : 'none';
}

// Facility Dashboard
async function showFacilityDashboard() {
    showScreen('facility-screen');
    document.getElementById('facility-name').textContent = `Welcome, ${currentUser.name}`;
    
    await loadFacilityShifts();
    await loadFacilityBookings();
}

async function loadFacilityShifts() {
    try {
        const data = await apiCall('/api/shifts');
        const container = document.getElementById('facility-shifts-list');
        
        const myShifts = data.shifts.filter(s => s.facilityId === currentUser.id);
        
        if (myShifts.length === 0) {
            container.innerHTML = '<div class="empty-state">📋<p>No shifts posted yet. Create your first shift!</p></div>';
            return;
        }

        container.innerHTML = myShifts.map(shift => `
            <div class="shift-card">
                <h4>${shift.title}</h4>
                <p>${shift.description || 'No description'}</p>
                <div class="shift-meta">
                    <span>📅 ${new Date(shift.startTime).toLocaleString()}</span>
                    <span>⏰ ${calculateDuration(shift.startTime, shift.endTime)} hours</span>
                    <span class="badge badge-status">${shift.status}</span>
                </div>
                <div>
                    ${shift.requiredSkills.map(s => `<span class="badge badge-skill">${s}</span>`).join('')}
                </div>
                <div style="margin-top: 10px;">
                    <span class="price">£${shift.dynamicRate}/hr</span>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading shifts:', error);
    }
}

document.getElementById('create-shift-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const title = document.getElementById('shift-title').value;
    const description = document.getElementById('shift-description').value;
    const startTime = document.getElementById('shift-start').value;
    const endTime = document.getElementById('shift-end').value;
    const skills = document.getElementById('shift-skills').value.split(',').map(s => s.trim());
    const baseRate = parseFloat(document.getElementById('shift-rate').value);
    const skillLevel = document.getElementById('shift-skill-level').value;

    try {
        await apiCall('/api/shifts', {
            method: 'POST',
            body: JSON.stringify({
                title,
                description,
                startTime,
                endTime,
                requiredSkills: skills,
                location: { lat: 51.5074, lng: -0.1278 },
                baseRate,
                skillLevel
            })
        });

        alert('Shift created successfully!');
        e.target.reset();
        await loadFacilityShifts();
        switchFacilityTab('shifts');
    } catch (error) {
        alert(error.message);
    }
});

async function loadFacilityBookings() {
    try {
        const data = await apiCall('/api/bookings');
        const container = document.getElementById('facility-bookings-list');
        
        if (!data.bookings || data.bookings.length === 0) {
            container.innerHTML = '<div class="empty-state">📋<p>No applications yet</p></div>';
            return;
        }

        container.innerHTML = data.bookings.map(booking => `
            <div class="booking-card">
                <h4>${booking.shift?.title || 'Shift'}</h4>
                <p>Worker: ${booking.worker?.name || 'Unknown'}</p>
                <div class="booking-meta">
                    <span>⭐ ${booking.worker?.rating || 'N/A'}</span>
                    <span>📅 ${booking.shift?.startTime ? new Date(booking.shift.startTime).toLocaleString() : 'N/A'}</span>
                    <span class="badge badge-${booking.status}">${booking.status}</span>
                </div>
                <div style="margin-top: 10px;">
                    ${booking.worker?.skills?.map(s => `<span class="badge badge-skill">${s}</span>`).join('') || ''}
                </div>
                ${booking.status === 'pending' ? `
                    <button class="btn btn-success" style="margin-top: 10px;" onclick="confirmBooking('${booking.id}')">Confirm Booking</button>
                ` : ''}
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading bookings:', error);
    }
}

async function confirmBooking(bookingId) {
    try {
        await apiCall(`/api/bookings/${bookingId}/confirm`, { method: 'PUT' });
        alert('Booking confirmed!');
        await loadFacilityBookings();
        await loadFacilityShifts();
    } catch (error) {
        alert(error.message);
    }
}

function switchFacilityTab(tab, event) {
    document.querySelectorAll('#facility-screen .tab').forEach(t => t.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    document.getElementById('facility-shifts').style.display = tab === 'shifts' ? 'block' : 'none';
    document.getElementById('facility-create').style.display = tab === 'create' ? 'block' : 'none';
    document.getElementById('facility-bookings').style.display = tab === 'bookings' ? 'block' : 'none';
}

// Utility
function calculateDuration(start, end) {
    const diff = new Date(end) - new Date(start);
    return Math.round(diff / (1000 * 60 * 60));
}
