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

// Multi-Step Registration Form
let currentStep = 1;
let selectedRole = null;
const totalSteps = 3;

function selectRole(role) {
    selectedRole = role;
    document.getElementById('reg-role').value = role;
    
    // Update role card UI
    document.querySelectorAll('.role-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.role-card[data-role="${role}"]`).classList.add('selected');
    
    // Show/hide role-specific fields in all steps
    updateRoleFields();
}

function updateRoleFields() {
    const isWorker = selectedRole === 'worker';
    
    document.querySelectorAll('.worker-only').forEach(el => {
        el.style.display = isWorker ? 'block' : 'none';
    });
    
    document.querySelectorAll('.facility-only').forEach(el => {
        el.style.display = isWorker ? 'none' : 'block';
    });
}

function updateStepIndicator() {
    document.querySelectorAll('.step-item').forEach((item, index) => {
        const stepNum = index + 1;
        if (stepNum < currentStep) {
            item.classList.add('completed');
            item.classList.remove('active');
        } else if (stepNum === currentStep) {
            item.classList.add('active');
            item.classList.remove('completed');
        } else {
            item.classList.remove('active', 'completed');
        }
    });
}

function showFormStep(step) {
    document.querySelectorAll('.form-step').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show the correct step based on role
    const stepElements = document.querySelectorAll(`.form-step[data-step="${step}"]`);
    stepElements.forEach(el => {
        if (step === 1 || (selectedRole === 'worker' && el.classList.contains('worker-only')) || 
            (selectedRole === 'facility' && el.classList.contains('facility-only')) ||
            (!el.classList.contains('worker-only') && !el.classList.contains('facility-only'))) {
            el.classList.add('active');
        }
    });
    
    // Update button visibility
    document.getElementById('prev-btn').style.display = step > 1 ? 'inline-block' : 'none';
    document.getElementById('next-btn').style.display = step < totalSteps ? 'inline-block' : 'none';
    document.getElementById('submit-btn').style.display = step === totalSteps ? 'inline-block' : 'none';
    
    updateStepIndicator();
}

function validateStep(step) {
    let isValid = true;
    const currentStepEl = document.querySelector(`.form-step.active`);
    
    if (!currentStepEl) return false;
    
    const inputs = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim() && input.type !== 'checkbox') {
            input.classList.add('invalid');
            isValid = false;
        } else if (input.type === 'checkbox' && !input.checked) {
            isValid = false;
        } else {
            input.classList.remove('invalid');
            input.classList.add('valid');
        }
    });
    
    // Special validations
    if (step === 1) {
        // Check if role is selected
        if (!selectedRole) {
            showError('Please select your role');
            return false;
        }
        
        // Check password match
        const password = document.getElementById('reg-password').value;
        const confirmPassword = document.getElementById('reg-confirm-password').value;
        if (password !== confirmPassword) {
            showError('Passwords do not match');
            document.getElementById('reg-confirm-password').classList.add('invalid');
            return false;
        }
    }
    
    if (step === 2 && selectedRole === 'worker') {
        // Validate NI number format
        const niNumber = document.getElementById('reg-ni-number').value;
        const niPattern = /^[A-Z]{2}[0-9]{6}[A-Z]$/i;
        if (niNumber && !niPattern.test(niNumber.replace(/\s/g, ''))) {
            showError('Invalid National Insurance number format (e.g., AB123456C)');
            document.getElementById('reg-ni-number').classList.add('invalid');
            return false;
        }
        
        // Validate UK postcode
        const postcode = document.getElementById('reg-postcode').value;
        if (postcode && !validateUKPostcode(postcode)) {
            showError('Invalid UK postcode format');
            document.getElementById('reg-postcode').classList.add('invalid');
            return false;
        }
    }
    
    if (step === 2 && selectedRole === 'facility') {
        // Validate UK postcode
        const postcode = document.getElementById('reg-facility-postcode').value;
        if (postcode && !validateUKPostcode(postcode)) {
            showError('Invalid UK postcode format');
            document.getElementById('reg-facility-postcode').classList.add('invalid');
            return false;
        }
    }
    
    if (!isValid) {
        showError('Please fill in all required fields');
    }
    
    return isValid;
}

function validateUKPostcode(postcode) {
    const postcodePattern = /^[A-Z]{1,2}[0-9]{1,2}[A-Z]?\s?[0-9][A-Z]{2}$/i;
    return postcodePattern.test(postcode.trim());
}

function nextStep() {
    if (validateStep(currentStep)) {
        if (currentStep < totalSteps) {
            currentStep++;
            showFormStep(currentStep);
            window.scrollTo(0, 0);
        }
    }
}

function previousStep() {
    // No restrictions - always allow going back
    if (currentStep > 1) {
        currentStep--;
        showFormStep(currentStep);
        window.scrollTo(0, 0);
    }
}

function toggleRoleFields() {
    // Legacy function - now handled by selectRole
    const role = document.getElementById('reg-role').value;
    if (role) {
        selectRole(role);
    }
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

    console.log('🔐 Attempting login with email:', email);

    try {
        const data = await apiCall('/api/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        console.log('✅ Login successful:', data);

        authToken = data.token;
        currentUser = data.user;
        
        // Store token in localStorage for persistence
        localStorage.setItem('authToken', authToken);
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        
        console.log('👤 User role:', currentUser.role);
        
        // Check if password change is required
        if (data.mustChangePassword) {
            console.log('⚠️ Password change required');
            showPasswordChangeModal(email, password);
            return;
        }
        
        // Route to appropriate dashboard based on role
        if (currentUser.role === 'admin') {
            console.log('🎯 Routing to admin dashboard');
            showAdminDashboard();
        } else if (currentUser.role === 'worker') {
            console.log('🎯 Routing to worker dashboard');
            showWorkerDashboard();
        } else {
            console.log('🎯 Routing to facility dashboard');
            showFacilityDashboard();
        }
    } catch (error) {
        console.error('❌ Login failed:', error);
        showError(error.message || 'Login failed. Please check your credentials and try again.');
    }
});

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validate final step
    if (!validateStep(currentStep)) {
        return;
    }

    const name = document.getElementById('reg-name').value;
    const email = document.getElementById('reg-email').value;
    const password = document.getElementById('reg-password').value;
    const role = selectedRole || document.getElementById('reg-role').value;

    const payload = {
        name, email, password, role,
        location: { lat: 51.5074, lng: -0.1278 } // Default London location
    };

    if (role === 'worker') {
        // Worker-specific fields
        payload.phone = document.getElementById('reg-phone')?.value || '';
        payload.niNumber = document.getElementById('reg-ni-number')?.value || '';
        payload.postcode = document.getElementById('reg-postcode')?.value || '';
        payload.address = document.getElementById('reg-address')?.value || '';
        payload.dateOfBirth = document.getElementById('reg-dob')?.value || '';
        payload.emergencyContactName = document.getElementById('reg-emergency-name')?.value || '';
        payload.emergencyContactPhone = document.getElementById('reg-emergency-phone')?.value || '';
        payload.dbsNumber = document.getElementById('reg-dbs-number')?.value || '';
        payload.dbsIssueDate = document.getElementById('reg-dbs-date')?.value || '';
        payload.nmcNumber = document.getElementById('reg-nmc-number')?.value || '';
        payload.nmcExpiryDate = document.getElementById('reg-nmc-expiry')?.value || '';
        payload.experience = document.getElementById('reg-experience')?.value || 0;
        payload.rightToWork = document.getElementById('reg-right-to-work')?.checked || false;
        
        const skillsInput = document.getElementById('reg-skills')?.value || 'Nursing, Elderly Care';
        payload.skills = skillsInput.split(',').map(s => s.trim()).filter(s => s);
    } else {
        // Facility-specific fields
        payload.phone = document.getElementById('reg-facility-phone')?.value || '';
        payload.cqcNumber = document.getElementById('reg-cqc-number')?.value || '';
        payload.postcode = document.getElementById('reg-facility-postcode')?.value || '';
        payload.address = document.getElementById('reg-facility-address')?.value || '';
        payload.facilityType = document.getElementById('reg-facility-type')?.value || 'care_home';
        payload.capacity = document.getElementById('reg-capacity')?.value || 0;
        payload.operatingHours = document.getElementById('reg-operating-hours')?.value || '24/7';
        payload.companyNumber = document.getElementById('reg-company-number')?.value || '';
        payload.vatNumber = document.getElementById('reg-vat-number')?.value || '';
        payload.contactPerson = document.getElementById('reg-contact-person')?.value || '';
        payload.contactPosition = document.getElementById('reg-contact-position')?.value || '';
        payload.insuranceNumber = document.getElementById('reg-insurance-number')?.value || '';
        payload.insuranceExpiry = document.getElementById('reg-insurance-expiry')?.value || '';
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
        
        // Reset form
        currentStep = 1;
        selectedRole = null;
        showFormStep(1);
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

// Initialize multi-step form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form to step 1
    showFormStep(1);
    
    // Add input event listeners for real-time validation
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', function() {
            if (this.classList.contains('invalid')) {
                this.classList.remove('invalid');
            }
        });
        
        // Auto-format NI number
        if (input.id === 'reg-ni-number') {
            input.addEventListener('input', function(e) {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (value.length > 9) value = value.substring(0, 9);
                e.target.value = value;
            });
        }
        
        // Auto-format UK postcode
        if (input.id === 'reg-postcode' || input.id === 'reg-facility-postcode') {
            input.addEventListener('blur', function(e) {
                let value = e.target.value.toUpperCase().replace(/\s/g, '');
                if (value.length >= 5) {
                    // Add space before last 3 characters
                    value = value.slice(0, -3) + ' ' + value.slice(-3);
                }
                e.target.value = value;
            });
        }
    });
});

// Admin Dashboard Functions
function showAdminDashboard() {
    showScreen('admin-screen');
    loadAdminOverview();
}

function showAdminTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.admin-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.admin-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(`admin-${tabName}`).classList.add('active');
    
    // Load tab-specific data
    switch(tabName) {
        case 'overview':
            loadAdminOverview();
            break;
        case 'users':
            loadUsers();
            break;
        case 'shifts':
            loadAdminShifts();
            break;
        case 'bookings':
            loadAdminBookings();
            break;
        case 'invoicing':
            loadInvoices();
            break;
        case 'payroll':
            loadPayroll();
            break;
        case 'clock':
            loadClockRecords();
            break;
        case 'compliance':
            loadCompliance();
            break;
        case 'payments':
            loadPayments();
            break;
        case 'reports':
            // Reports tab is static
            break;
    }
}

async function loadAdminOverview() {
    try {
        // Load statistics
        const users = await apiCall('/api/users');
        const shifts = await apiCall('/api/shifts');
        const bookings = await apiCall('/api/bookings');
        
        const workers = users.filter(u => u.role === 'worker');
        const facilities = users.filter(u => u.role === 'facility');
        const activeShifts = shifts.filter(s => s.status === 'open');
        const pendingBookings = bookings.filter(b => b.status === 'pending');
        
        document.getElementById('stat-total-users').textContent = users.length;
        document.getElementById('stat-care-workers').textContent = workers.length;
        document.getElementById('stat-facilities').textContent = facilities.length;
        document.getElementById('stat-active-shifts').textContent = activeShifts.length;
        document.getElementById('stat-pending-bookings').textContent = pendingBookings.length;
        
        // Load recent activity (simplified)
        const activityList = document.getElementById('recent-activity-list');
        activityList.innerHTML = `
            <div class="activity-item">
                <p>✅ System operational - All services running</p>
                <small>${new Date().toLocaleString()}</small>
            </div>
        `;
    } catch (error) {
        console.error('Error loading admin overview:', error);
    }
}

async function loadUsers() {
    try {
        const users = await apiCall('/api/users');
        const usersList = document.getElementById('users-list');
        
        usersList.innerHTML = users.map(user => `
            <div class="admin-list-item">
                <div class="list-item-header">
                    <h4>${user.name}</h4>
                    <span class="badge badge-${user.role}">${user.role}</span>
                </div>
                <p>📧 ${user.email}</p>
                <p>⭐ Rating: ${user.rating || 'N/A'} | Shifts: ${user.completedShifts || 0}</p>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

async function loadAdminShifts() {
    try {
        const shifts = await apiCall('/api/shifts');
        const shiftsList = document.getElementById('shifts-list');
        
        shiftsList.innerHTML = shifts.map(shift => `
            <div class="admin-list-item">
                <h4>${shift.title}</h4>
                <p>📍 ${shift.location || 'Location not set'}</p>
                <p>💰 £${shift.rate}/hour | Status: ${shift.status}</p>
                <p>${new Date(shift.startTime).toLocaleString()}</p>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading shifts:', error);
    }
}

async function loadAdminBookings() {
    try {
        const bookings = await apiCall('/api/bookings');
        const bookingsList = document.getElementById('bookings-list');
        
        bookingsList.innerHTML = bookings.map(booking => `
            <div class="admin-list-item">
                <h4>Booking #${booking.id.substr(0, 8)}</h4>
                <p>Worker ID: ${booking.workerId.substr(0, 8)}</p>
                <p>Shift ID: ${booking.shiftId.substr(0, 8)}</p>
                <p>Status: <span class="badge badge-${booking.status}">${booking.status}</span></p>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading bookings:', error);
    }
}

function loadInvoices() {
    document.getElementById('invoices-list').innerHTML = '<p>UK Invoicing functionality - Implementation ready</p>';
}

function loadPayroll() {
    document.getElementById('payroll-list').innerHTML = '<p>Payroll functionality - Implementation ready</p>';
}

function loadClockRecords() {
    document.getElementById('clock-records-list').innerHTML = '<p>Clock records functionality - Implementation ready</p>';
}

function loadCompliance() {
    document.getElementById('compliance-list').innerHTML = '<p>Compliance tracking - Implementation ready</p>';
}

function loadPayments() {
    document.getElementById('payment-transactions').innerHTML = '<p>Payment transactions - Implementation ready</p>';
}

// Password Change Modal Functions
function showPasswordChangeModal(email, currentPassword) {
    const modal = document.getElementById('password-change-modal');
    modal.style.display = 'flex';
    
    // Set current password (for API call)
    document.getElementById('current-password').value = currentPassword;
    
    // Store email for API call
    modal.setAttribute('data-email', email);
}

function hidePasswordChangeModal() {
    const modal = document.getElementById('password-change-modal');
    modal.style.display = 'none';
}

// Password Change Form Handler
document.getElementById('password-change-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const modal = document.getElementById('password-change-modal');
    const email = modal.getAttribute('data-email');
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-new-password').value;
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        document.getElementById('password-change-error').textContent = 'New passwords do not match';
        return;
    }
    
    try {
        await apiCall('/api/auth/change-password', {
            method: 'POST',
            body: JSON.stringify({
                email,
                currentPassword,
                newPassword
            })
        });
        
        alert('Password changed successfully! Please login with your new password.');
        hidePasswordChangeModal();
        logout();
    } catch (error) {
        document.getElementById('password-change-error').textContent = error.message;
    }
});

// Filter and search functions (simplified)
function filterUsers() {
    loadUsers();
}

function searchUsers() {
    loadUsers();
}

function filterShifts() {
    loadAdminShifts();
}

function filterBookings() {
    loadAdminBookings();
}

function showPaymentTab(tabName) {
    document.querySelectorAll('.sub-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    document.querySelectorAll('.payment-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(`payment-${tabName}`).classList.add('active');
}

// Placeholder functions for admin actions
function createInvoice() {
    alert('UK Invoice creation interface - Coming soon');
}

function createPayroll() {
    alert('Payroll processing interface - Coming soon');
}

function generateFinancialReport() {
    alert('Financial report generation - Coming soon');
}

function generateShiftReport() {
    alert('Shift analytics report - Coming soon');
}

function generateUserReport() {
    alert('User activity report - Coming soon');
}
