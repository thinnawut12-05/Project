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