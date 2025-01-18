<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Keyboard, Pagination } from 'swiper/modules';
import type { Swiper as SwiperType } from 'swiper';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

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

const onSlideChange = (swiper: SwiperType) => {
  const newIndex = swiper.activeIndex;
  if (newIndex > mingleData.currentPage) {
    wire.nextPage();
  } else {
    wire.prevPage();
  }
};
</script>

<template>
  <div class="comic-strip">
    <Swiper
      :modules="[Navigation, Pagination, Keyboard]"
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
      class="h-full [--swiper-pagination-bullet-horizontal-gap:6px] [--swiper-pagination-bullet-inactive-color:#5045E6] [--swiper-pagination-bullet-inactive-opacity:0.2] [--swiper-pagination-bullet-size:10px] [--swiper-theme-color:#5045E6]"
      @slide-change="onSlideChange"
    >
      <SwiperSlide v-for="(page, index) in mingleData.pages" :key="index">
        <img
          :src="page"
          :alt="`Comic page ${index + 1}`"
          class="mx-auto h-auto w-full max-w-3xl"
          :loading="index === 0 ? 'eager' : 'lazy'"
        />
      </SwiperSlide>
    </Swiper>
  </div>
</template>
