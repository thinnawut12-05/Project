// แสดง popup
function showPopup(message, color = 'red') {
  const popup = document.createElement('div');
  popup.innerText = message;
  popup.style.position = 'fixed';
  popup.style.top = '20px';
  popup.style.left = '50%';
  popup.style.transform = 'translateX(-50%)';
  popup.style.background = color === 'red' ? '#f44336' : '#4CAF50';
  popup.style.color = 'white';
  popup.style.padding = '16px 32px';
  popup.style.borderRadius = '8px';
  popup.style.zIndex = '9999';
  popup.style.fontSize = '18px';
  popup.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
  document.body.appendChild(popup);
  setTimeout(() => {
    popup.style.transition = 'opacity 0.5s';
    popup.style.opacity = 0;
    setTimeout(() => document.body.removeChild(popup), 500);
  }, 2000);
}

// toggle password
document.addEventListener('DOMContentLoaded', function() {
  // password field
  const togglePasswordBtn = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');
  if (togglePasswordBtn && passwordInput && eyeIcon) {
    togglePasswordBtn.addEventListener('click', function() {
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeIcon.innerHTML = `
          <ellipse cx="12" cy="12" rx="10" ry="6"/>
          <circle cx="12" cy="12" r="2"/>
        `;
      } else {
        passwordInput.type = "password";
        eyeIcon.innerHTML = `
          <ellipse cx="12" cy="12" rx="10" ry="6"/>
          <circle cx="12" cy="12" r="2"/>
          <line x1="4" y1="20" x2="20" y2="4"/>
        `;
      }
    });
  }

  // confirm password field
  const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
  const confirmPasswordInput = document.getElementById('confirm-password');
  const eyeIconConfirm = document.getElementById('eyeIconConfirm');
  if (toggleConfirmPasswordBtn && confirmPasswordInput && eyeIconConfirm) {
    toggleConfirmPasswordBtn.addEventListener('click', function() {
      if (confirmPasswordInput.type === "password") {
        confirmPasswordInput.type = "text";
        eyeIconConfirm.innerHTML = `
          <ellipse cx="12" cy="12" rx="10" ry="6"/>
          <circle cx="12" cy="12" r="2"/>
        `;
      } else {
        confirmPasswordInput.type = "password";
        eyeIconConfirm.innerHTML = `
          <ellipse cx="12" cy="12" rx="10" ry="6"/>
          <circle cx="12" cy="12" r="2"/>
          <line x1="4" y1="20" x2="20" y2="4"/>
        `;
      }
    });
  }
});


