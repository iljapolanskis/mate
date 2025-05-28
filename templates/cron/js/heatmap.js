// Configuration
let currentGranularity = 1;
let currentView = 'day';
let currentDate = new Date();
let currentGroup = '';

// Initialize the heatmap
function initializeHeatmap() {
    if (typeof heatmapConfig === 'undefined') {
        console.error('Heatmap configuration not found');
        return;
    }

    // Set initial date
    currentDate = new Date(heatmapConfig.year + '-01-01');
    document.getElementById('selectedDate').value = formatDate(currentDate);

    updateHeatmap();
    setupEventListeners();
}

// Setup event listeners
function setupEventListeners() {
    // Control changes
    document.getElementById('granularity').addEventListener('change', updateHeatmap);
    document.getElementById('viewType').addEventListener('change', updateHeatmap);
    document.getElementById('selectedDate').addEventListener('change', updateHeatmap);
    document.getElementById('groupFilter').addEventListener('change', updateHeatmap);

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            resetView();
        } else if (e.key === 'ArrowLeft') {
            navigateDate(-1);
        } else if (e.key === 'ArrowRight') {
            navigateDate(1);
        }
    });
}

// Update the heatmap based on current settings
function updateHeatmap() {
    currentGranularity = parseInt(document.getElementById('granularity').value);
    currentView = document.getElementById('viewType').value;
    currentDate = new Date(document.getElementById('selectedDate').value);
    currentGroup = document.getElementById('groupFilter').value;

    const container = document.getElementById('heatmapContainer');
    container.innerHTML = '';

    const heatmap = document.createElement('div');
    heatmap.className = 'heatmap';

    switch (currentView) {
        case 'day':
            renderDayView(heatmap);
            break;
        case 'week':
            renderWeekView(heatmap);
            break;
        case 'month':
            renderMonthView(heatmap);
            break;
    }

    container.appendChild(heatmap);
    updateStats();
}

// Render single day view
function renderDayView(container) {
    const dayOfYear = getDayOfYear(currentDate);

    // Create header row with time labels
    const headerRow = createHeaderRow();
    container.appendChild(headerRow);

    // Create data row
    const dataRow = document.createElement('div');
    dataRow.className = 'heatmap-row';

    // Date label
    const dateLabel = document.createElement('div');
    dateLabel.className = 'date-label';
    dateLabel.textContent = formatDateLabel(currentDate);
    dataRow.appendChild(dateLabel);

    // Create cells for each time slot
    for (let minute = 0; minute < 1440; minute += currentGranularity) {
        const cell = createTimeCell(dayOfYear, minute);
        dataRow.appendChild(cell);
    }

    container.appendChild(dataRow);
}

// Render week view
function renderWeekView(container) {
    const startDate = new Date(currentDate);
    startDate.setDate(currentDate.getDate() - currentDate.getDay()); // Start of week

    renderMultiDayView(container, startDate, 7);
}

// Render month view
function renderMonthView(container) {
    const startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();

    renderMultiDayView(container, startDate, daysInMonth);
}

// Render multiple days view
function renderMultiDayView(container, startDate, numDays) {
    // Create header row with time labels
    const headerRow = createHeaderRow();
    container.appendChild(headerRow);

    // Create rows for each day
    for (let day = 0; day < numDays; day++) {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + day);
        const dayOfYear = getDayOfYear(date);

        const row = document.createElement('div');
        row.className = 'heatmap-row';

        // Date label
        const dateLabel = document.createElement('div');
        dateLabel.className = 'date-label';
        dateLabel.textContent = formatDateLabel(date);
        row.appendChild(dateLabel);

        // Create cells for time slots
        for (let minute = 0; minute < 1440; minute += currentGranularity) {
            const cell = createTimeCell(dayOfYear, minute);
            row.appendChild(cell);
        }

        container.appendChild(row);
    }
}

// Create header row with time labels
function createHeaderRow() {
    const headerRow = document.createElement('div');
    headerRow.className = 'heatmap-row';

    // Empty corner cell
    const emptyCell = document.createElement('div');
    emptyCell.className = 'date-label';
    headerRow.appendChild(emptyCell);

    // Header row depends on currentGranularity in minutes
    $howManyColumnsPerHour = Math.floor(60 / currentGranularity);

    // Time labels
    for (let i = 0; i < 24 * $howManyColumnsPerHour; i++) {
        const label = document.createElement('div');
        label.className = 'time-label';
        let hour = Math.floor(i / $howManyColumnsPerHour);
        let minutes = (i % $howManyColumnsPerHour) * currentGranularity;
        label.textContent = hour.toString().padStart(2, '0') + ':' + minutes.toString().padStart(2, '0');
        label.style.width = (60 / currentGranularity * 100) + 'px';
        headerRow.appendChild(label);
    }

    return headerRow;
}

// Create a time cell
function createTimeCell(dayOfYear, minute) {
    const cell = document.createElement('div');
    cell.className = 'heatmap-cell';

    // Calculate value for this time slot
    const value = getValueForTimeSlot(dayOfYear, minute);

    // Set cell properties
    cell.style.backgroundColor = getHeatmapColor(value);
    cell.style.width = (100 / currentGranularity) + 'px';

    // Add tooltip
    addTooltip(cell, dayOfYear, minute, value);

    return cell;
}

// Get value for a specific time slot
function getValueForTimeSlot(dayOfYear, minute) {
    let value = 0;

    if (currentGroup) {
        // Use group-specific matrix if available
        const groupMatrix = heatmapConfig.groupMatrix[currentGroup];
        if (groupMatrix && groupMatrix[dayOfYear]) {
            for (let i = 0; i < currentGranularity && minute + i < 1440; i++) {
                value += groupMatrix[dayOfYear][minute + i] || 0;
            }
        }
    } else {
        // Use overall matrix
        if (heatmapConfig.matrix[dayOfYear]) {
            for (let i = 0; i < currentGranularity && minute + i < 1440; i++) {
                value += heatmapConfig.matrix[dayOfYear][minute + i] || 0;
            }
        }
    }

    return value;
}

// Get heatmap color based on value
function getHeatmapColor(value) {
    if (value === 0) {
        return '#f0f9ff';
    }

    const intensity = Math.min(value / heatmapConfig.maxValue / currentGranularity, 1);
    const lightness = 95 - (intensity * 80);

    return `hsl(200, 80%, ${lightness}%)`;
}

// Add tooltip to cell
function addTooltip(cell, dayOfYear, minute, value) {
    cell.addEventListener('mouseenter', function(e) {
        const tooltip = document.getElementById('tooltip');
        const date = dateFromDayOfYear(dayOfYear, heatmapConfig.year);
        const time = minutesToTime(minute);
        const endTime = minutesToTime(minute + currentGranularity - 1);

        let tooltipText = `Date: ${formatDateLabel(date)}\n`;
        tooltipText += `Time: ${time} - ${endTime}\n`;
        tooltipText += `Executions: ${value}`;

        if (currentGranularity > 1) {
            tooltipText += ` (${currentGranularity} minutes)`;
        }

        tooltip.textContent = tooltipText;
        tooltip.style.display = 'block';

        // Position tooltip
        const rect = cell.getBoundingClientRect();
        tooltip.style.left = (rect.left + rect.width / 2) + 'px';
        tooltip.style.top = (rect.top - 10) + 'px';
        tooltip.style.transform = 'translate(-50%, -100%)';
    });

    cell.addEventListener('mouseleave', function() {
        document.getElementById('tooltip').style.display = 'none';
    });
}

// Update statistics panel
function updateStats() {
    const statsGrid = document.getElementById('statsGrid');
    statsGrid.innerHTML = '';

    // Calculate stats for current view
    const stats = calculateCurrentViewStats();

    // Render stats
    Object.entries(stats).forEach(([label, value]) => {
        const card = document.createElement('div');
        card.className = 'stat-card';

        const labelEl = document.createElement('div');
        labelEl.className = 'stat-label';
        labelEl.textContent = label;

        const valueEl = document.createElement('div');
        valueEl.className = 'stat-value';
        valueEl.textContent = value;

        card.appendChild(labelEl);
        card.appendChild(valueEl);
        statsGrid.appendChild(card);
    });
}

// Calculate statistics for current view
function calculateCurrentViewStats() {
    let totalExecutions = 0;
    let peakValue = 0;
    let peakTime = '';
    let activeSlots = 0;

    // Determine date range for current view
    let startDate, endDate;

    switch (currentView) {
        case 'day':
            startDate = endDate = currentDate;
            break;
        case 'week':
            startDate = new Date(currentDate);
            startDate.setDate(currentDate.getDate() - currentDate.getDay());
            endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
            break;
        case 'month':
            startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            endDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            break;
    }

    // Calculate stats for date range
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        const dayOfYear = getDayOfYear(d);

        for (let minute = 0; minute < 1440; minute += currentGranularity) {
            const value = getValueForTimeSlot(dayOfYear, minute);
            totalExecutions += value;

            if (value > 0) {
                activeSlots++;
            }

            if (value > peakValue) {
                peakValue = value;
                peakTime = `${formatDateLabel(d)} ${minutesToTime(minute)}`;
            }
        }
    }

    return {
        'Total Executions': totalExecutions,
        'Peak Executions': peakValue,
        'Peak Time': peakTime || 'N/A',
        'Active Time Slots': activeSlots,
        'View': `${currentView.charAt(0).toUpperCase() + currentView.slice(1)} (${currentGranularity} min)`,
        'Group': currentGroup || 'All Groups'
    };
}

// Navigation
function navigateDate(direction) {
    const newDate = new Date(currentDate);

    switch (currentView) {
        case 'day':
            newDate.setDate(currentDate.getDate() + direction);
            break;
        case 'week':
            newDate.setDate(currentDate.getDate() + (direction * 7));
            break;
        case 'month':
            newDate.setMonth(currentDate.getMonth() + direction);
            break;
    }

    document.getElementById('selectedDate').value = formatDate(newDate);
    updateHeatmap();
}

// Reset view
function resetView() {
    document.getElementById('granularity').value = '1';
    document.getElementById('viewType').value = 'day';
    document.getElementById('selectedDate').value = heatmapConfig.year + '-01-01';
    document.getElementById('groupFilter').value = '';
    updateHeatmap();
}

// Export current view
function exportView() {
    // Create export data
    const exportData = {
        date: currentDate.toISOString(),
        view: currentView,
        granularity: currentGranularity,
        group: currentGroup,
        stats: calculateCurrentViewStats()
    };

    // Download as JSON
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `cron-heatmap-${formatDate(currentDate)}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

// Utility functions
function getDayOfYear(date) {
    const start = new Date(date.getFullYear(), 0, 0);
    const diff = date - start;
    return Math.floor(diff / (1000 * 60 * 60 * 24));
}

function dateFromDayOfYear(dayOfYear, year) {
    const date = new Date(year, 0);
    date.setDate(dayOfYear);
    return date;
}

function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatDateLabel(date) {
    return date.toLocaleDateString('en', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
}
