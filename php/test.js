// รอให้หน้าเว็บโหลดเสร็จก่อนเริ่มทำงาน
document.addEventListener('DOMContentLoaded', () => {
  // เริ่มต้นสร้างห้องตามค่าเริ่มต้นใน input หรือมีอยู่แล้ว
  updateRoomsFromInput(); 
  updateGuestSummary();

  // ตรวจสอบและสร้าง selectors อายุเด็กสำหรับห้องที่มีอยู่
  document.querySelectorAll('.room').forEach(room => {
    const childCount = parseInt(room.querySelector('.child-count').textContent);
    if (childCount > 0) {
      generateChildAgeSelectors(room);
    }
  });
});

// นับจำนวนห้องเริ่มต้นจาก HTML ที่มีอยู่จริง (ควรจะมี 1 ห้องเริ่มต้น)
let roomCount = document.querySelectorAll('.room').length;

/**
 * (ใหม่) ฟังก์ชันสำหรับอัปเดตจำนวนห้องตามค่าในช่องกรอกตัวเลข
 */
function updateRoomsFromInput() {
    const numRoomsInput = document.getElementById('num-rooms');
    let desiredRoomCount = parseInt(numRoomsInput.value);

    // ตรวจสอบค่า min/max (ใน HTML ก็กำหนดไว้แล้ว แต่เช็คซ้ำเพื่อความปลอดภัย)
    if (isNaN(desiredRoomCount) || desiredRoomCount < 1) {
        desiredRoomCount = 1;
        numRoomsInput.value = 1;
    }
    const maxRooms = parseInt(numRoomsInput.getAttribute('max')) || 5; // อ่านค่า max จาก attribute
    if (desiredRoomCount > maxRooms) {
        desiredRoomCount = maxRooms;
        numRoomsInput.value = maxRooms;
    }

    const container = document.getElementById('rooms-container');
    const existingRooms = container.querySelectorAll('.room');
    const currentRoomCount = existingRooms.length;

    // ถ้าจำนวนห้องที่ต้องการน้อยกว่าห้องที่มีอยู่ ให้ลบห้องส่วนเกิน
    if (desiredRoomCount < currentRoomCount) {
        for (let i = currentRoomCount - 1; i >= desiredRoomCount; i--) {
            existingRooms[i].remove();
        }
    } 
    // ถ้าจำนวนห้องที่ต้องการมากกว่าห้องที่มีอยู่ ให้เพิ่มห้องใหม่
    else if (desiredRoomCount > currentRoomCount) {
        for (let i = currentRoomCount; i < desiredRoomCount; i++) {
            addRoomInternal(i + 1); // ส่งเลขห้องถัดไป
        }
    }
    // อัปเดตตัวแปร global roomCount
    roomCount = desiredRoomCount; 
    updateRoomNumbers(); // อัปเดตหมายเลขห้องให้ถูกต้อง
    updateGuestSummary();
}


/**
 * ฟังก์ชันเพิ่มห้องใหม่ (ถูกเรียกโดย updateRoomsFromInput เท่านั้น)
 * @param {number} newRoomNumber - หมายเลขห้องที่จะสร้าง
 */
function addRoomInternal(newRoomNumber) {
  const container = document.getElementById('rooms-container');
  const guestSummary = document.querySelector('.guest-summary'); // อ้างอิงส่วน guest-summary

  const newRoom = document.createElement('div');
  newRoom.classList.add('room');
  newRoom.setAttribute('data-room', newRoomNumber);

  newRoom.innerHTML = `
        <div class="room-header">
            <h4>ห้องที่ ${newRoomNumber}</h4>
            <!-- ปุ่มลบจะถูกควบคุมด้วยจำนวนห้องใน input แทน -->
        </div>
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

  // แทรกห้องใหม่เข้าไปใน container (ก่อนส่วน guest-summary)
  container.insertBefore(newRoom, guestSummary);
}


/**
 * (ลบฟังก์ชัน addRoom เดิม)
 * (ลบฟังก์ชัน deleteRoom เดิม)
 * (ลบฟังก์ชัน updateRoomNumbers เดิม)
 *
 * เหตุผล: ฟังก์ชันเหล่านี้ถูกแทนที่ด้วย updateRoomsFromInput()
 * ซึ่งจัดการการสร้าง/ลบ/อัปเดตหมายเลขห้องทั้งหมดตามค่า input แล้ว
 */


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
    if (newCount > 2) newCount = 2; // จำกัดผู้ใหญ่ไม่เกิน 2 คนต่อห้อง
  }
  if (type === 'child') {
    if (newCount < 0) newCount = 0;
    if (newCount > 1) newCount = 1; // จำกัดเด็กไม่เกิน 1 คนต่อห้อง
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

// โค้ดปฏิทิน (ไม่มีการเปลี่ยนแปลงในส่วนนี้)
const monthNames = [
  "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let isDragging = false;
let selectedDates = [];

// เก็บวันปัจจุบัน
const today = new Date();
today.setHours(0, 0, 0, 0); // ตั้งค่าเวลาเป็น 00:00:00 เพื่อเปรียบเทียบเฉพาะวันที่

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
  
  // *** เพิ่มการตรวจสอบวันที่ในอดีตที่นี่ ***
  if (el.classList.contains("past-date")) {
    return; // ถ้าเป็นวันที่ในอดีต จะไม่ทำอะไร
  }

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

    // *** เพิ่มการตรวจสอบและคลาสสำหรับวันที่ในอดีต ***
    const dateToCheck = new Date(currentYear, currentMonth, i);
    dateToCheck.setHours(0, 0, 0, 0); // ตั้งค่าเวลาเป็น 00:00:00

    if (dateToCheck < today) {
      dateEl.classList.add("past-date"); // เพิ่มคลาส "past-date"
    }

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
  // ไม่ให้ย้อนกลับไปเดือนในอดีต ถ้าเดือนปัจจุบันคือเดือนของวันนี้
  const newMonth = currentMonth + offset;
  const newDate = new Date(currentYear, newMonth, 1);

  // ถ้าเดือนใหม่ย้อนไปก่อนเดือนปัจจุบัน และเป็นปีเดียวกัน
  if (newDate < new Date(today.getFullYear(), today.getMonth(), 1)) {
    return; // ไม่อนุญาตให้เปลี่ยน
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
  
  // ตรวจสอบว่าวันที่เริ่มต้นไม่เป็นวันที่ในอดีต (ซ้ำซ้อนเพื่อความมั่นใจ)
  const firstSelectedDateEl = selectedDates.sort((a,b) => +a.textContent - +b.textContent)[0];
  const firstSelectedDay = parseInt(firstSelectedDateEl.textContent);
  const checkInDate = new Date(currentYear, currentMonth, firstSelectedDay);
  checkInDate.setHours(0,0,0,0);

  if (checkInDate < today) {
    alert("ไม่สามารถเลือกวันที่เช็คอินย้อนหลังได้ กรุณาเลือกวันที่ปัจจุบันหรืออนาคต");
    selectedDates.forEach(el => el.classList.remove("selected")); // ลบการเลือกทั้งหมด
    selectedDates = [];
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