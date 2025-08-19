// // อัปเดตสาขาตามภูมิภาคที่เลือก
// function updateBranches() {
//   const regionSelect = document.getElementById("region");
//   const branchSelect = document.getElementById("branch");

//   const selectedRegion = regionSelect.value;

//   branchSelect.innerHTML = '<option disabled selected value>เลือกสาขา</option>';

//   // const branches = {
//   //   north: ["เชียงใหม่", "พะเยา"],
//   //   central: ["กรุงเทพฯ", "อ่างทอง"],
//   //   northeast: ["ขอนแก่น", "นครราชสีมา"],
//   //   west: ["กาญจนบุรี", "เพชรบุรี"],
//   //   south: ["สุราษฎร์ธานี", "นครศรีธรรมราช"]
//   // };

//   // if (branches[selectedRegion]) {
//   //   branches[selectedRegion].forEach(branch => {
//   //     const option = document.createElement("option");
//   //     option.value = branch;
//   //     option.textContent = `ดอมอินน์ สาขา ${branch}`;
//   //     branchSelect.appendChild(option);
//   //   });
//   // }
// }

// // เปลี่ยนจำนวนผู้เข้าพัก พร้อมจำกัดจำนวนและแสดงช่องเลือกอายุเด็ก
// function changeGuest(button, type, delta) {
//   const room = button.closest('.room');
//   const countElement = room.querySelector(`.${type}-count`);
//   let count = parseInt(countElement.textContent);

//   // ปรับจำนวน
//   count = Math.max(0, count + delta);
//   if (type === 'adult') count = Math.min(2, count);  // จำกัดผู้ใหญ่ 2
//   if (type === 'child') count = Math.min(1, count);  // จำกัดเด็ก 1

//   countElement.textContent = count;

//   // ถ้าเป็นเด็ก → แสดงช่องเลือกอายุ
//   if (type === 'child') {
//     const childAgeContainer = room.querySelector('.child-age-container');
//     const childAgeList = room.querySelector('.child-age-list');
//     childAgeList.innerHTML = '';

//     if (count > 0) {
//       childAgeContainer.style.display = 'block';
//       for (let i = 0; i < count; i++) {
//         const select = document.createElement('select');
//         select.name = `child-age-room${room.dataset.room}-${i}`;
//         select.innerHTML = `<option value="">เลือกอายุ</option>`;
//         for (let age = 1; age <= 12; age++) { // จำกัดอายุเด็กไม่เกิน 12
//           select.innerHTML += `<option value="${age}">${age} ปี</option>`;
//         }
//         childAgeList.appendChild(select);
//       }
//     } else {
//       childAgeContainer.style.display = 'none';
//     }
//   }
// }

// //  ปฏิทิน
// function updateGuestSummary() {
//   const adult = document.getElementById('adult-count').innerText;
//   const child = document.getElementById('child-count').innerText;
//   document.getElementById('guest-summary-input').value = `ผู้ใหญ่ ${adult}, เด็ก ${child} คน`;
// }
// const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
// const prices = [25, 26, 27];
// let currentMonth = new Date().getMonth();
// let currentYear = new Date().getFullYear();
// let isDragging = false;
// let selectedDates = [];

// const calendarDaysEl = document.getElementById("calendar-days");
// const calendarDatesEl = document.getElementById("calendar-dates");
// const monthLabel = document.getElementById("month-label");

// function openCalendar() {
//   document.getElementById("calendarOverlay").style.display = "block";
//   document.getElementById("calendarPopup").style.display = "block";
// }

// function closeCalendar() {
//   document.getElementById("calendarOverlay").style.display = "none";
//   document.getElementById("calendarPopup").style.display = "none";
// }

// function toggleDate(el) {
//   if (!el.classList.contains("calendar-date")) return;
//   if (el.classList.contains("selected")) {
//     el.classList.remove("selected");
//     selectedDates = selectedDates.filter(d => d !== el);
//   } else {
//     el.classList.add("selected");
//     selectedDates.push(el);
//   }
// }

// function renderDaysOfWeek() {
//   const days = ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"];
//   calendarDaysEl.innerHTML = "";
//   days.forEach(day => {
//     const el = document.createElement("div");
//     el.className = "calendar-day";
//     el.textContent = day;
//     calendarDaysEl.appendChild(el);
//   });
// }

// function renderCalendar() {
//   const firstDay = new Date(currentYear, currentMonth, 1).getDay();
//   const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

//   monthLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;
//   calendarDatesEl.innerHTML = "";
//   selectedDates = [];

//   for (let i = 0; i < firstDay; i++) {
//     const blank = document.createElement("div");
//     calendarDatesEl.appendChild(blank);
//   }

//   for (let i = 1; i <= daysInMonth; i++) {
//     const dateEl = document.createElement("div");
//     dateEl.className = "calendar-date";
//     dateEl.textContent = i;


//     dateEl.addEventListener("mousedown", () => {
//       isDragging = true;
//       toggleDate(dateEl);
//     });

//     dateEl.addEventListener("mouseover", () => {
//       if (isDragging) toggleDate(dateEl);
//     });

//     dateEl.addEventListener("mouseup", () => isDragging = false);

//     calendarDatesEl.appendChild(dateEl);
//   }
// }

// function changeMonth(offset) {
//   currentMonth += offset;
//   if (currentMonth < 0) {
//     currentMonth = 11;
//     currentYear--;
//   } else if (currentMonth > 11) {
//     currentMonth = 0;
//     currentYear++;
//   }
//   renderCalendar();
// }

// function confirmDate() {
//   if (selectedDates.length === 0) {
//     alert("กรุณาเลือกวันก่อน");
//     return;
//   }
//   const days = selectedDates.map(el => el.textContent.trim().split('฿')[0].trim()).sort((a, b) => +a - +b);
//   const start = days[0];
//   const end = days[days.length - 1];
//   document.getElementById("date-range").value = `วันที่ ${start} ${monthNames[currentMonth]} - ${end} ${monthNames[currentMonth]} ${currentYear}`;
//   closeCalendar();
// }
// function changeGuest(el, type, delta) {
//   const roomEl = el.closest('.room');
//   const countEl = roomEl.querySelector(`.${type}-count`);
//   let count = parseInt(countEl.innerText);

//   count = Math.max(0, count + delta);

//   if (type === 'adult') count = Math.min(2, count);
//   if (type === 'child') count = Math.min(2, count); // อนุญาตเด็กได้ 2 คน

//   countEl.innerText = count;

//   if (type === 'child') {
//     const container = roomEl.querySelector('.child-age-container');
//     const list = roomEl.querySelector('.child-age-list');
//     list.innerHTML = '';
//     if (count > 0) {
//       container.style.display = 'block';
//       for (let i = 0; i < count; i++) {
//         const select = document.createElement('select');
//         select.name = `child-age-room${roomEl.dataset.room}-${i}`;
//         select.innerHTML = `<option value="">เลือกอายุ</option>`;
//         for (let age = 1; age <= 12; age++) {
//           select.innerHTML += `<option value="${age}">${age} ปี</option>`;
//         }
//         list.appendChild(select);
//       }
//     } else {
//       container.style.display = 'none';
//     }
//   }

//   updateGuestSummary();
// }


// let roomCount = 1;

// function changeGuest(button, type, delta) {
//   const room = button.closest('.room');
//   const countElement = room.querySelector(`.${type}-count`);
//   let count = parseInt(countElement.textContent);

//   count = Math.max(0, count + delta);
//   countElement.textContent = count;

//   if (type === 'child') {
//     const childAgeContainer = room.querySelector('.child-age-container');
//     const childAgeList = room.querySelector('.child-age-list');

//     if (count > 0) {
//       childAgeContainer.style.display = 'block';
//       childAgeList.innerHTML = '';
//       for (let i = 0; i < count; i++) {
//         const input = document.createElement('input');
//         input.type = 'number';
//         input.min = 0;
//         input.max = 12;
//         input.placeholder = `เด็ก ${i + 1}`;
//         input.style.width = '60px';
//         input.style.marginRight = '5px';
//         childAgeList.appendChild(input);
//       }
//     } else {
//       childAgeContainer.style.display = 'none';
//       childAgeList.innerHTML = '';
//     }
//   }

//   updateGuestSummary();
// }

// function addRoom() {
//   roomCount++;
//   const container = document.getElementById('rooms-container');

//   const newRoom = document.createElement('div');
//   newRoom.classList.add('room');
//   newRoom.setAttribute('data-room', roomCount);
//   newRoom.innerHTML = `
//     <h4>ห้องที่ ${roomCount}</h4>
//     <div class="guest-group">
//       <span>ผู้ใหญ่</span>
//       <button type="button" onclick="changeGuest(this, 'adult', -1)">–</button>
//       <span class="adult-count">1</span>
//       <button type="button" onclick="changeGuest(this, 'adult', 1)">+</button>
//     </div>
//     <div class="guest-group">
//       <span>เด็ก</span>
//       <button type="button" onclick="changeGuest(this, 'child', -1)">–</button>
//       <span class="child-count">0</span>
//       <button type="button" onclick="changeGuest(this, 'child', 1)">+</button>
//     </div>
//     <div class="child-age-container" style="display:none; margin-top:8px;">
//       <label>อายุของเด็กแต่ละคน (ปี):</label>
//       <div class="child-age-list"></div>
//     </div>
//   `;
//   container.appendChild(newRoom);
//   updateGuestSummary();
// }

// function updateGuestSummary() {
//   let totalAdults = 0;
//   let totalChildren = 0;

//   document.querySelectorAll('.room').forEach(room => {
//     totalAdults += parseInt(room.querySelector('.adult-count').textContent);
//     totalChildren += parseInt(room.querySelector('.child-count').textContent);
//   });

//   document.getElementById('guest-summary-input').value =
//     `ผู้ใหญ่ ${totalAdults}, เด็ก ${totalChildren} คน`;
// }

// renderDaysOfWeek();
// renderCalendar();
