// --- Calendar JS ---
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let isDragging = false;
let selectedDates = []; // Stores Date objects for selected range

const today = new Date();
today.setHours(0, 0, 0, 0);

const calendarOverlay = document.getElementById("calendarOverlay");
const calendarPopup = document.getElementById("calendarPopup");
const calendarDaysEl = document.getElementById("calendar-days");
const calendarDatesEl = document.getElementById("calendar-dates");
const monthLabel = document.getElementById("month-label");
const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

const dayNamesShort = ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"]; // For rendering day headers


function openCalendar() {
    const checkinInput = document.getElementById("start-date");
    const checkoutInput = document.getElementById("end-date");

    calendarPopup.style.display = "block";
    calendarOverlay.style.display = "block";
    
    // Reset selectedDates and set calendar to previously selected dates or current month
    selectedDates = [];
    if (checkinInput.value && checkoutInput.value) {
        const [y1, m1, d1] = checkinInput.value.split('-').map(Number);
        const [y2, m2, d2] = checkoutInput.value.split('-').map(Number);
        
        const startDate = new Date(y1, m1 - 1, d1);
        const endDate = new Date(y2, m2 - 1, d2);

        currentYear = startDate.getFullYear();
        currentMonth = startDate.getMonth();

        // Populate selectedDates with the range
        let tempDate = new Date(startDate);
        while (tempDate <= endDate) {
            selectedDates.push(new Date(tempDate));
            tempDate.setDate(tempDate.getDate() + 1);
        }

    } else {
        // Reset to current month/year if no dates selected
        const now = new Date();
        currentMonth = now.getMonth();
        currentYear = now.getFullYear();
    }
    renderDaysOfWeek();
    renderCalendar();
}

function closeCalendar() {
    calendarPopup.style.display = "none";
    calendarOverlay.style.display = "none";
    // We do NOT clear selectedDates here, so the range remains
}

// Helper to check if a dateObj is in selectedDates
function isDateSelected(dateObj) {
    return selectedDates.some(d =>
        d.getFullYear() === dateObj.getFullYear() &&
        d.getMonth() === dateObj.getMonth() &&
        d.getDate() === dateObj.getDate()
    );
}

// This function now handles range selection logic
function toggleDate(dateEl, dateObj) {
    if (dateEl.classList.contains("past-date")) {
        return; 
    }

    if (selectedDates.length === 0) {
        // Start a new selection
        selectedDates.push(dateObj);
    } else if (selectedDates.length === 1) {
        // Second click, define end of range
        const firstSelectedDate = selectedDates[0];
        if (dateObj < firstSelectedDate) { // Clicked before first date, make it new start
            selectedDates = [dateObj, firstSelectedDate];
        } else { // Clicked after first date
            selectedDates.push(dateObj);
        }
        selectedDates.sort((a,b) => a.getTime() - b.getTime()); // Ensure sorted
    } else {
        // A range is already selected, clear and start new
        selectedDates = [dateObj];
    }
    highlightDateRange();
}

function renderDaysOfWeek() {
    calendarDaysEl.innerHTML = ""; // Clear previous days
    dayNamesShort.forEach(day => { // Use dayNamesShort
        const dayEl = document.createElement("div");
        el.classList.add("calendar-day-name"); // Use calendar-day-name class
        dayEl.textContent = day;
        calendarDaysEl.appendChild(dayEl);
    });
}

function renderCalendar() {
    monthLabel.textContent = `${monthNames[currentMonth]} ${currentYear + 543}`; // Thai year
    calendarDatesEl.innerHTML = ''; // Clear previous dates

    const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay(); // 0 for Sunday, 1 for Monday...
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    // Fill in blank dates for the start of the month
    for (let i = 0; i < firstDayOfMonth; i++) {
        const blank = document.createElement("div");
        blank.classList.add("calendar-date", "blank");
        calendarDatesEl.appendChild(blank);
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateEl = document.createElement("div");
        dateEl.className = "calendar-date";
        dateEl.textContent = i;
        dateEl.setAttribute('data-day', i); // Add data-day for easier selection

        const dateToCheck = new Date(currentYear, currentMonth, i);
        dateToCheck.setHours(0, 0, 0, 0);

        if (dateToCheck < today) {
            dateEl.classList.add("past-date");
        }

        dateEl.addEventListener("mousedown", (e) => {
            if (dateEl.classList.contains("past-date")) return;
            isDragging = true;
            toggleDate(dateEl, dateToCheck); // Use new toggleDate
        });

        dateEl.addEventListener("mouseover", () => {
            if (isDragging && !dateEl.classList.contains("past-date")) {
                toggleDate(dateEl, dateToCheck); // Use new toggleDate
            }
        });
        
        calendarDatesEl.appendChild(dateEl);
    }

    // Ensure range is highlighted after rendering
    highlightDateRange();

    // Listen for mouseup on the document to stop dragging anywhere
    document.addEventListener('mouseup', () => {
        isDragging = false;
    }, { once: true }); // Only listen once after mousedown
}

// New helper function for highlighting a range
function highlightDateRange() {
    const allDates = Array.from(calendarDatesEl.children).filter(el => el.classList.contains('calendar-date') && !el.classList.contains('blank'));
    allDates.forEach(el => el.classList.remove('selected')); // Clear all selections first

    if (selectedDates.length === 0) return;

    selectedDates.sort((a, b) => a.getTime() - b.getTime()); // Ensure selectedDates is sorted

    const startDate = selectedDates[0];
    const endDate = selectedDates[selectedDates.length - 1];

    allDates.forEach(dateEl => {
        const day = parseInt(dateEl.textContent);
        if (isNaN(day)) return; // Skip non-date elements

        const dateObj = new Date(currentYear, currentMonth, day);
        dateObj.setHours(0,0,0,0);

        if (dateObj >= startDate && dateObj <= endDate) {
            dateEl.classList.add('selected');
        }
    });
}


function changeMonth(offset) {
  // ไม่ให้ย้อนกลับไปเดือนในอดีต ถ้าเดือนใหม่ย้อนไปก่อนเดือนของวันนี้
  const newDate = new Date(currentYear, currentMonth + offset, 1);
  const startOfCurrentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  startOfCurrentMonth.setHours(0,0,0,0);

  if (newDate < startOfCurrentMonth && offset < 0) {
    return; // ไม่อนุญาตให้เปลี่ยนย้อนหลังไปเดือนที่ผ่านมา
  }

  currentMonth += offset;
  if (currentMonth < 0) {
    currentMonth = 11;
    currentYear--;
  } else if (currentMonth > 11) {
    currentMonth = 0;
    currentYear++;
  }
  renderCalendar();
}

function confirmDate() {
    if (selectedDates.length === 0) {
        alert("กรุณาเลือกวันก่อน");
        return;
    }

    // Sort selectedDates to ensure proper start and end
    selectedDates.sort((a, b) => a.getTime() - b.getTime());

    const checkInDate = selectedDates[0];
    const checkOutDate = selectedDates[selectedDates.length - 1];

    // Check if check-in date is in the past
    if (checkInDate < today) {
        alert("ไม่สามารถเลือกวันที่เช็คอินย้อนหลังได้ กรุณาเลือกวันที่ปัจจุบันหรืออนาคต");
        selectedDates = []; // Clear selection
        highlightDateRange(); // Update visual
        return;
    }
    
    const pad = n => n.toString().padStart(2, '0');

    // Update the input fields in hotel_room.php
    document.getElementById("start-date").value = `${checkInDate.getFullYear()}-${pad(checkInDate.getMonth() + 1)}-${pad(checkInDate.getDate())}`;
    document.getElementById("end-date").value = `${checkOutDate.getFullYear()}-${pad(checkOutDate.getMonth() + 1)}-${pad(checkOutDate.getDate())}`;

    closeCalendar();
    // Call updateGuestSummary from test.js to update hidden form fields as well
    // It's crucial that test.js is loaded BEFORE calendar.js if updateGuestSummary is in test.js
    if (typeof updateGuestSummary === 'function') {
        updateGuestSummary();
    } else {
        console.error("updateGuestSummary is not defined. Ensure test.js is loaded correctly.");
    }
}