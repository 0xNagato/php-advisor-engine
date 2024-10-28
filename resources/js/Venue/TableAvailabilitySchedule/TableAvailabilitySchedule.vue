<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { LoaderCircle, Save, ArrowLeft, ArrowRight } from 'lucide-vue-next';
import formatTime from '@/utils/formatTime';

interface TimeSlot {
  start: string;
  end: string;
  [key: number]: number;
}

interface WeeklySchedule {
  [key: string]: TimeSlot[] | 'closed';
}

interface BusinessHours {
  [key: string]: {
    start: string;
    end: string;
  };
}

interface MingleData {
  weeklySchedule: WeeklySchedule;
  openDays: Record<string, 'open' | 'closed'>;
  currentDay: string;
  partySizes: number[];
  businessHours: BusinessHours;
  maxAvailableTables: number;
}

interface Props {
  wire: {
    saveAvailability: (
      updatedSchedule: WeeklySchedule,
    ) => Promise<{ success: boolean; message: string }>;
  };
  mingleData: MingleData;
}

const { wire, mingleData } = defineProps<Props>();

const daysOfWeek = [
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
  'Sunday',
] as const;
type DayOfWeek = (typeof daysOfWeek)[number];

const isSaving = ref(false);
const weeklySchedule = ref<WeeklySchedule>(mingleData.weeklySchedule);
const currentDay = ref<DayOfWeek>('Monday');
const partySizes = Object.fromEntries(
  Object.entries(mingleData.partySizes).filter(
    ([key]) => key !== 'Special Request',
  ),
);
const maxAvailableTables = mingleData.maxAvailableTables;

const currentDaySchedule = computed(() => {
  const daySchedule = weeklySchedule.value[currentDay.value.toLowerCase()];
  if (Array.isArray(daySchedule)) {
    return daySchedule.sort((a, b) => a.start.localeCompare(b.start));
  }
  return daySchedule;
});

const updateAvailability = (
  timeIndex: number,
  partySize: number,
  value: string,
) => {
  const daySchedule = weeklySchedule.value[currentDay.value.toLowerCase()];
  if (Array.isArray(daySchedule)) {
    const numValue = parseInt(value, 10);
    daySchedule[timeIndex][partySize] = Math.min(numValue, maxAvailableTables);
  }
};

const saveAvailability = async () => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;
    const result = await wire.saveAvailability(weeklySchedule.value);
    console.log(result.message);
  } catch (error) {
    console.error('Error saving table availability:', error);
  } finally {
    isSaving.value = false;
  }
};

onMounted(() => {
  const day = new URLSearchParams(window.location.search).get(
    'day',
  ) as DayOfWeek;

  if (day) {
    currentDay.value = day;
  }
});

function changeDay(day: DayOfWeek) {
  const url = new URL(window.location.href);
  currentDay.value = day;
  url.searchParams.set('day', day);
  window.history.pushState(null, '', url.toString());
}

function selectText(event: FocusEvent) {
  const input = event.target as HTMLInputElement;
  input.select();
}

const canNavigateBack = computed(() => currentDay.value !== daysOfWeek[0]);
const canNavigateForward = computed(
  () => currentDay.value !== daysOfWeek[daysOfWeek.length - 1],
);

function navigateDay(direction: 'prev' | 'next') {
  const currentIndex = daysOfWeek.indexOf(currentDay.value);
  const newIndex = direction === 'prev' ? currentIndex - 1 : currentIndex + 1;
  if (newIndex >= 0 && newIndex < daysOfWeek.length) {
    changeDay(daysOfWeek[newIndex]);
  }
}

const isExceedingLimit = (value: number) => value > maxAvailableTables;
</script>

<template>
  <div class="mx-auto">
    <div class="mb-4 flex items-center justify-between gap-4">
      <div class="inline-flex grow rounded-md shadow-sm" role="group">
        <button
          :disabled="!canNavigateBack"
          class="rounded-l-lg border border-gray-200 bg-white p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-900"
          @click="navigateDay('prev')"
        >
          <ArrowLeft class="size-4" />
        </button>
        <select
          :value="currentDay"
          class="grow border-y border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700"
          @change="
            changeDay(($event.target as HTMLSelectElement).value as DayOfWeek)
          "
        >
          <option v-for="day in daysOfWeek" :key="day" :value="day">
            {{ day }}
          </option>
        </select>
        <button
          :disabled="!canNavigateForward"
          class="rounded-r-lg border border-gray-200 bg-white p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-900"
          @click="navigateDay('next')"
        >
          <ArrowRight class="size-4" />
        </button>
      </div>
      <button
        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
        :disabled="isSaving"
        @click="saveAvailability"
      >
        <LoaderCircle v-if="isSaving" class="mr-2 size-4 animate-spin" />
        <Save v-else class="mr-2 size-4" />
        Save
      </button>
    </div>

    <div class="-mx-4 overflow-hidden rounded-lg bg-white shadow-lg sm:mx-auto">
      <div
        v-if="currentDaySchedule === 'closed'"
        class="bg-gray-100 p-4 text-center text-gray-500"
      >
        Closed
      </div>

      <div v-else-if="Array.isArray(currentDaySchedule)" class="grid">
        <div
          class="grid grid-cols-[100px_repeat(4,_minmax(0,_1fr))] items-center divide-x divide-y divide-white"
        >
          <div class="p-2 text-center text-xs font-medium uppercase sm:text-sm">
            Table Size
          </div>
          <div
            v-for="size in partySizes"
            :key="size"
            class="p-2 text-center font-medium uppercase"
          >
            <span
              class="inline-flex size-8 items-center justify-center rounded-full border-2 border-gray-300"
            >
              {{ size }}
            </span>
          </div>
        </div>

        <div
          v-for="(slot, index) in currentDaySchedule"
          :key="slot.start"
          class="grid grid-cols-[100px_repeat(4,_minmax(0,_1fr))] items-center divide-x divide-y divide-white"
        >
          <div class="p-2 text-center text-xs sm:text-sm">
            {{ formatTime(slot.start, 'h:mm A') }}
          </div>
          <div
            v-for="size in partySizes"
            :key="size"
            class="bg-indigo-50 p-2 text-center"
          >
            <input
              type="number"
              :value="slot[size]"
              :class="[
                'w-full rounded border-gray-300 text-center',
                { 'border-red-500': isExceedingLimit(slot[size]) },
              ]"
              min="0"
              :max="maxAvailableTables"
              @focus="selectText"
              @input="
                (e) =>
                  updateAvailability(
                    index,
                    size,
                    (e.target as HTMLInputElement).value,
                  )
              "
              @contextmenu.prevent
            />
            <div
              v-if="isExceedingLimit(slot[size])"
              class="mt-1 text-xs text-red-500"
            >
              Max {{ maxAvailableTables }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
