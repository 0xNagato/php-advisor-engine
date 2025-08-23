// Site-specific JavaScript for PRIMA marketing pages
// This file contains vanilla JavaScript extracted from designer HTML files

// WOW.js will be initialized at the bottom after all DOM elements are ready

// Initialize Lucide icons safely
function initIcons() {
  try {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  } catch (e) {
    console.warn('Lucide init failed', e);
  }
}

// Toggle helper for any element with data-target / data-close
function wireCollapsibles() {
  document.querySelectorAll('[data-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-target');
      const panel = document.getElementById(id);
      const overlay = document.getElementById('modalOverlay');
      if (!panel) return;
      panel.classList.add('open');
      overlay.classList.add('open');
      overlay.onclick = function () {
        panel.classList.remove('open');
        overlay.classList.remove('open');
      };
      initIcons();
    });
  });

  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-close');
      const panel = document.getElementById(id);
      const overlay = document.getElementById('modalOverlay');
      if (panel) {
        panel.classList.remove('open');
        // Wait for transition to finish before hiding overlay
        setTimeout(() => {
          if (overlay) { overlay.classList.remove('open'); }
        }, 400);
      } else {
        if (overlay) { overlay.classList.remove('open'); }
      }
    });
  });
}

// Mobile menu logic
function wireMobileMenu() {
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  const closeMobileMenu = document.getElementById('closeMobileMenu');
  const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

  if (mobileMenuBtn && mobileMenu && mobileMenuOverlay) {
    mobileMenuBtn.addEventListener('click', function () {
      mobileMenu.classList.remove('translate-x-full');
      mobileMenuOverlay.classList.remove('hidden');
      initIcons();
    });
  }

  if (closeMobileMenu && mobileMenu && mobileMenuOverlay) {
    closeMobileMenu.addEventListener('click', function () {
      mobileMenu.classList.add('translate-x-full');
      mobileMenuOverlay.classList.add('hidden');
    });
  }

  // Close menu when clicking overlay
  if (mobileMenuOverlay && mobileMenu) {
    mobileMenuOverlay.addEventListener('click', function () {
      mobileMenu.classList.add('translate-x-full');
      mobileMenuOverlay.classList.add('hidden');
    });
  }

  // Prevent closing when clicking inside menu
  if (mobileMenu) {
    mobileMenu.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }
}

// FAQ accordion functionality
function wireFAQs() {
  const faqToggles = document.querySelectorAll('.faq-toggle');

  faqToggles.forEach(function (btn, idx) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();

      // Close all other FAQ items (accordion behavior)
      faqToggles.forEach(function (b, i) {
        if (b !== btn) {
          b.setAttribute('aria-expanded', 'false');
          const answer = b.parentElement.querySelector('.faq-answer');
          if (answer) {
            answer.classList.add('hidden');
            answer.classList.remove('block');
            answer.style.display = 'none';
          }
          // Rotate chevron back to default
          const icon = b.querySelector('i[data-lucide="chevron-down"]');
          if (icon) {
            icon.style.transform = 'rotate(0deg)';
          }
        }
      });

      // Toggle the clicked FAQ item
      const isExpanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', (!isExpanded).toString());
      const answer = btn.parentElement.querySelector('.faq-answer');

      if (answer) {
        if (isExpanded) {
          // Close this item
          answer.classList.add('hidden');
          answer.classList.remove('block');
          answer.style.display = 'none';
        } else {
          // Open this item
          answer.classList.remove('hidden');
          answer.classList.add('block');
          answer.style.display = 'block';
        }
      }

      // Rotate chevron for clicked item
      const icon = btn.querySelector('i[data-lucide="chevron-down"]');
      if (icon) {
        icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
      }

      // Refresh icons after DOM changes
      setTimeout(() => {
        initIcons();
      }, 100);
    });
  });

  // Open first FAQ by default
  setTimeout(() => {
    const firstBtn = document.querySelector('.faq-toggle');
    if (firstBtn) {
      firstBtn.setAttribute('aria-expanded', 'true');
      const firstAnswer = firstBtn.parentElement.querySelector('.faq-answer');
      if (firstAnswer) {
        firstAnswer.classList.remove('hidden');
        firstAnswer.classList.add('block');
        firstAnswer.style.display = 'block';
      }
      // Rotate first chevron
      const firstIcon = firstBtn.querySelector('i[data-lucide="chevron-down"]');
      if (firstIcon) {
        firstIcon.style.transform = 'rotate(180deg)';
      }
      initIcons();
    }
  }, 500);
}

// Boot all site functionality
window.addEventListener('DOMContentLoaded', () => {
  wireCollapsibles();
  wireMobileMenu();
  wireFAQs();
  initIcons();

  // Initialize WOW.js animations once everything is ready
  if (typeof WOW !== 'undefined') {
    new WOW().init();
  }
});
