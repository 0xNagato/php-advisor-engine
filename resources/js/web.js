// import Swiper JS
import Swiper from 'swiper';
import { Autoplay, Pagination } from 'swiper/modules';
// import Swiper styles
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/autoplay';

// Import Plyr
import Plyr from 'plyr';
// Import Plyr styles
import 'plyr/dist/plyr.css';

// Initialize Plyr for the main video
const mainPlayer = new Plyr('.youtube-player-main', {
    youtube: {
        noCookie: true,
    },
});
mainPlayer.poster = '/assets/images/yt-thumbnail.png';

const swiper = new Swiper('.testimonial-swiper', {
    modules: [Pagination, Autoplay],
    loop: true,
    centeredSlides: true,
    autoplay: {
        delay: 4000,
        disableOnInteraction: false,
    },

    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },

    breakpoints: {
        // need proper breakpoints from 4 to 1, let's do 3 steps
        320: {
            slidesPerView: 1,
            spaceBetween: 10,
        },
        640: {
            slidesPerView: 2,
            spaceBetween: 20,
        },
        1024: {
            slidesPerView: 3,
            spaceBetween: 30,
        },

    },
});

document.addEventListener('alpine:init', () => {
    Alpine.data('modalHandler', () => ({
        player: null,

        init() {
            console.log('modalHandler initialized');

            // Initialize Plyr player
            this.player = new Plyr('.youtube-player-modal', {
                youtube: {
                    noCookie: true,
                },
            });

            this.player.poster = '/assets/images/yt-thumbnail.png';

            // Listen for the close-modal event and call pauseVideo if the video modal is closed
            window.addEventListener('close-modal', (event) => {
                if (event.detail.id === 'video') {
                    this.pauseVideo();
                }
            });
        },

        pauseVideo() {
            if (this.player) {
                this.player.pause();
            }
        },
    }));
});

// Note: jQuery code was removed from here because it's not used by the main web app
// Site-specific functionality is now in resources/js/site.js for marketing pages
