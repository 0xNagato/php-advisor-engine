document.addEventListener('alpine:init', () => {
  Alpine.data('modalHandler', () => ({}));
});

/* menu */
document.addEventListener('DOMContentLoaded', () => {
  const navButton = document.querySelector('.nav_button');
  const navMenu = document.querySelector('.nav_menu');

  if (navButton && navMenu) {
    navButton.addEventListener('click', (event) => {
      event.stopPropagation();
      navMenu.classList.toggle('nav_menu--active');
    });

    document.addEventListener('click', (event) => {
      if (
        !navMenu.contains(event.target) &&
        !navButton.contains(event.target)
      ) {
        navMenu.classList.remove('nav_menu--active');
      }
    });
  }
});

/* fade in-out */
// Prevent scroll restoration and ensure page starts at top
if ('scrollRestoration' in history) {
  history.scrollRestoration = 'manual';
}

document.addEventListener('DOMContentLoaded', () => {
  // Force scroll to top on page load
  window.scrollTo(0, 0);

  const observerOptions = {
    root: null,
    threshold: 0.1,
    rootMargin: '50px',
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        // Add a small delay before adding the class
        requestAnimationFrame(() => {
          entry.target.classList.add('fade-in-active');
        });
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  // Wrap the observation in requestAnimationFrame
  requestAnimationFrame(() => {
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((el) => observer.observe(el));
  });
});

/* scroll gallery */
document.addEventListener('DOMContentLoaded', () => {
  const scrollSection = document.querySelector('.scroll-section');
  const imageContainers = document.querySelectorAll('.gallery_item');

  // Add null check before proceeding
  if (!scrollSection || !imageContainers.length) return;

  window.addEventListener('scroll', () => {
    const sectionTop = scrollSection.offsetTop;
    const sectionHeight = scrollSection.offsetHeight;
    const scrollY = window.scrollY;
    const windowHeight = window.innerHeight;

    // Detect if the user is in the section
    if (
      scrollY + windowHeight > sectionTop &&
      scrollY < sectionTop + sectionHeight
    ) {
      const relativeScroll = scrollY - sectionTop;

      // Progress for the gradient
      const progress = Math.min(
        1,
        (scrollY + windowHeight - sectionTop) / (sectionHeight / 2),
      );

      // Color interpolation for the background
      const startColor1 = [123, 52, 241];
      const startColor2 = [57, 84, 199];
      const endColor1 = [43, 93, 137];
      const endColor2 = [88, 172, 112];

      const interpolatedColor1 = startColor1.map((start, i) =>
        Math.round(start + (endColor1[i] - start) * progress),
      );
      const interpolatedColor2 = startColor2.map((start, i) =>
        Math.round(start + (endColor2[i] - start) * progress),
      );

      // Apply the interpolated gradient
      scrollSection.style.background = `linear-gradient(rgb(${interpolatedColor1.join(',')}) 0%, rgb(${interpolatedColor2.join(',')}) 100%)`;

      // Rotate the containers
      const rotationDegree = relativeScroll * 0.02; // Adjust speed
      imageContainers.forEach((container, index) => {
        const rotationOffset = index * 0; // Slightly change the rotation of each container
        container.style.transform = `rotate(${rotationDegree + rotationOffset}deg)`;
      });
    }
  });
});
