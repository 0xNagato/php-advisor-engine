<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { LoaderCircle, Save, ArrowLeft, ArrowRight, CopyPlus, Files, ClipboardCopy } from 'lucide-vue-next';
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
    duplicateSchedule: (
      updatedSchedule: WeeklySchedule,
    ) => Promise<{ success: boolean; message: string }>;
    refresh: () => Promise<MingleData>;
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

const duplicateSchedule = async () => {
  if (isSaving.value || currentDaySchedule.value === 'closed') return;

  try {
    isSaving.value = true;
    const currentDayLower = currentDay.value.toLowerCase();

    const scheduleToSend = {
      [currentDayLower]: weeklySchedule.value[currentDayLower]
    };

    const result = await wire.duplicateSchedule(scheduleToSend);
    if (result.success) {
      const response = await wire.refresh();
      weeklySchedule.value = response.weeklySchedule;
    }
  } catch (error) {
    console.error('Error duplicating schedule:', error);
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

const showDuplicateModal = ref(false);

const handleDuplicateClick = () => {
  showDuplicateModal.value = true;
};

const confirmDuplicate = async () => {
  showDuplicateModal.value = false;
  await duplicateSchedule();
};
</script>

<template>
  <div class="mx-auto">
    <div class="flex items-center justify-between gap-4 mb-4">
      <div class="inline-flex rounded-md shadow-sm grow" role="group">
        <button
          :disabled="!canNavigateBack"
          class="p-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-900"
          @click="navigateDay('prev')"
        >
          <ArrowLeft class="size-4" />
        </button>
        <select
          :value="currentDay"
          class="px-3 py-2 text-sm font-medium text-gray-900 bg-white border-gray-200 grow border-y hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700"
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
          class="p-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-900"
          @click="navigateDay('next')"
        >
          <ArrowRight class="size-4" />
        </button>
      </div>
      <div class="flex gap-2 shrink-0">
        <button
          class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-gray-600 rounded-lg shrink-0 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50"
          :disabled="isSaving || currentDaySchedule === 'closed'"
          @click="handleDuplicateClick"
        >
          <CopyPlus class="size-4 sm:mr-2" />
          <span class="hidden sm:inline">Duplicate to All Days</span>
        </button>

        <button
          class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg shrink-0 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
          :disabled="isSaving"
          @click="saveAvailability"
        >
          <LoaderCircle v-if="isSaving" class="size-4 sm:mr-2" />
          <Save v-else class="size-4 sm:mr-2" />
          <span class="hidden sm:inline">Save</span>
        </button>
      </div>
    </div>

    <div class="-mx-4 overflow-hidden bg-white rounded-lg shadow-lg sm:mx-auto">
      <div
        v-if="currentDaySchedule === 'closed'"
        class="p-4 text-center text-gray-500 bg-gray-100"
      >
        Closed
      </div>

      <div v-else-if="Array.isArray(currentDaySchedule)" class="grid">
        <div
          class="grid grid-cols-[100px_repeat(4,_minmax(0,_1fr))] items-center divide-x divide-y divide-white"
        >
          <div class="p-2 text-xs font-medium text-center uppercase sm:text-sm">
            Table Size
          </div>
          <div
            v-for="size in partySizes"
            :key="size"
            class="p-2 font-medium text-center uppercase"
          >
            <span
              class="inline-flex items-center justify-center border-2 border-gray-300 rounded-full size-8"
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
          <div class="p-2 text-xs text-center sm:text-sm">
            {{ formatTime(slot.start, 'h:mm A') }}
          </div>
          <div
            v-for="size in partySizes"
            :key="size"
            class="p-2 text-center bg-indigo-50"
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

    <!-- Vue Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showDuplicateModal" class="fixed inset-0 z-40 transition-opacity bg-gray-500 bg-opacity-75" />
      </Transition>

      <Transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
        leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
      >
        <div
          v-if="showDuplicateModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          @click.self="showDuplicateModal = false"
        >
          <div class="relative w-full max-w-lg overflow-hidden transition-all transform bg-white rounded-lg shadow-xl">
            <div class="px-6 py-4">
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                Duplicate {{ currentDay }}'s Schedule
              </h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">
                  This will copy {{ currentDay }}'s table availability settings to all other open days. This includes:
                </p>
                <ul class="pl-5 mt-2 text-sm text-gray-500 list-disc">
                  <li>Available tables for each party size</li>
                </ul>
                <p class="mt-2 text-sm text-gray-500">
                  Are you sure you want to continue?
                </p>
              </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50">
              <button
                type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                @click="showDuplicateModal = false"
              >
                Cancel
              </button>
              <button
                type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                :disabled="isSaving"
                @click="confirmDuplicate"
              >
                <LoaderCircle v-if="isSaving" class="mr-2 size-4 animate-spin" />
                <span>Confirm</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
