document.addEventListener('DOMContentLoaded', () => {
  updateRoomsFromInput(); 
  updateGuestSummary();

  document.querySelectorAll('.room').forEach(room => {
    const childCount = parseInt(room.querySelector('.child-count').textContent);
    if (childCount > 0) {
      generateChildAgeSelectors(room);
    }
  });
  renderDaysOfWeek(); // เพิ่มตรงนี้
  renderCalendar(); // เพิ่มตรงนี้
});

let roomCount = document.querySelectorAll('.room').length;

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
    roomCount = desiredRoomCount; 
    updateGuestSummary();
}

function addRoomInternal(newRoomNumber) {
  const container = document.getElementById('rooms-container');
  const guestSummary = document.querySelector('.guest-summary');

  const newRoom = document.createElement('div');
  newRoom.classList.add('room');
  newRoom.setAttribute('data-room', newRoomNumber);

  newRoom.innerHTML = `
        <div class="room-header">
            <h4>ห้องที่ ${newRoomNumber}</h4>
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

  container.insertBefore(newRoom, guestSummary);
}

function changeGuest(button, type, delta) {
  const room = button.closest('.room');
  const countElement = room.querySelector(`.${type}-count`);
  let count = parseInt(countElement.textContent);
  let newCount = count + delta;

  if (type === 'adult') {
    if (newCount < 1) newCount = 1;
    if (newCount > 2) newCount = 2;
  }
  if (type === 'child') {
    if (newCount < 0) newCount = 0;
    if (newCount > 1) newCount = 1;
  }

  countElement.textContent = newCount;

  if (type === 'child') {
    generateChildAgeSelectors(room);
  }

  updateGuestSummary();
}

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
  
  // Update hidden inputs for adults and children
  const adultsHiddenInput = document.getElementById('adults');
  const childrenHiddenInput = document.getElementById('children');
  if (adultsHiddenInput) adultsHiddenInput.value = totalAdults;
  if (childrenHiddenInput) childrenHiddenInput.value = totalChildren;
}


// ================== Calendar Management ==================



// ลบฟังก์ชัน toggleDate() เดิมออก ไม่ได้ใช้แล้ว
// renderDaysOfWeek(); // ย้ายไปใน DOMContentLoaded
// renderCalendar();   // ย้ายไปใน DOMContentLoaded
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

  // format เป็น YYYY-MM-DD
  const startDateISO = `${currentYear}-${String(currentMonth + 1).padStart(2, "0")}-${String(start).padStart(2, "0")}`;
  const endDateISO   = `${currentYear}-${String(currentMonth + 1).padStart(2, "0")}-${String(end).padStart(2, "0")}`;

  // เก็บค่าแบบ ISO ไว้ใน input (ส่งไป PHP)
  document.getElementById("start-date").value = startDateISO;
  document.getElementById("end-date").value = endDateISO;

  // ถ้าอยากโชว์เป็นภาษาไทยให้ user เห็น (ทำ span แยก)
  document.getElementById("start-date-display").textContent =
    `วันที่ ${start} ${monthNames[currentMonth]} ${currentYear}`;
  document.getElementById("end-date-display").textContent =
    `วันที่ ${end} ${monthNames[currentMonth]} ${currentYear}`;

  closeCalendar();
}
renderDaysOfWeek();
renderCalendar();

