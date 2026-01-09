<?php
/**
 * Barron Production Management System
 * Production Schedule View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

// Check permissions
if (!checkPermission('planning.view')) {
    header('Location: /pages/dashboard.php?error=access_denied');
    exit;
}

$page_title = 'Production Schedule';
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> Production Schedule</h1>
    <div class="header-actions">
        <div class="view-switcher">
            <button class="active" data-view="week">Week</button>
            <button data-view="month">Month</button>
        </div>
        <?php if (checkPermission('planning.create')): ?>
            <button class="btn btn-primary" onclick="createQuickJob()">
                <i class="fas fa-plus"></i> Schedule Job
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Calendar Controls -->
<div class="card">
    <div class="card-body">
        <div class="calendar-controls">
            <button class="btn btn-secondary" onclick="previousPeriod()">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <h3 id="currentPeriodLabel"></h3>
            <button class="btn btn-secondary" onclick="nextPeriod()">
                Next <i class="fas fa-chevron-right"></i>
            </button>
            <button class="btn btn-outline" onclick="goToToday()">
                Today
            </button>
        </div>
    </div>
</div>

<!-- Department Filter -->
<div class="card mt-20">
    <div class="card-body">
        <div class="department-filter">
            <label>Filter by Department:</label>
            <select id="departmentFilter" onchange="loadSchedule()">
                <option value="">All Departments</option>
                <!-- Will be populated dynamically -->
            </select>
        </div>
    </div>
</div>

<!-- Schedule Calendar -->
<div class="card mt-20">
    <div class="card-body">
        <div id="scheduleCalendar" class="schedule-calendar">
            <div class="loading-spinner"></div>
            Loading schedule...
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card mt-20">
    <div class="card-body">
        <div class="schedule-legend">
            <h4>Legend:</h4>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="badge badge-scheduled"></span> Scheduled
                </div>
                <div class="legend-item">
                    <span class="badge badge-in_progress"></span> In Progress
                </div>
                <div class="legend-item">
                    <span class="badge badge-completed"></span> Completed
                </div>
                <div class="legend-item">
                    <span class="badge badge-on_hold"></span> On Hold
                </div>
                <div class="legend-item">
                    <span class="capacity-indicator capacity-high"></span> High Capacity (>80%)
                </div>
                <div class="legend-item">
                    <span class="capacity-indicator capacity-overbooked"></span> Overbooked (>100%)
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = new Date();
let currentView = 'week';
let selectedDepartment = '';

document.addEventListener('DOMContentLoaded', function() {
    loadDepartments();
    loadSchedule();
});

function loadDepartments() {
    fetch('/api/master/departments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('departmentFilter');
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.department_name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading departments:', error));
}

function loadSchedule() {
    selectedDepartment = document.getElementById('departmentFilter').value;
    
    const { startDate, endDate } = getDateRange();
    
    const params = new URLSearchParams({
        date_from: startDate,
        date_to: endDate
    });
    
    if (selectedDepartment) {
        params.append('department_id', selectedDepartment);
    }
    
    fetch(`/api/planning/jobs.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSchedule(data.jobs, startDate, endDate);
                updatePeriodLabel();
            }
        })
        .catch(error => {
            console.error('Error loading schedule:', error);
            showAlert('Failed to load schedule', 'error');
        });
}

function getDateRange() {
    const start = new Date(currentDate);
    const end = new Date(currentDate);
    
    if (currentView === 'week') {
        // Get Monday of the current week
        const day = start.getDay();
        const diff = start.getDate() - day + (day === 0 ? -6 : 1);
        start.setDate(diff);
        start.setHours(0, 0, 0, 0);
        
        end.setDate(start.getDate() + 6);
        end.setHours(23, 59, 59, 999);
    } else {
        // Month view
        start.setDate(1);
        start.setHours(0, 0, 0, 0);
        
        end.setMonth(start.getMonth() + 1);
        end.setDate(0);
        end.setHours(23, 59, 59, 999);
    }
    
    return {
        startDate: start.toISOString().split('T')[0],
        endDate: end.toISOString().split('T')[0]
    };
}

function renderSchedule(jobs, startDate, endDate) {
    const calendar = document.getElementById('scheduleCalendar');
    
    // Group jobs by date
    const jobsByDate = {};
    jobs.forEach(job => {
        const date = job.start_date.split(' ')[0];
        if (!jobsByDate[date]) {
            jobsByDate[date] = [];
        }
        jobsByDate[date].push(job);
    });
    
    // Generate calendar grid
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    let html = '<div class="calendar-grid">';
    
    // Header row with dates
    html += '<div class="calendar-header">';
    const current = new Date(start);
    while (current <= end) {
        const dayName = current.toLocaleDateString('en-US', { weekday: 'short' });
        const dayNum = current.getDate();
        const isToday = isToday Date(current);
        
        html += `
            <div class="calendar-day-header ${isToday ? 'today' : ''}">
                <div class="day-name">${dayName}</div>
                <div class="day-number">${dayNum}</div>
            </div>
        `;
        
        current.setDate(current.getDate() + 1);
    }
    html += '</div>';
    
    // Jobs grid
    html += '<div class="calendar-body">';
    const currentBody = new Date(start);
    while (currentBody <= end) {
        const dateStr = currentBody.toISOString().split('T')[0];
        const dayJobs = jobsByDate[dateStr] || [];
        const isToday = isTodayDate(currentBody);
        
        html += `<div class="calendar-day ${isToday ? 'today' : ''}" data-date="${dateStr}">`;
        
        if (dayJobs.length > 0) {
            dayJobs.forEach(job => {
                html += `
                    <div class="job-card badge-${job.status}" onclick="viewJob(${job.id})" title="${job.job_number} - ${job.order_number}">
                        <div class="job-number">${job.job_number}</div>
                        <div class="job-order">${job.order_number}</div>
                        <div class="job-dept">${job.department_name}</div>
                        <div class="job-qty">${job.quantity_completed}/${job.quantity_planned}</div>
                    </div>
                `;
            });
            
            // Show capacity indicator
            const capacity = calculateDayCapacity(dayJobs);
            if (capacity.status !== 'normal') {
                html += `<div class="capacity-indicator capacity-${capacity.status}" title="${capacity.utilization}% utilized"></div>`;
            }
        } else {
            html += '<div class="no-jobs">No jobs scheduled</div>';
        }
        
        html += '</div>';
        currentBody.setDate(currentBody.getDate() + 1);
    }
    html += '</div>';
    html += '</div>';
    
    calendar.innerHTML = html;
}

function calculateDayCapacity(jobs) {
    // Simplified capacity calculation
    const totalJobs = jobs.length;
    const avgCapacity = 10; // Assume 10 jobs per day capacity
    
    const utilization = (totalJobs / avgCapacity) * 100;
    
    let status = 'normal';
    if (utilization > 100) status = 'overbooked';
    else if (utilization > 80) status = 'high';
    
    return { utilization: Math.round(utilization), status };
}

function isTodayDate(date) {
    const today = new Date();
    return date.toDateString() === today.toDateString();
}

function updatePeriodLabel() {
    const { startDate, endDate } = getDateRange();
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    let label;
    if (currentView === 'week') {
        label = `Week of ${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
    } else {
        label = start.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }
    
    document.getElementById('currentPeriodLabel').textContent = label;
}

function previousPeriod() {
    if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() - 7);
    } else {
        currentDate.setMonth(currentDate.getMonth() - 1);
    }
    loadSchedule();
}

function nextPeriod() {
    if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + 7);
    } else {
        currentDate.setMonth(currentDate.getMonth() + 1);
    }
    loadSchedule();
}

function goToToday() {
    currentDate = new Date();
    loadSchedule();
}

function viewJob(jobId) {
    // Navigate to job details
    window.location.href = `/pages/planning/jobs.php?id=${jobId}`;
}

// View switcher
document.querySelectorAll('.view-switcher button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.view-switcher button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentView = this.dataset.view;
        loadSchedule();
    });
});
</script>

<style>
.calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.calendar-controls h3 {
    margin: 0;
    flex: 1;
    text-align: center;
}

.view-switcher {
    display: flex;
    gap: 5px;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 6px;
}

.view-switcher button {
    padding: 8px 16px;
    border: none;
    background: transparent;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.view-switcher button.active {
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.department-filter {
    display: flex;
    align-items: center;
    gap: 10px;
}

.department-filter label {
    font-weight: 600;
}

.calendar-grid {
    display: grid;
    grid-template-rows: auto 1fr;
    gap: 0;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
}

.calendar-day-header {
    background: white;
    padding: 10px;
    text-align: center;
}

.calendar-day-header.today {
    background: #eff6ff;
}

.day-name {
    font-size: 12px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
}

.day-number {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-top: 5px;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
    border-top: none;
}

.calendar-day {
    background: white;
    min-height: 150px;
    padding: 8px;
    position: relative;
    cursor: pointer;
}

.calendar-day.today {
    background: #eff6ff;
}

.calendar-day:hover {
    background: #f9fafb;
}

.job-card {
    background: #3b82f6;
    color: white;
    padding: 6px 8px;
    border-radius: 4px;
    margin-bottom: 5px;
    font-size: 11px;
    cursor: pointer;
    transition: transform 0.2s;
}

.job-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.job-card.badge-scheduled { background: #6366f1; }
.job-card.badge-in_progress { background: #f59e0b; }
.job-card.badge-completed { background: #10b981; }
.job-card.badge-on_hold { background: #ef4444; }

.job-number {
    font-weight: 700;
    margin-bottom: 2px;
}

.job-order {
    opacity: 0.9;
    margin-bottom: 2px;
}

.job-dept, .job-qty {
    font-size: 10px;
    opacity: 0.8;
}

.no-jobs {
    color: #9ca3af;
    font-size: 12px;
    text-align: center;
    padding: 20px;
}

.capacity-indicator {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.capacity-indicator.capacity-high {
    background: #f59e0b;
}

.capacity-indicator.capacity-overbooked {
    background: #ef4444;
}

.schedule-legend {
    display: flex;
    align-items: center;
    gap: 30px;
}

.schedule-legend h4 {
    margin: 0;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.legend-item .badge,
.legend-item .capacity-indicator {
    width: 20px;
    height: 20px;
    display: inline-block;
    border-radius: 4px;
}

@media (max-width: 968px) {
    .calendar-grid {
        overflow-x: auto;
    }
    
    .calendar-header,
    .calendar-body {
        min-width: 700px;
    }
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
