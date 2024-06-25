// import Swiper JS
import Swiper from 'swiper';
import { Pagination } from 'swiper/modules';
// import Swiper styles
import 'swiper/css';
import 'swiper/css/pagination';

const swiper = new Swiper('.testimonial-swiper', {
  modules: [Pagination],
  loop: true,
  centeredSlides: true,
  autoplay: {
    delay: 2500,
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


$(document).ready(function() {

  /* Section 4  Slider js code Start */
  var slider4_active = false;
  window.addEventListener('resize', function(event) {
    if (window.outerWidth < 769) {
      $('.section4_slider_js').slick({
        dots: false,
        speed: 500,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: false,
        infinite: true,
        cssEase: 'linear',
        prevArrow: '<button class="slick-arrow slick-prev"></button>',
        nextArrow: '<button class="slick-arrow slick-next"></button>',
        responsive: [{
          breakpoint: 768,
          settings: {
            speed: 300,
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: true,
          },
        }],
      });
      slider4_active = true;
    } else {
      if (slider4_active == true) {
        $('.section4_slider_js').slick('unslick');
        slider4_active = false;
      }
    }
  });
  if (window.outerWidth < 769) {
    $('.section4_slider_js').slick({
      dots: false,
      speed: 500,
      slidesToShow: 1,
      slidesToScroll: 1,
      autoplay: false,
      infinite: true,
      cssEase: 'linear',
      prevArrow: '<button class="slick-arrow slick-prev"></button>',
      nextArrow: '<button class="slick-arrow slick-next"></button>',
      responsive: [{
        breakpoint: 768,
        settings: {
          speed: 300,
          slidesToShow: 1,
          slidesToScroll: 1,
          dots: true,
        },
      }],
    });
    slider4_active = true;
  } else {
    if (slider4_active == true) {
      $('.section4_slider_js').slick('unslick');
      slider4_active = false;
    }
  }
  /* Section 4 Slider js code End*/


  /* section 8 js Start */
  $('.section8_js_trigger').click(function() {
    var current_tab = $(this).attr('data_tab');
    $(this).parents('section').find('.section8_js_trigger').removeClass('active');
    $(this).addClass('active');
    $(this).parents('section').find('.section8_js_trigger_item').hide();
    $(this).parents('section').find('.section8_js_trigger_item').removeClass('active');
    $(this).parents('section').find('.section8_js_trigger_item[data_tab="' + current_tab + '"]').show();
    $(this).parents('section').find('.section8_js_trigger_item[data_tab="' + current_tab + '"]').addClass('active');
  });

  /* section 8 js End */


  /* Section 9  Slider js code Start */
  var slider9_active = false;
  window.addEventListener('resize', function(event) {
    if (window.outerWidth < 769) {
      $('.section9_slider_js').slick({
        dots: false,
        speed: 500,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: false,
        infinite: true,
        cssEase: 'linear',
        prevArrow: '<button class="slick-arrow slick-prev"></button>',
        nextArrow: '<button class="slick-arrow slick-next"></button>',
        responsive: [{
          breakpoint: 768,
          settings: {
            speed: 300,
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: true,
          },
        }],
      });
      slider9_active = true;
    } else {
      if (slider9_active == true) {
        $('.section9_slider_js').slick('unslick');
        slider9_active = false;
      }
    }
  });
  if (window.outerWidth < 769) {
    $('.section9_slider_js').slick({
      dots: false,
      speed: 500,
      slidesToShow: 1,
      slidesToScroll: 1,
      autoplay: false,
      infinite: true,
      cssEase: 'linear',
      prevArrow: '<button class="slick-arrow slick-prev"></button>',
      nextArrow: '<button class="slick-arrow slick-next"></button>',
      responsive: [{
        breakpoint: 768,
        settings: {
          speed: 300,
          slidesToShow: 1,
          slidesToScroll: 1,
          dots: true,
        },
      }],
    });
    slider9_active = true;
  } else {
    if (slider9_active == true) {
      $('.section9_slider_js').slick('unslick');
      slider9_active = false;
    }
  }
  /* Section 9 Slider js code End*/


  /* Section 10 Slider js code End*/

  $('.section10_slider_js').slick({
    dots: false,
    speed: 500,
    slidesToShow: 5,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 2000,
    infinite: true,
    cssEase: 'linear',
    prevArrow: '<button class="slick-arrow slick-prev"></button>',
    nextArrow: '<button class="slick-arrow slick-next"></button>',
    responsive: [{
      breakpoint: 915,
      settings: {
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: false,
        dots: true,
      },
    }],
  });
  /* Section 10 Slider js code End*/

  /* menu js code start */
  $(document).on('click', '.menu_trigger_js', function(e) {
    $(this).toggleClass('active');
    $(this).next().slideToggle();
  });
  /* menu js code end */


});
