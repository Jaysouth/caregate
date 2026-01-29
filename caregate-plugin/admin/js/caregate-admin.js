/**
 * CareGate Admin JavaScript
 * 
 * Handles all admin dashboard functionality including:
 * - Real-time updates
 * - Data loading and filtering
 * - Form submissions
 * - AJAX requests
 * - UI interactions
 */

(function($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    $(document).ready(function() {
        // Initialize components
        initDashboardStats();
        initDataTables();
        initFormHandlers();
        initAjaxActions();
        initAutoRefresh();
        initNotifications();
        
        console.log('CareGate Admin initialized');
    });

    /**
     * Dashboard Statistics
     */
    function initDashboardStats() {
        if ($('.caregate-stats-card').length) {
            loadDashboardStats();
            
            // Refresh stats every 60 seconds
            setInterval(loadDashboardStats, 60000);
        }
    }

    function loadDashboardStats() {
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_get_dashboard_stats',
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateStatsDisplay(response.data);
                }
            }
        });
    }

    function updateStatsDisplay(stats) {
        if (stats.total_users) $('#stat-users').text(stats.total_users);
        if (stats.total_shifts) $('#stat-shifts').text(stats.total_shifts);
        if (stats.total_bookings) $('#stat-bookings').text(stats.total_bookings);
        if (stats.workers_clocked_in) $('#stat-clocked-in').text(stats.workers_clocked_in);
    }

    /**
     * Data Tables Initialization
     */
    function initDataTables() {
        // User Management Table
        if ($('#users-table').length) {
            initUsersTable();
        }

        // Shifts Table
        if ($('#shifts-table').length) {
            initShiftsTable();
        }

        // Bookings Table
        if ($('#bookings-table').length) {
            initBookingsTable();
        }

        // Clock Records Table
        if ($('#clock-records-table').length) {
            initClockRecordsTable();
        }
    }

    function initUsersTable() {
        // Filter by role
        $('#user-role-filter').on('change', function() {
            var role = $(this).val();
            filterUsers(role);
        });

        // Search functionality
        $('#user-search').on('keyup', debounce(function() {
            var search = $(this).val();
            searchUsers(search);
        }, 300));
    }

    function filterUsers(role) {
        var $rows = $('#users-table tbody tr');
        
        if (role === 'all') {
            $rows.show();
        } else {
            $rows.hide();
            $rows.filter('[data-role="' + role + '"]').show();
        }
    }

    function searchUsers(search) {
        var $rows = $('#users-table tbody tr');
        
        if (search === '') {
            $rows.show();
        } else {
            $rows.hide();
            $rows.filter(function() {
                var text = $(this).text().toLowerCase();
                return text.indexOf(search.toLowerCase()) > -1;
            }).show();
        }
    }

    function initShiftsTable() {
        // Filter by status
        $('#shift-status-filter').on('change', function() {
            var status = $(this).val();
            filterShifts(status);
        });
    }

    function filterShifts(status) {
        var $rows = $('#shifts-table tbody tr');
        
        if (status === 'all') {
            $rows.show();
        } else {
            $rows.hide();
            $rows.filter('[data-status="' + status + '"]').show();
        }
    }

    function initBookingsTable() {
        // Booking actions
        $('.approve-booking').on('click', function(e) {
            e.preventDefault();
            var bookingId = $(this).data('booking-id');
            approveBooking(bookingId);
        });

        $('.reject-booking').on('click', function(e) {
            e.preventDefault();
            var bookingId = $(this).data('booking-id');
            rejectBooking(bookingId);
        });
    }

    function initClockRecordsTable() {
        // Auto-refresh clock records every 30 seconds
        setInterval(function() {
            if ($('#clock-records-table').length) {
                loadClockRecords();
            }
        }, 30000);
    }

    /**
     * Form Handlers
     */
    function initFormHandlers() {
        // Invoice form
        $('#create-invoice-form').on('submit', function(e) {
            e.preventDefault();
            submitInvoiceForm($(this));
        });

        // Payroll form
        $('#payroll-form').on('submit', function(e) {
            e.preventDefault();
            submitPayrollForm($(this));
        });

        // Clock entry form
        $('#clock-entry-form').on('submit', function(e) {
            e.preventDefault();
            submitClockEntry($(this));
        });

        // Settings form
        $('#caregate-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings($(this));
        });
    }

    function submitInvoiceForm($form) {
        var formData = $form.serialize();
        
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: formData + '&action=caregate_create_invoice&nonce=' + careGateAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    showNotification('Invoice created successfully', 'success');
                    $form[0].reset();
                } else {
                    showNotification(response.data.message || 'Error creating invoice', 'error');
                }
            },
            error: function() {
                showNotification('Server error. Please try again.', 'error');
            }
        });
    }

    function submitPayrollForm($form) {
        var formData = $form.serialize();
        
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: formData + '&action=caregate_process_payroll&nonce=' + careGateAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    showNotification('Payroll processed successfully', 'success');
                    displayPayrollResult(response.data);
                } else {
                    showNotification(response.data.message || 'Error processing payroll', 'error');
                }
            },
            error: function() {
                showNotification('Server error. Please try again.', 'error');
            }
        });
    }

    function submitClockEntry($form) {
        var formData = $form.serialize();
        
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: formData + '&action=caregate_manual_clock_entry&nonce=' + careGateAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    showNotification('Clock entry recorded successfully', 'success');
                    $form[0].reset();
                    loadClockRecords();
                } else {
                    showNotification(response.data.message || 'Error recording clock entry', 'error');
                }
            },
            error: function() {
                showNotification('Server error. Please try again.', 'error');
            }
        });
    }

    function saveSettings($form) {
        var formData = $form.serialize();
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: formData + '&action=caregate_save_settings&nonce=' + careGateAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    showNotification('Settings saved successfully', 'success');
                } else {
                    showNotification(response.data.message || 'Error saving settings', 'error');
                }
            },
            error: function() {
                showNotification('Server error. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * AJAX Actions
     */
    function initAjaxActions() {
        // Approve timesheet
        $(document).on('click', '.approve-timesheet', function(e) {
            e.preventDefault();
            var timesheetId = $(this).data('timesheet-id');
            approveTimesheet(timesheetId);
        });

        // Reject timesheet
        $(document).on('click', '.reject-timesheet', function(e) {
            e.preventDefault();
            var timesheetId = $(this).data('timesheet-id');
            rejectTimesheet(timesheetId);
        });

        // Send invoice
        $(document).on('click', '.send-invoice', function(e) {
            e.preventDefault();
            var invoiceId = $(this).data('invoice-id');
            sendInvoice(invoiceId);
        });

        // Mark invoice as paid
        $(document).on('click', '.mark-paid', function(e) {
            e.preventDefault();
            var invoiceId = $(this).data('invoice-id');
            markInvoiceAsPaid(invoiceId);
        });
    }

    function approveBooking(bookingId) {
        if (!confirm('Are you sure you want to approve this booking?')) {
            return;
        }

        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_approve_booking',
                booking_id: bookingId,
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Booking approved successfully', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error approving booking', 'error');
                }
            }
        });
    }

    function rejectBooking(bookingId) {
        if (!confirm('Are you sure you want to reject this booking?')) {
            return;
        }

        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_reject_booking',
                booking_id: bookingId,
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Booking rejected', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error rejecting booking', 'error');
                }
            }
        });
    }

    function approveTimesheet(timesheetId) {
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_approve_timesheet',
                timesheet_id: timesheetId,
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Timesheet approved successfully', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error approving timesheet', 'error');
                }
            }
        });
    }

    function rejectTimesheet(timesheetId) {
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_reject_timesheet',
                timesheet_id: timesheetId,
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Timesheet rejected', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error rejecting timesheet', 'error');
                }
            }
        });
    }

    function sendInvoice(invoiceId) {
        if (!confirm('Send this invoice to the facility?')) {
            return;
        }

        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_send_invoice',
                invoice_id: invoiceId,
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Invoice sent successfully', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error sending invoice', 'error');
                }
            }
        });
    }

    function markInvoiceAsPaid(invoiceId) {
        if (!confirm('Mark this invoice as paid?')) {
            return;
        }

        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_mark_invoice_paid',
                invoice_id: invoiceId,
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Invoice marked as paid', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error updating invoice', 'error');
                }
            }
        });
    }

    /**
     * Auto-refresh functionality
     */
    function initAutoRefresh() {
        // Auto-refresh dashboard every 60 seconds
        if ($('.caregate-dashboard').length) {
            setInterval(function() {
                loadDashboardStats();
            }, 60000);
        }
    }

    function loadClockRecords() {
        $.ajax({
            url: careGateAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'caregate_get_clock_records',
                nonce: careGateAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateClockRecordsTable(response.data);
                }
            }
        });
    }

    function updateClockRecordsTable(records) {
        var $tbody = $('#clock-records-table tbody');
        $tbody.empty();

        if (records.length === 0) {
            $tbody.append('<tr><td colspan="6" style="text-align:center;">No clock records found</td></tr>');
            return;
        }

        records.forEach(function(record) {
            var row = '<tr>' +
                '<td>' + record.worker_name + '</td>' +
                '<td>' + record.facility_name + '</td>' +
                '<td>' + record.clock_in_time + '</td>' +
                '<td>' + (record.clock_out_time || 'In Progress') + '</td>' +
                '<td>' + (record.total_hours || '-') + '</td>' +
                '<td>' + getStatusBadge(record.status) + '</td>' +
                '</tr>';
            $tbody.append(row);
        });
    }

    /**
     * Notifications
     */
    function initNotifications() {
        // Close notification
        $(document).on('click', '.caregate-notification .close', function() {
            $(this).parent().fadeOut();
        });
    }

    function showNotification(message, type) {
        type = type || 'info';
        
        var notification = $('<div class="caregate-notification ' + type + '">' +
            '<span class="message">' + message + '</span>' +
            '<button class="close">&times;</button>' +
            '</div>');
        
        $('body').append(notification);
        
        notification.fadeIn();
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Utility Functions
     */
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    function displayPayrollResult(data) {
        var $result = $('#payroll-result');
        if ($result.length) {
            var html = '<div class="payroll-summary">' +
                '<h3>Payroll Summary</h3>' +
                '<table>' +
                '<tr><td>Gross Pay:</td><td>£' + data.gross_pay + '</td></tr>' +
                '<tr><td>Income Tax:</td><td>£' + data.income_tax + '</td></tr>' +
                '<tr><td>National Insurance:</td><td>£' + data.national_insurance + '</td></tr>' +
                '<tr><td>Pension:</td><td>£' + data.pension + '</td></tr>' +
                '<tr><td>Agency Fees:</td><td>£' + data.agency_fees + '</td></tr>' +
                '<tr class="total"><td><strong>Net Pay:</strong></td><td><strong>£' + data.net_pay + '</strong></td></tr>' +
                '</table>' +
                '</div>';
            $result.html(html).show();
        }
    }

    function getStatusBadge(status) {
        var badges = {
            'in_progress': '<span class="status-badge in-progress">In Progress</span>',
            'completed': '<span class="status-badge completed">Completed</span>',
            'approved': '<span class="status-badge approved">Approved</span>',
            'pending': '<span class="status-badge pending">Pending</span>'
        };
        return badges[status] || status;
    }

})(jQuery);
