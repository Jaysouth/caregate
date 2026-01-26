    <div id="app">
        <!-- Login/Register Screen -->
        <div id="auth-screen" class="screen active">
            <div class="container">
                <div class="logo">
                    <h1>🏥 CareGate</h1>
                    <p>On-Demand Care Staffing Platform</p>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="showTab('login', event)">Login</button>
                    <button class="tab" onclick="showTab('register', event)">Register</button>
                </div>

                <!-- Login Form -->
                <form id="login-form" class="auth-form">
                    <h2>Login</h2>
                    <input type="email" id="login-email" placeholder="Email" required>
                    <input type="password" id="login-password" placeholder="Password" required>
                    <button type="submit" class="btn btn-primary">Login</button>
                    <p class="demo-info">Demo: worker@test.com / password123</p>
                </form>

                <!-- Register Form -->
                <form id="register-form" class="auth-form" style="display: none;">
                    <h2>Register</h2>
                    <input type="text" id="reg-name" placeholder="Full Name" required>
                    <input type="email" id="reg-email" placeholder="Email" required>
                    <input type="password" id="reg-password" placeholder="Password" required>
                    
                    <label>I am a:</label>
                    <select id="reg-role" onchange="toggleRoleFields()">
                        <option value="worker">Healthcare Worker</option>
                        <option value="facility">Care Facility</option>
                    </select>

                    <div id="worker-fields">
                        <input type="text" id="reg-skills" placeholder="Skills (comma-separated)" value="Nursing, Elderly Care">
                    </div>

                    <div id="facility-fields" style="display: none;">
                        <input type="text" id="reg-facility-type" placeholder="Facility Type" value="Care Home">
                    </div>

                    <button type="submit" class="btn btn-primary">Register</button>
                </form>

                <div id="auth-error" class="error-message"></div>
            </div>
        </div>

        <!-- Worker Dashboard -->
        <div id="worker-screen" class="screen">
            <nav class="navbar">
                <h2>CareGate - Worker</h2>
                <button onclick="logout()" class="btn btn-small">Logout</button>
            </nav>
            
            <div class="container">
                <div class="welcome">
                    <h3 id="worker-name">Welcome</h3>
                    <div class="stats">
                        <div class="stat">
                            <span class="stat-value" id="worker-rating">5.0</span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value" id="worker-shifts">0</span>
                            <span class="stat-label">Shifts</span>
                        </div>
                    </div>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="switchWorkerTab('matches', event)">Matched Shifts</button>
                    <button class="tab" onclick="switchWorkerTab('bookings', event)">My Bookings</button>
                    <button class="tab" onclick="switchWorkerTab('compliance', event)">Compliance</button>
                </div>

                <div id="worker-matches" class="tab-content">
                    <h3>Matched Shifts for You</h3>
                    <div id="matches-list" class="shifts-list"></div>
                </div>

                <div id="worker-bookings" class="tab-content" style="display: none;">
                    <h3>My Bookings</h3>
                    <div id="bookings-list" class="bookings-list"></div>
                </div>

                <div id="worker-compliance" class="tab-content" style="display: none;">
                    <h3>Compliance Status</h3>
                    <div id="compliance-info" class="compliance-info"></div>
                </div>
            </div>
        </div>

        <!-- Facility Dashboard -->
        <div id="facility-screen" class="screen">
            <nav class="navbar">
                <h2>CareGate - Facility</h2>
                <button onclick="logout()" class="btn btn-small">Logout</button>
            </nav>
            
            <div class="container">
                <div class="welcome">
                    <h3 id="facility-name">Welcome</h3>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="switchFacilityTab('shifts', event)">My Shifts</button>
                    <button class="tab" onclick="switchFacilityTab('create', event)">Create Shift</button>
                    <button class="tab" onclick="switchFacilityTab('bookings', event)">Applications</button>
                </div>

                <div id="facility-shifts" class="tab-content">
                    <h3>My Posted Shifts</h3>
                    <div id="facility-shifts-list" class="shifts-list"></div>
                </div>

                <div id="facility-create" class="tab-content" style="display: none;">
                    <h3>Create New Shift</h3>
                    <form id="create-shift-form">
                        <input type="text" id="shift-title" placeholder="Shift Title" required>
                        <textarea id="shift-description" placeholder="Description" rows="3"></textarea>
                        <label>Start Time</label>
                        <input type="datetime-local" id="shift-start" required>
                        <label>End Time</label>
                        <input type="datetime-local" id="shift-end" required>
                        <input type="text" id="shift-skills" placeholder="Required Skills (comma-separated)" value="Nursing, Elderly Care">
                        <input type="number" id="shift-rate" placeholder="Base Rate (£/hour)" step="0.01" value="20.00" required>
                        <label>Skill Level</label>
                        <select id="shift-skill-level">
                            <option value="basic">Basic</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Create Shift</button>
                    </form>
                </div>

                <div id="facility-bookings" class="tab-content" style="display: none;">
                    <h3>Shift Applications</h3>
                    <div id="facility-bookings-list" class="bookings-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
