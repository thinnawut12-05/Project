// อัปเดตสาขาตามภูมิภาคที่เลือก
function updateBranches() {
  const regionSelect = document.getElementById("region");
  const branchSelect = document.getElementById("branch");

  const selectedRegion = regionSelect.value;

  branchSelect.innerHTML = '<option disabled selected value>เลือกสาขา</option>';

  // const branches = {
  //   north: ["เชียงใหม่", "พะเยา"],
  //   central: ["กรุงเทพฯ", "อ่างทอง"],
  //   northeast: ["ขอนแก่น", "นครราชสีมา"],
  //   west: ["กาญจนบุรี", "เพชรบุรี"],
  //   south: ["สุราษฎร์ธานี", "นครศรีธรรมราช"]
  // };

  // if (branches[selectedRegion]) {
  //   branches[selectedRegion].forEach(branch => {
  //     const option = document.createElement("option");
  //     option.value = branch;
  //     option.textContent = `ดอมอินน์ สาขา ${branch}`;
  //     branchSelect.appendChild(option);
  //   });
  // }
}

// เปลี่ยนจำนวนผู้เข้าพัก พร้อมจำกัดจำนวนและแสดงช่องเลือกอายุเด็ก
function changeGuest(type, delta) {
  const countElement = document.getElementById(`${type}-count`);
  let count = parseInt(countElement.innerText);

  // จำกัดค่าต่ำสุดที่ 0
  count = Math.max(0, count + delta);

  // จำกัดจำนวนผู้ใหญ่ไม่เกิน 2 คน
  if (type === 'adult') {
    count = Math.min(2, count);
  }

  // จำกัดจำนวนเด็กไม่เกิน 1 คน
  if (type === 'child') {
    count = Math.min(1, count);
  }

  countElement.innerText = count;

  updateGuestSummary();

  if (type === 'child') {
    const container = document.getElementById('child-age-container');
    const list = document.getElementById('child-age-list');
    list.innerHTML = '';

    if (count > 0) {
      container.style.display = 'block';

      for (let i = 0; i < count; i++) {
        const select = document.createElement('select');
        select.name = `child-age-${i}`;
        select.innerHTML = `<option value="">เลือกอายุ</option>`;
        for (let age = 1; age <= 12; age++) {
          select.innerHTML += `<option value="${age}">${age} ปี</option>`;
        }
        select.style.marginBottom = '5px';
        list.appendChild(select);
      }
    } else {
      container.style.display = 'none';
    }
  }
}

//  ปฏิทิน
function updateGuestSummary() {
  const adult = document.getElementById('adult-count').innerText;
  const child = document.getElementById('child-count').innerText;
  document.getElementById('guest-summary-input').value = `ผู้ใหญ่ ${adult}, เด็ก ${child} คน`;
}
const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    const prices = [25, 26, 27];
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
      const days = selectedDates.map(el => el.textContent.trim().split('฿')[0].trim()).sort((a, b) => +a - +b);
      const start = days[0];
      const end = days[days.length - 1];
      document.getElementById("date-range").value = `วันที่ ${start} ${monthNames[currentMonth]} - ${end} ${monthNames[currentMonth]} ${currentYear}`;
      closeCalendar();
    }

    renderDaysOfWeek();
    renderCalendar();
  
function changeGuest(type, delta) {
  const countElement = document.getElementById(`${type}-count`);
  let count = parseInt(countElement.innerText);

  // จำกัดค่าต่ำสุดที่ 0 หรือ 1
  if (type === 'room') {
    count = Math.max(1, count + delta);
    count = Math.min(5, count); // จำกัดไม่เกิน 5 ห้อง
  } else {
    count = Math.max(0, count + delta);

    if (type === 'adult') {
      count = Math.min(2, count); // จำกัดผู้ใหญ่ 2 คน
    }

    if (type === 'child') {
      count = Math.min(1, count); // จำกัดเด็ก 1 คน
    }
  }

  countElement.innerText = count;
  updateGuestSummary();

  // จัดการแสดงอายุเด็ก
  if (type === 'child') {
    const container = document.getElementById('child-age-container');
    const list = document.getElementById('child-age-list');
    list.innerHTML = '';

    if (count > 0) {
      container.style.display = 'block';
      for (let i = 0; i < count; i++) {
        const select = document.createElement('select');
        select.name = `child-age-${i}`;
        select.innerHTML = `<option value="">เลือกอายุ</option>`;
        for (let age = 1; age <= 12; age++) {
          select.innerHTML += `<option value="${age}">${age} ปี</option>`;
        }
        select.style.marginBottom = '5px';
        list.appendChild(select);
      }
    } else {
      container.style.display = 'none';
    }
  }
}
