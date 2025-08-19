// รอให้หน้าเว็บโหลดเสร็จก่อนเริ่มทำงาน
document.addEventListener('DOMContentLoaded', () => {
    updateGuestSummary();
    document.querySelectorAll('.room').forEach(room => {
        const childCount = parseInt(room.querySelector('.child-count').textContent);
        if (childCount > 0) {
            generateChildAgeSelectors(room);
        }
    });
});

// นับจำนวนห้องเริ่มต้นจาก HTML ที่มีอยู่จริง
let roomCount = document.querySelectorAll('.room').length;

/**
 * ฟังก์ชันเพิ่มห้องใหม่
 */
function addRoom() {
    roomCount++;
    const container = document.getElementById('rooms-container');

    const newRoom = document.createElement('div');
    newRoom.classList.add('room');
    newRoom.setAttribute('data-room', roomCount);
    
    // *** แก้ไข HTML ตรงนี้ให้เหมือนกับห้องที่ 1 ***
    newRoom.innerHTML = `
        <h4>ห้องที่ ${roomCount}</h4>
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
    
    // แทรกห้องใหม่เข้าไปใน container
    container.appendChild(newRoom);
    updateGuestSummary();
}


/**
 * ฟังก์ชันเปลี่ยนจำนวนผู้เข้าพัก (ผู้ใหญ่/เด็ก)
 */
function changeGuest(button, type, delta) {
    const room = button.closest('.room');
    const countElement = room.querySelector(`.${type}-count`);
    let count = parseInt(countElement.textContent);
    let newCount = count + delta;

    if (type === 'adult') {
        if (newCount < 1) newCount = 1;
        if (newCount > 2) newCount = 2; // จำกัดผู้ใหญ่ 4 คน
    }
    if (type === 'child') {
        if (newCount < 0) newCount = 0;
        if (newCount > 2) newCount = 2; // จำกัดเด็ก 3 คน
    }

    countElement.textContent = newCount;

    if (type === 'child') {
        generateChildAgeSelectors(room);
    }
    
    updateGuestSummary();
}


/**
 * ฟังก์ชันสร้าง Dropdown สำหรับเลือกอายุเด็ก
 */
function generateChildAgeSelectors(room) {
    const childCount = parseInt(room.querySelector('.child-count').textContent);
    const childAgeContainer = room.querySelector('.child-age-container');
    const childAgeList = room.querySelector('.child-age-list');
    
    childAgeList.innerHTML = ''; 

    if (childCount > 0) {
        childAgeContainer.style.display = 'block';
        for (let i = 0; i < childCount; i++) {
            const select = document.createElement('select');
            select.name = `child-age-room${room.dataset.room}-${i}`;
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
 * ฟังก์ชันอัปเดตข้อมูลสรุปรวมจำนวนผู้เข้าพักทั้งหมด
 */
function updateGuestSummary() {
    let totalAdults = 0;
    let totalChildren = 0;

    document.querySelectorAll('.room').forEach(room => {
        totalAdults += parseInt(room.querySelector('.adult-count').textContent);
        totalChildren += parseInt(room.querySelector('.child-count').textContent);
    });
    
    const summaryText = `ผู้ใหญ่ ${totalAdults}, เด็ก ${totalChildren} คน`;
    const summaryInput = document.getElementById('guest-summary-input');
    if (summaryInput) {
        summaryInput.value = summaryText;
    }
}

//ปฏิทิน
const monthNames = [
  "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let isDragging = false;
let selectedDates = [];

const calendarDaysEl = document.getElementById("calendar-days");
const calendarDatesEl = document.getElementById("calendar-dates");
const monthLabel = document.getElementById("month-label");

function openCalendar() {
  document.getElementById("calendarOverlay").style.display = "block";
  document.getElementById("calendarPopup").style.display = "block";
}

function closeCalendar() {
  document.getElementById("calendarOverlay").style.display = "none";
  document.getElementById("calendarPopup").style.display = "none";
}

function toggleDate(el) {
  if (!el.classList.contains("calendar-date")) return;
  if (el.classList.contains("selected")) {
    el.classList.remove("selected");
    selectedDates = selectedDates.filter(d => d !== el);
  } else {
    el.classList.add("selected");
    selectedDates.push(el);
  }
}

function renderDaysOfWeek() {
  const days = ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"];
  calendarDaysEl.innerHTML = "";
  days.forEach(day => {
    const el = document.createElement("div");
    el.className = "calendar-day";
    el.textContent = day;
    calendarDaysEl.appendChild(el);
  });
}

function renderCalendar() {
  const firstDay = new Date(currentYear, currentMonth, 1).getDay();
  const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

  monthLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;
  calendarDatesEl.innerHTML = "";
  selectedDates = [];

  for (let i = 0; i < firstDay; i++) {
    const blank = document.createElement("div");
    calendarDatesEl.appendChild(blank);
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const dateEl = document.createElement("div");
    dateEl.className = "calendar-date";
    dateEl.textContent = i;

    dateEl.addEventListener("mousedown", () => {
      isDragging = true;
      toggleDate(dateEl);
    });

    dateEl.addEventListener("mouseover", () => {
      if (isDragging) toggleDate(dateEl);
    });

    dateEl.addEventListener("mouseup", () => isDragging = false);

    calendarDatesEl.appendChild(dateEl);
  }
}

function changeMonth(offset) {
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
  const days = selectedDates
    .map(el => el.textContent.trim())
    .sort((a, b) => +a - +b);
  const start = days[0];
  const end = days[days.length - 1];
  document.getElementById("date-range").value =
    `วันที่ ${start} ${monthNames[currentMonth]} - ${end} ${monthNames[currentMonth]} ${currentYear}`;
  closeCalendar();
}
renderDaysOfWeek();
renderCalendar();
