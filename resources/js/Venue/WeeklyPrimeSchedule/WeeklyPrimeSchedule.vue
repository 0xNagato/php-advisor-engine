<script setup lang="ts">
import { ref, computed } from 'vue';
import { LoaderCircle, Save } from 'lucide-vue-next';
import formatTime from '@/utils/formatTime';

interface TimeSlot {
  start: string;
  end: string;
  is_prime: boolean;
  is_available: boolean;
  schedule_template_id: number | null;
}

interface WeeklySchedule {
  [key: string]: TimeSlot[] | 'closed';
}

interface MingleData {
  earliestStartTime: string;
  latestEndTime: string;
  weeklySchedule: WeeklySchedule;
  selectedTimeSlots: Record<string, boolean[]>;
  openDays: Record<string, 'open' | 'closed'>;
}

interface Props {
  wire: {
    save: (selectedTimeSlots: Record<string, boolean[]>) => Promise<{ success: boolean; message: string }>;
  };
  mingleData: MingleData;
}

const props = defineProps<Props>();

const { wire, mingleData } = props;

const isSaving = ref(false);
const selectedTimeSlots = ref<Record<string, boolean[]>>(mingleData.selectedTimeSlots);
const weeklySchedule: WeeklySchedule = mingleData.weeklySchedule;
const openDays: Record<string, 'open' | 'closed'> = mingleData.openDays;

const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const;
type DayOfWeek = typeof daysOfWeek[number];

const times = computed((): string[] => {
  const allTimes = new Set<string>();
  Object.values(weeklySchedule).forEach(daySchedule => {
    if (Array.isArray(daySchedule)) {
      daySchedule.forEach(slot => {
        allTimes.add(slot.start);
      });
    }
  });
  return Array.from(allTimes).sort();
});

const formatDay = (day: DayOfWeek): string => {
  return day.slice(0, 3).toUpperCase();
};

const isTimeSlotAvailable = (day: DayOfWeek, time: string): boolean => {
  if (openDays[day] === 'closed' || weeklySchedule[day] === 'closed') {
    return false;
  }
  const daySchedule = weeklySchedule[day] as TimeSlot[];
  return daySchedule.some(slot => slot.start === time && slot.is_available);
};

const toggleTimeSlot = (day: DayOfWeek, timeIndex: number): void => {
  if (isTimeSlotAvailable(day, times.value[timeIndex])) {
    selectedTimeSlots.value[day][timeIndex] = !selectedTimeSlots.value[day][timeIndex];
  }
};

const saveWeeklySchedule = async (): Promise<void> => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;
    const result = await wire.save(selectedTimeSlots.value);
    console.log(result.message);
  } catch (error) {
    console.error('Error saving weekly schedule:', error);
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <div class="mx-auto">
    <div class="mb-4 flex items-center justify-end">
      <button
        class="flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
        :disabled="isSaving"
        @click="saveWeeklySchedule"
      >
        <LoaderCircle
          v-if="isSaving"
          class="mr-2 size-4 animate-spin"
        />
        <Save
          v-else
          class="mr-2 size-4"
        />
        Save
      </button>
    </div>
    <div class="-mx-4 overflow-hidden rounded-lg shadow-lg sm:mx-auto">
      <div class="grid grid-cols-[80px_repeat(7,_minmax(0,_1fr))] items-center bg-white">
        <div class="p-2 text-center text-xs font-medium uppercase sm:text-sm" />
        <div
          v-for="day in daysOfWeek"
          :key="day"
          class="p-2 text-center"
        >
          <div class="text-xs font-semibold sm:text-sm">
            {{ formatDay(day) }}
          </div>
        </div>
      </div>
      <div class="grid grid-cols-[80px_repeat(7,_minmax(0,_1fr))] divide-x divide-y divide-white">
        <template
          v-for="(time, timeIndex) in times"
          :key="time"
        >
          <div class="bg-white py-4 text-center text-xs sm:text-sm">
            {{ formatTime(time, 'h:mm A') }}
          </div>
          <div
            v-for="day in daysOfWeek"
            :key="`${time}-${day}`"
            :class="[
              'flex items-center justify-center p-4',
              isTimeSlotAvailable(day, time)
                ? selectedTimeSlots[day][timeIndex]
                  ? 'cursor-pointer bg-indigo-50'
                  : 'cursor-pointer bg-white'
                : 'cursor-not-allowed bg-gray-50'
            ]"
            @click="toggleTimeSlot(day, timeIndex)"
          >
            <template v-if="isTimeSlotAvailable(day, time)">
              <input
                v-model="selectedTimeSlots[day][timeIndex]"
                type="checkbox"
                class="size-4 rounded text-indigo-600 sm:size-5"
                @click.stop
              >
            </template>
            <template v-else>
              <span class="text-xs text-gray-400 sm:text-sm">
                N/A
              </span>
            </template>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
