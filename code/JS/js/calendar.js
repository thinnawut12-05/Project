// --- Calendar JS ---
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let isDragging = false;
let selectedDates = [];

const today = new Date();
today.setHours(0, 0, 0, 0);

const calendarDaysEl = document.getElementById("calendar-days");
const calendarDatesEl = document.getElementById("calendar-dates");
const monthLabel = document.getElementById("month-label");
const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

function openCalendar() {
    document.getElementById("calendarOverlay").style.display = "block";
    document.getElementById("calendarPopup").style.display = "block";
    renderDaysOfWeek();
    renderCalendar();
}

function closeCalendar() {
    document.getElementById("calendarOverlay").style.display = "none";
    document.getElementById("calendarPopup").style.display = "none";
}

function toggleDate(el, dateObj) {
    if (!el.classList.contains("calendar-date") || el.classList.contains("past-date")) return;
    const index = selectedDates.findIndex(d =>
        d.getFullYear() === dateObj.getFullYear() &&
        d.getMonth() === dateObj.getMonth() &&
        d.getDate() === dateObj.getDate()
    );
    if (index > -1) {
        el.classList.remove("selected");
        selectedDates.splice(index, 1);
    } else {
        el.classList.add("selected");
        selectedDates.push(dateObj);
    }
}

function renderDaysOfWeek() {
    calendarDaysEl.innerHTML = "";
    ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"].forEach(d => {
        const el = document.createElement("div");
        el.className = "calendar-day";
        el.textContent = d;
        calendarDaysEl.appendChild(el);
    });
}

function renderCalendar() {
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    monthLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    calendarDatesEl.innerHTML = "";

    // blank days
    for (let i = 0; i < firstDay; i++) {
        const blank = document.createElement("div");
        blank.className = "calendar-date blank";
        calendarDatesEl.appendChild(blank);
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateEl = document.createElement("div");
        dateEl.className = "calendar-date";
        dateEl.textContent = i;

        const dateToCheck = new Date(currentYear, currentMonth, i);
        dateToCheck.setHours(0, 0, 0, 0);

        if (dateToCheck < today) dateEl.classList.add("past-date");

        if (selectedDates.some(d =>
            d.getFullYear() === dateToCheck.getFullYear() &&
            d.getMonth() === dateToCheck.getMonth() &&
            d.getDate() === dateToCheck.getDate()
        )) dateEl.classList.add("selected");

        dateEl.addEventListener("mousedown", () => {
            isDragging = true;
            toggleDate(dateEl, dateToCheck);
        });
        dateEl.addEventListener("mouseover", () => {
            if (isDragging) toggleDate(dateEl, dateToCheck);
        });
        dateEl.addEventListener("mouseup", () => isDragging = false);

        calendarDatesEl.appendChild(dateEl);
    }
}

function changeMonth(offset) {
    currentMonth += offset;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    else if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    renderCalendar();
}

function confirmDate() {
    if (selectedDates.length === 0) {
        alert("กรุณาเลือกวันก่อน");
        return;
    }

    const sorted = selectedDates.sort((a, b) => a - b);

    const checkInDate = sorted[0];
    const checkOutDate = sorted[sorted.length - 1];

    const pad = n => n.toString().padStart(2, '0');

    // แสดงใน input ที่เห็น
    document.getElementById("start-date").value = `${checkInDate.getFullYear()}-${pad(checkInDate.getMonth() + 1)}-${pad(checkInDate.getDate())}`;
    document.getElementById("end-date").value = `${checkOutDate.getFullYear()}-${pad(checkOutDate.getMonth() + 1)}-${pad(checkOutDate.getDate())}`;

    // ส่งค่าไป hidden input สำหรับ submit
    document.getElementById("checkin_date_submit").value = `${checkInDate.getFullYear()}-${pad(checkInDate.getMonth() + 1)}-${pad(checkInDate.getDate())}`;
    document.getElementById("checkout_date_submit").value = `${checkOutDate.getFullYear()}-${pad(checkOutDate.getMonth() + 1)}-${pad(checkOutDate.getDate())}`;

    closeCalendar();
}

