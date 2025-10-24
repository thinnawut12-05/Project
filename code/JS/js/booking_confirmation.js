// ================== Global Variables and Initial Setup ==================
const today = new Date();
today.setHours(0, 0, 0, 0); // Set time to 00:00:00 for accurate date comparison

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const monthNames = [
  "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

let selectedDates = []; // Stores selected date elements for range selection
let isDragging = false; // For date range selection in calendar

// DOM Elements
const monthYearEl = document.getElementById("month-year");
const calendarDatesEl = document.getElementById("calendar-dates");
const calendarModal = document.getElementById("calendarModal");
const openCalendarBtn = document.getElementById("open-calendar-modal-btn");
const closeCalendarBtn = document.querySelector(".close-button");
const confirmDateBtn = document.getElementById("confirm-date-btn");

// ================== Guest and Room Management Functions ==================

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup for rooms and guests
    updateRoomsFromInput(); 
    updateGuestSummary(); // Call the combined summary function

    // Re-initialize child age selectors for any rooms loaded from PHP
    document.querySelectorAll('.room').forEach(room => {
        const childCount = parseInt(room.querySelector('.child-count').textContent);
        if (childCount > 0) {
            generateChildAgeSelectors(room);
        }
    });

    // Calendar initialization
    renderDaysOfWeek();
    renderCalendar();

    // Event listeners for calendar modal
    if (openCalendarBtn) {
        openCalendarBtn.addEventListener("click", () => calendarModal.style.display = "block");
    }
    if (closeCalendarBtn) {
        closeCalendarBtn.addEventListener("click", () => calendarModal.style.display = "none");
    }
    window.addEventListener("click", (event) => {
        if (event.target == calendarModal) {
            calendarModal.style.display = "none";
        }
    });

    // Event listener for date change updates
    const formCheckinDate = document.getElementById('form-checkin-date');
    const formCheckoutDate = document.getElementById('form-checkout-date');

    if (formCheckinDate) {
        formCheckinDate.addEventListener('change', updateGuestSummary);
    }
    if (formCheckoutDate) {
        formCheckoutDate.addEventListener('change', updateGuestSummary);
    }
});

// The roomCount variable is not needed globally since we can query for rooms directly.
// let roomCount = document.querySelectorAll('.room').length; 

function updateRoomsFromInput() {
    const numRoomsInput = document.getElementById('num-rooms');
    let desiredRoomCount = parseInt(numRoomsInput.value);

    if (isNaN(desiredRoomCount) || desiredRoomCount < 1) {
        desiredRoomCount = 1;
        numRoomsInput.value = 1;
    }
    const maxRooms = parseInt(numRoomsInput.getAttribute('max')) || 5;
    if (desiredRoomCount > maxRooms) {
        desiredRoomCount = maxRooms;
        numRoomsInput.value = maxRooms;
        alert(`คุณสามารถจองห้องพักได้สูงสุด ${maxRooms} ห้อง`); // Inform user
    }

    const container = document.getElementById('rooms-container');
    const existingRooms = container.querySelectorAll('.room');
    const currentRoomCount = existingRooms.length;

    if (desiredRoomCount < currentRoomCount) {
        for (let i = currentRoomCount - 1; i >= desiredRoomCount; i--) {
            existingRooms[i].remove();
        }
    } else if (desiredRoomCount > currentRoomCount) {
        for (let i = currentRoomCount; i < desiredRoomCount; i++) {
            addRoomInternal(i + 1);
        }
    }
    // Update the hidden input for num_rooms immediately
    const formNumRoomsInput = document.getElementById('form-num-rooms');
    if (formNumRoomsInput) {
        formNumRoomsInput.value = desiredRoomCount;
    }
    updateGuestSummary(); // Call the combined summary function
}

function addRoomInternal(newRoomNumber) {
    const container = document.getElementById('rooms-container');
    const guestSummaryEl = document.querySelector('.guest-summary'); // Find the guest summary div

    const newRoom = document.createElement('div');
    newRoom.classList.add('room');
    newRoom.setAttribute('data-room', newRoomNumber);
    newRoom.style = "margin-bottom: 10px; padding: 10px; border: 1px solid #f0f0f0; border-radius: 5px; background-color: #fff;"; // Apply styles

    newRoom.innerHTML = `
        <h4>ห้องที่ ${newRoomNumber}</h4>
        <div class="guest-group">
            <span>ผู้ใหญ่</span>
            <button type="button" onclick="changeGuest(this, 'adult', -1)">–</button>
            <span class="adult-count">1</span>
            <button type="button" onclick="changeGuest(this, 'adult', 1)">+</button>
        </div>
        <div class="guest-group">
            <span>เด็ก</span>
            <button type="button" onclick="changeGuest(this, 'child', -1)">–</button>
            <span class="child-count">0</span>
            <button type="button" onclick="changeGuest(this, 'child', 1)">+</button>
        </div>
        <div class="child-age-container" style="display:none; margin-top:8px;">
            <label>อายุของเด็กแต่ละคน (ปี):</label>
            <div class="child-age-list"></div>
        </div>
    `;

    // Insert new room before the guest summary element
    if (guestSummaryEl) {
        container.insertBefore(newRoom, guestSummaryEl);
    } else {
        container.appendChild(newRoom); // Fallback if guest-summary not found
    }
}

function changeGuest(button, type, delta) {
    const room = button.closest('.room');
    const countElement = room.querySelector(`.${type}-count`);
    let count = parseInt(countElement.textContent);
    let newCount = count + delta;

    // Apply specific constraints per room (2 adults max, 1 child max)
    if (type === 'adult') {
        if (newCount < 1) { // Minimum 1 adult per room
             // Check if this is the last adult across all rooms
            let totalAdultsAcrossRooms = 0;
            document.querySelectorAll('.room .adult-count').forEach(span => {
                totalAdultsAcrossRooms += parseInt(span.textContent);
            });
            if (totalAdultsAcrossRooms <= 1 && delta === -1) {
                alert('ต้องมีผู้ใหญ่อย่างน้อย 1 ท่านสำหรับการจองโดยรวม');
                return; // Prevent decrement if it's the last adult overall
            }
            newCount = 1; // Ensure at least 1 adult in this room if not the last overall
        }
        if (newCount > 2) newCount = 2; // Maximum 2 adults per room
    }
    if (type === 'child') {
        if (newCount < 0) newCount = 0; // Minimum 0 children per room
        if (newCount > 1) newCount = 1; // Maximum 1 child per room
    }

    countElement.textContent = newCount;

    if (type === 'child') {
        generateChildAgeSelectors(room);
    }

    updateGuestSummary(); // Call the combined summary function
}

function generateChildAgeSelectors(room) {
    const childCount = parseInt(room.querySelector('.child-count').textContent);
    const childAgeContainer = room.querySelector('.child-age-container');
    const childAgeList = room.querySelector('.child-age-list');

    // Clear existing age selectors
    childAgeList.innerHTML = '';

    if (childCount > 0) {
        childAgeContainer.style.display = 'block';
        for (let i = 0; i < childCount; i++) {
            const select = document.createElement('select');
            select.name = `child-age-room${room.dataset.room}-${i}`;
            select.classList.add('child-age-input'); // Add a class for styling if needed
            let options = '<option value="">อายุ</option>';
            for (let age = 1; age <= 12; age++) {
                options += `<option value="${age}">${age} ปี</option>`;
            }
            select.innerHTML = options;
            childAgeList.appendChild(select);
        }
    } else {
        childAgeContainer.style.display = 'none';
    }
}

/**
 * Combines guest summary, room count, night calculation, and total price calculation.
 * Updates both displayed elements and hidden form inputs for submission.
 */
function updateGuestSummary() {
    let totalAdults = 0;
    let totalChildren = 0;
    const rooms = document.querySelectorAll('.room');
    rooms.forEach(room => {
        totalAdults += parseInt(room.querySelector('.adult-count').textContent);
        totalChildren += parseInt(room.querySelector('.child-count').textContent);
    });

    const numRooms = rooms.length; // Get the current number of displayed rooms

    // Update displayed guest summary
    const summaryText = `ผู้ใหญ่ ${totalAdults}, เด็ก ${totalChildren} คน`;
    const summaryInput = document.getElementById('guest-summary-input');
    if (summaryInput) {
        summaryInput.value = summaryText;
    }

    // Update hidden inputs for form submission (total guests and rooms)
    const formAdultsInput = document.getElementById('form-total-adults');
    const formChildrenInput = document.getElementById('form-total-children');
    const formNumRoomsInput = document.getElementById('form-num-rooms');

    if (formAdultsInput) formAdultsInput.value = totalAdults;
    if (formChildrenInput) formChildrenInput.value = totalChildren;
    if (formNumRoomsInput) formNumRoomsInput.value = numRooms;


    // === Date and Price Calculation ===
    const checkinDateStr = document.getElementById('form-checkin-date').value;
    const checkoutDateStr = document.getElementById('form-checkout-date').value;
    let numNights = 1;

    try {
        const checkinDate = new Date(checkinDateStr);
        const checkoutDate = new Date(checkoutDateStr);
        
        // Ensure valid dates and checkout is strictly after checkin
        if (checkoutDate <= checkinDate) {
            // If checkout is invalid or same as checkin, default to +1 day
            checkoutDate.setDate(checkinDate.getDate() + 1);
            // Update the hidden checkout date field if it was invalid
            document.getElementById('form-checkout-date').value = checkoutDate.toISOString().slice(0, 10);
            // Also update the display for checkout date
            document.getElementById('display-checkout-date').textContent = checkoutDate.toISOString().slice(0, 10);
        }
        
        const diffTime = Math.abs(checkoutDate - checkinDate);
        numNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); // Use ceil to ensure at least 1 night for same-day checkin/checkout resulting in 0 diff
        if (numNights <= 0) numNights = 1; // Fallback to ensure at least 1 night
    } catch (e) {
        console.error("Error calculating nights:", e);
        numNights = 1;
    }

    // Update displayed number of nights
    const displayNumNights = document.getElementById('display-num-nights');
    if (displayNumNights) {
        displayNumNights.textContent = numNights;
    }

    // Calculate total price
    const pricePerRoomInput = document.getElementById('form-price-per-room');
    const pricePerRoom = pricePerRoomInput ? parseFloat(pricePerRoomInput.value) : 0;
    const totalPrice = (pricePerRoom * numRooms) * numNights;

    // Update displayed total price
    const displayTotalPrice = document.getElementById('display-total-price');
    if (displayTotalPrice) {
        displayTotalPrice.textContent = totalPrice.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

// ================== Calendar Management Functions ==================

function renderDaysOfWeek() {
    // This part should be rendered once if the structure is static HTML for days of week.
    // Assuming the calendar has a 'calendar-days' element for this.
    // No change needed here unless the structure is dynamic.
}

function renderCalendar() {
    if (!calendarDatesEl || !monthYearEl) return; // Ensure elements exist

    const firstDay = new Date(currentYear, currentMonth, 1).getDay(); // 0 = Sunday, 1 = Monday, etc.
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    monthYearEl.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    calendarDatesEl.innerHTML = "";
    selectedDates = []; // Clear selected dates when re-rendering

    // Fill in blank spaces for the first day of the month
    for (let i = 0; i < firstDay; i++) {
        const blank = document.createElement("div");
        calendarDatesEl.appendChild(blank);
    }

    // Render days of the month
    for (let i = 1; i <= daysInMonth; i++) {
        const dateEl = document.createElement("div");
        dateEl.className = "calendar-date";
        dateEl.textContent = i;

        const dateToCheck = new Date(currentYear, currentMonth, i);
        dateToCheck.setHours(0, 0, 0, 0);

        // Add 'past-date' class for dates before today
        if (dateToCheck < today) {
            dateEl.classList.add("past-date");
        }

        // Add event listeners for date selection
        dateEl.addEventListener("mousedown", () => {
            if (dateToCheck < today) return; // Prevent selecting past dates
            isDragging = true;
            toggleDate(dateEl);
        });

        dateEl.addEventListener("mouseover", () => {
            if (isDragging && dateToCheck >= today) { // Only allow dragging for future/today dates
                toggleDate(dateEl);
            }
        });

        dateEl.addEventListener("mouseup", () => isDragging = false);

        calendarDatesEl.appendChild(dateEl);
    }
}

function toggleDate(dateEl) {
    if (dateEl.classList.contains("past-date")) return; // Do not toggle past dates

    dateEl.classList.toggle("selected");
    if (dateEl.classList.contains("selected")) {
        selectedDates.push(dateEl);
    } else {
        selectedDates = selectedDates.filter(el => el !== dateEl);
    }
    // Sort selected dates to ensure checkin/checkout logic is correct
    selectedDates.sort((a, b) => parseInt(a.textContent) - parseInt(b.textContent));

    // For range selection, if two dates are selected, select all in between
    if (selectedDates.length === 2 && isDragging) {
        const firstDay = parseInt(selectedDates[0].textContent);
        const lastDay = parseInt(selectedDates[1].textContent);
        
        document.querySelectorAll('.calendar-date').forEach(el => {
            const day = parseInt(el.textContent);
            if (day > firstDay && day < lastDay && !el.classList.contains("past-date")) {
                el.classList.add("selected");
                if (!selectedDates.includes(el)) {
                    selectedDates.push(el);
                }
            }
        });
        selectedDates.sort((a, b) => parseInt(a.textContent) - parseInt(b.textContent)); // Re-sort after adding range
    } else if (selectedDates.length > 2 && !isDragging) {
        // If more than two dates are selected without dragging, clear all and select just the new one
        selectedDates.forEach(el => el.classList.remove("selected"));
        selectedDates = [dateEl];
        dateEl.classList.add("selected");
    } else if (selectedDates.length === 1 && !isDragging) {
        // If only one date is selected and not dragging, clear previous selections
        document.querySelectorAll('.calendar-date.selected').forEach(el => el.classList.remove("selected"));
        selectedDates = [dateEl];
        dateEl.classList.add("selected");
    }
}


function changeMonth(offset) {
    // ไม่ให้ย้อนกลับไปเดือนในอดีต ถ้าเดือนปัจจุบันคือเดือนของวันนี้
    const newMonth = currentMonth + offset;
    const newDate = new Date(currentYear, newMonth, 1);

    // ถ้าเดือนใหม่ย้อนไปก่อนเดือนปัจจุบัน และเป็นปีเดียวกัน (หรือปีก่อนหน้า)
    if (newDate < new Date(today.getFullYear(), today.getMonth(), 1)) {
        return; // ไม่อนุญาตให้เปลี่ยนไปเดือนในอดีต
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
        alert("กรุณาเลือกวันเช็คอินและเช็คเอาท์");
        return;
    }

    // Sort selected dates to ensure start and end are correct
    selectedDates.sort((a, b) => parseInt(a.textContent) - parseInt(b.textContent));

    const firstSelectedDay = parseInt(selectedDates[0].textContent);
    const lastSelectedDay = parseInt(selectedDates[selectedDates.length - 1].textContent);

    const checkInDate = new Date(currentYear, currentMonth, firstSelectedDay);
    checkInDate.setHours(0, 0, 0, 0);
    const checkOutDate = new Date(currentYear, currentMonth, lastSelectedDay);
    checkOutDate.setHours(0, 0, 0, 0);

    // Ensure check-in date is not in the past
    if (checkInDate < today) {
        alert("ไม่สามารถเลือกวันที่เช็คอินย้อนหลังได้ กรุณาเลือกวันที่ปัจจุบันหรืออนาคต");
        selectedDates.forEach(el => el.classList.remove("selected")); // Clear selection
        selectedDates = [];
        return;
    }

    // Ensure check-out date is at least one day after check-in
    if (checkOutDate <= checkInDate) {
        alert("วันที่เช็คเอาท์ต้องเป็นวันหลังจากวันที่เช็คอิน");
        selectedDates.forEach(el => el.classList.remove("selected")); // Clear selection
        selectedDates = [];
        return;
    }

    // Format dates to YYYY-MM-DD
    const checkInDateISO = `${checkInDate.getFullYear()}-${String(checkInDate.getMonth() + 1).padStart(2, "0")}-${String(checkInDate.getDate()).padStart(2, "0")}`;
    const checkOutDateISO = `${checkOutDate.getFullYear()}-${String(checkOutDate.getMonth() + 1).padStart(2, "0")}-${String(checkOutDate.getDate()).padStart(2, "0")}`;

    // ***************************************************************
    // IMPORTANT: Update the hidden form inputs with the correct IDs
    // These are the inputs that PHP will read for submission
    // ***************************************************************
    const formCheckinInput = document.getElementById("form-checkin-date");
    const formCheckoutInput = document.getElementById("form-checkout-date");

    if (formCheckinInput) formCheckinInput.value = checkInDateISO;
    if (formCheckoutInput) formCheckoutInput.value = checkOutDateISO;

    // Update the displayed dates as well
    const displayCheckinSpan = document.getElementById("display-checkin-date");
    const displayCheckoutSpan = document.getElementById("display-checkout-date");
    
    if (displayCheckinSpan) displayCheckinSpan.textContent = checkInDateISO;
    if (displayCheckoutSpan) displayCheckoutSpan.textContent = checkOutDateISO;


    closeCalendar(); // Close the modal
    updateGuestSummary(); // Recalculate everything after date change
}

function closeCalendar() {
    if (calendarModal) {
        calendarModal.style.display = "none";
    }
}

// Ensure the calendar navigation buttons are linked
// Example: Assuming you have buttons with IDs 'prev-month-btn' and 'next-month-btn'
document.addEventListener('DOMContentLoaded', () => {
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');

    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => changeMonth(-1));
    }
    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => changeMonth(1));
    }

    if (confirmDateBtn) {
        confirmDateBtn.addEventListener('click', confirmDate);
    }
});