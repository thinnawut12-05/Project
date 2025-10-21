document.addEventListener('DOMContentLoaded', () => {
    const numRoomsInput = document.getElementById('num-rooms');
    const branchSelect = document.getElementById('branch');
    const regionSelect = document.getElementById('region');
    const provinceIdHiddenInput = document.getElementById('province_id_hidden');
    // checkinDateDisplay and checkoutDateDisplay are not needed here, calendar.js will handle them

    // Initial calls
    updateBranches(); // Ensure correct branches are visible
    
    // Set initial max rooms for the input based on PHP calculation
    const initialMaxRooms = parseInt(numRoomsInput.getAttribute('max')) || 1;
    numRoomsInput.setAttribute('max', initialMaxRooms);
    // Ensure the initial value doesn't exceed the initial max
    if (parseInt(numRoomsInput.value) > initialMaxRooms) {
        numRoomsInput.value = initialMaxRooms;
    }
    updateRoomsFromInput(); // Adjust displayed rooms based on initial num_rooms and max
    updateGuestSummary(); // Initial summary update


    // Event listener for region change
    regionSelect.addEventListener('change', function() {
        updateBranches(); // Update branches based on new region
        // Reset branch selection when region changes
        branchSelect.value = '';
        provinceIdHiddenInput.value = ''; // Also clear hidden input

        // Reset num-rooms max and value
        numRoomsInput.setAttribute('max', 1); // Default max rooms when no branch selected
        numRoomsInput.value = 1;
        updateRoomsFromInput(); // Re-adjust displayed rooms (will remove extra rooms)
    });

    // Event listener for branch change
    branchSelect.addEventListener('change', function() {
        const selectedProvinceId = this.value;
        provinceIdHiddenInput.value = selectedProvinceId; // Update hidden input

        if (selectedProvinceId === '') {
            numRoomsInput.setAttribute('max', 1);
            numRoomsInput.value = 1;
            updateRoomsFromInput();
            return;
        }

        // Make AJAX call to get max rooms for the selected province
        fetch(`get_available_rooms_count.php?province_id=${selectedProvinceId}`)
            .then(response => response.json())
            .then(data => {
                const maxRoomsForBranch = data.max_rooms;
                numRoomsInput.setAttribute('max', maxRoomsForBranch);
                // Adjust current num_rooms if it exceeds the new max
                if (parseInt(numRoomsInput.value) > maxRoomsForBranch) {
                    numRoomsInput.value = maxRoomsForBranch;
                }
                updateRoomsFromInput(); // Re-adjust displayed rooms
            })
            .catch(error => {
                console.error('Error fetching max rooms:', error);
                numRoomsInput.setAttribute('max', 1); // Fallback
                numRoomsInput.value = 1;
                updateRoomsFromInput();
            });
    });

    // If a province was selected on initial load via URL, ensure branch dropdown reflects it
    const urlParams = new URLSearchParams(window.location.search);
    const initialProvinceId = urlParams.get('province_id');
    if (initialProvinceId && !branchSelect.value) { // Only if not already set by PHP's `selected` attribute
        branchSelect.value = initialProvinceId;
        updateBranches(); // Re-run to ensure visibility and hidden input are correct
        // Max rooms for this initial province is already set by PHP.
    }

    // Child age selectors for initially loaded rooms
    document.querySelectorAll('.room').forEach(room => {
        const childCount = parseInt(room.querySelector('.child-count').textContent);
        if (childCount > 0) {
            generateChildAgeSelectors(room);
        }
    });

});


let roomCount = 0; // จะถูกตั้งค่าโดย updateRoomsFromInput()

function updateRoomsFromInput() {
    const numRoomsInput = document.getElementById('num-rooms');
    let desiredRoomCount = parseInt(numRoomsInput.value);

    if (isNaN(desiredRoomCount) || desiredRoomCount < 1) {
        desiredRoomCount = 1;
        numRoomsInput.value = 1;
    }
    const maxRooms = parseInt(numRoomsInput.getAttribute('max')) || 1; // Default to 1 if max is not set or invalid
    if (desiredRoomCount > maxRooms) {
        desiredRoomCount = maxRooms;
        numRoomsInput.value = maxRooms;
    }

    const container = document.getElementById('rooms-container');
    // ต้องระวังไม่ให้ลบ .room-input-group และ .guest-summary
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
  const guestSummary = document.querySelector('.guest-summary'); // อ้างอิง guestSummary

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

  // แทรกก่อน guestSummary เพื่อให้ guestSummary อยู่ล่างสุดเสมอ
  if (guestSummary) {
    container.insertBefore(newRoom, guestSummary);
  } else {
    container.appendChild(newRoom); // กรณีหา guestSummary ไม่เจอ (ไม่น่าจะเกิด)
  }
}

function changeGuest(button, type, delta) {
  const room = button.closest('.room');
  const countElement = room.querySelector(`.${type}-count`);
  let count = parseInt(countElement.textContent);
  let newCount = count + delta;

  // กำหนดจำนวนผู้ใหญ่และเด็กสูงสุดต่อห้องตามที่ต้องการ
  // (สมมติ ผู้ใหญ่สูงสุด 2 คน, เด็กสูงสุด 1 คนต่อห้อง)
  if (type === 'adult') {
    if (newCount < 1) newCount = 1;
    if (newCount > 200) newCount = 200; 
  }
  if (type === 'child') {
    if (newCount < 0) newCount = 0;
    if (newCount > 200) newCount = 200; 
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
      select.classList.add('child-age-select'); // เพิ่ม class เพื่อให้เลือกง่ายขึ้น
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
    const currentNumRooms = parseInt(document.getElementById('num-rooms').value);

    document.querySelectorAll('.room').forEach(room => {
        totalAdults += parseInt(room.querySelector('.adult-count').textContent);
        totalChildren += parseInt(room.querySelector('.child-count').textContent);
    });

    const summaryText = `ผู้ใหญ่ ${totalAdults}, เด็ก ${totalChildren} คน`;
    const summaryInput = document.getElementById('guest-summary-input');
    if (summaryInput) {
        summaryInput.value = summaryText;
    }

    // Update hidden inputs for the main form submission
    document.getElementById('total_adults_form_submit').value = totalAdults;
    document.getElementById('total_children_form_submit').value = totalChildren;
    document.getElementById('num_rooms_form_submit').value = currentNumRooms;

    // Update hidden inputs in each booking form within room cards (for individual booking buttons)
    const checkinDateVal = document.getElementById('start-date').value;
    const checkoutDateVal = document.getElementById('end-date').value;
    const provinceIdVal = document.getElementById('province_id_hidden').value;

    document.querySelectorAll('form.booking-form-item').forEach(form => {
        form.querySelector('.checkin_date_hidden').value = checkinDateVal;
        form.querySelector('.checkout_date_hidden').value = checkoutDateVal;
        form.querySelector('.num_rooms_hidden').value = currentNumRooms;
        form.querySelector('.total_adults_hidden').value = totalAdults;
        form.querySelector('.total_children_hidden').value = totalChildren;
        form.querySelector('.province_id_hidden_item').value = provinceIdVal;
    });
}

function updateBranches() {
    const regionSelect = document.getElementById('region');
    const branchSelect = document.getElementById('branch');
    const selectedRegionId = regionSelect.value;
    const provinceIdHiddenInput = document.getElementById('province_id_hidden');

    const branchOptions = branchSelect.getElementsByTagName('option');

    let hasValidSelectedBranch = false;
    for (let i = 0; i < branchOptions.length; i++) {
        const option = branchOptions[i];
        const regionIdOfBranch = option.getAttribute('data-region-id');

        if (option.value === "" || regionIdOfBranch === selectedRegionId) {
            option.style.display = '';
            if (option.selected && option.value !== "") {
                hasValidSelectedBranch = true;
            }
        } else {
            option.style.display = 'none';
            if (option.selected) { // If a currently selected option is now hidden
                option.selected = false; // Deselect it
            }
        }
    }
    // If after filtering, the currently selected branch is no longer valid, reset
    if (!hasValidSelectedBranch && branchSelect.value !== "") {
         branchSelect.value = ""; // Reset dropdown if invalid
         provinceIdHiddenInput.value = ''; // Also clear hidden input
    }
    // If no branch is selected (e.g., after region change or initial empty), ensure hidden input is clear
    if (branchSelect.value === "") {
        provinceIdHiddenInput.value = '';
    } else {
        provinceIdHiddenInput.value = branchSelect.value;
    }

    // Also reset num-rooms max and value if no valid branch is selected
    const numRoomsInput = document.getElementById('num-rooms');
    if (branchSelect.value === "" || !provinceIdHiddenInput.value) { // Check both dropdown and hidden input
        numRoomsInput.setAttribute('max', 1);
        numRoomsInput.value = 1;
        updateRoomsFromInput(); // Re-adjust rooms
    }
}