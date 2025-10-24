/**
 * ฟังก์ชันใหม่สำหรับเปิด Modal และใส่ข้อมูลห้องพัก
 * @param {object} roomData - อ็อบเจกต์ที่เก็บข้อมูลห้องพัก
 */
function openRoomDetailsModal(roomData) {
    // 1. ใส่ข้อมูลตัวหนังสือลงใน element ของ Modal
    document.getElementById('modal-title').innerText = roomData.name;
    document.getElementById('modal-description').innerText = roomData.description;
    
    const featuresDiv = document.getElementById('modal-features');
    featuresDiv.innerHTML = `
        <div class="feature-item"><i class="fas fa-users"></i> ${roomData.capacity}</div>
        <div class="feature-item"><i class="fas fa-user-friends"></i> ${roomData.guests}</div>
        <div class="feature-item"><i class="fas fa-bed"></i> ${roomData.bed_type}</div>
    `;
    
    document.getElementById('modal-price').innerHTML = `<i class="fas fa-tag"></i> ฿${roomData.price}`;
    document.getElementById('modal-total').innerHTML = `
        <span>ยอดรวม</span>
        <span>฿${roomData.price}</span>
    `;

    // 2. สร้างสไลด์รูปภาพและจุด (dots) แบบไดนามิก
    const galleryDiv = document.getElementById('modal-gallery');
    const dotsDiv = document.getElementById('modal-dots');
    galleryDiv.innerHTML = ''; // เคลียร์รูปเก่าออกก่อน
    dotsDiv.innerHTML = '';    // เคลียร์จุดเก่าออกก่อน

    roomData.images.forEach((imageUrl, index) => {
        // สร้าง slide
        const slide = document.createElement('div');
        slide.className = 'gallery-slide' + (index === 0 ? ' active' : '');
        slide.innerHTML = `<img src="${imageUrl}" alt="${roomData.name} image ${index + 1}">`;
        galleryDiv.appendChild(slide);

        // สร้าง dot
        const dot = document.createElement('span');
        dot.className = 'dot' + (index === 0 ? ' active' : '');
        dot.onclick = () => currentSlide(index);
        dotsDiv.appendChild(dot);
    });

    // เพิ่มปุ่มซ้าย-ขวา
    galleryDiv.innerHTML += `
        <div class="gallery-nav">
            <button class="gallery-arrow" onclick="changeSlide(-1)">&#10094;</button>
            <button class="gallery-arrow" onclick="changeSlide(1)">&#10095;</button>
        </div>
    `;

    // 3. แสดง Modal และเริ่มต้นการทำงานของสไลด์
    document.getElementById('roomModal').style.display = 'flex';
    showSlide(0); // เริ่มสไลด์ที่รูปแรก
}


// --- โค้ดเดิมสำหรับควบคุม Gallery และปิด Modal (ไม่ต้องแก้ไข) ---
let slideIndex = 0;

function showSlide(n) {
    // ใช้ querySelectorAll เพราะ element ถูกสร้างแบบไดนามิก
    let slides = document.querySelectorAll("#modal-gallery .gallery-slide");
    let dots = document.querySelectorAll("#modal-dots .dot");
    
    if (slides.length === 0) return;

    if (n >= slides.length) { slideIndex = 0; }
    if (n < 0) { slideIndex = slides.length - 1; }
    
    slides.forEach(slide => slide.style.display = "none");
    dots.forEach(dot => dot.classList.remove("active"));
    
    slides[slideIndex].style.display = "block";
    dots[slideIndex].classList.add("active");
}

function changeSlide(n) {
    showSlide(slideIndex += n);
}

function currentSlide(n) {
    showSlide(slideIndex = n);
}

function closeModal() {
    const modal = document.getElementById('roomModal');
    if(modal) {
        modal.style.display = 'none';
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('roomModal');
    if (event.target == modal) {
        closeModal();
    }
}