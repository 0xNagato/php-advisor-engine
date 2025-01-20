<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Keyboard, Pagination, Zoom } from 'swiper/modules';
import type { Swiper as SwiperType } from 'swiper';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/zoom';
import { ref } from 'vue';

interface Props {
  wire: {
    nextPage: () => Promise<void>;
    prevPage: () => Promise<void>;
  };
  mingleData: {
    pages: string[];
    currentPage: number;
  };
}

const { wire, mingleData } = defineProps<Props>();

const isZoomed = ref(false);

const onSlideChange = (swiper: SwiperType) => {
  if (isZoomed.value) {
    swiper.zoom.out();
    isZoomed.value = false;
  }

  const newIndex = swiper.activeIndex;
  if (newIndex > mingleData.currentPage) {
    wire.nextPage();
  } else {
    wire.prevPage();
  }
};

const onZoomChange = (swiper: SwiperType, scale: number) => {
  isZoomed.value = scale > 1;
};
</script>

<template>
  <div class="comic-strip">
    <Swiper
      :modules="[Navigation, Pagination, Keyboard, Zoom]"
      :slides-per-view="1"
      :breakpoints="{
        '768': {
          slidesPerView: 2,
          spaceBetween: 20,
        },
      }"
      :space-between="30"
      navigation
      :pagination="{ clickable: true }"
      :keyboard="{ enabled: true }"
      :initial-slide="mingleData.currentPage"
      :zoom="{
        maxRatio: 3,
        minRatio: 1,
        toggle: true,
      }"
      class="h-full [--swiper-pagination-bullet-horizontal-gap:6px] [--swiper-pagination-bullet-inactive-color:#5045E6] [--swiper-pagination-bullet-inactive-opacity:0.2] [--swiper-pagination-bullet-size:10px] [--swiper-theme-color:#5045E6]"
      @slide-change="onSlideChange"
      @zoom-change="onZoomChange"
    >
      <SwiperSlide v-for="(page, index) in mingleData.pages" :key="index">
        <div class="swiper-zoom-container">
          <img
            :src="page"
            :alt="`Comic page ${index + 1}`"
            class="mx-auto h-auto w-full max-w-3xl touch-manipulation"
            :loading="index === 0 ? 'eager' : 'lazy'"
          />
        </div>
      </SwiperSlide>
    </Swiper>
  </div>
</template>

<style>
.swiper-zoom-container {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.touch-manipulation {
  touch-action: manipulation;
  -webkit-touch-callout: none;
}
</style>
