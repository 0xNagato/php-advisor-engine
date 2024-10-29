<script setup lang="ts">
import { ref, computed } from 'vue';
import { ArrowLeft, ArrowRight, LoaderCircle, Save } from 'lucide-vue-next';
import dayjs from 'dayjs';
import formatTime from '@/utils/formatTime';

interface TimeSlot {
  start: string;
  end: string;
  schedule_prime: boolean;
  is_override: boolean;
  override_prime: boolean;
  schedule_template_id: number | null;
  is_available?: boolean;
}

interface DetailedSchedule {
  [key: string]:
    | 'closed'
    | {
        start_time: string;
        end_time: string;
        is_available: boolean;
        prime_time: boolean;
      }[];
}

interface MingleData {
  selectedTimeSlots: Record<string, boolean[]>;
  earliestStartTime: string;
  latestEndTime: string;
  daysToDisplay: number;
  timeSlots: Record<string, TimeSlot[]>;
  detailedSchedule: DetailedSchedule;
}

interface TimeSlotChanges {
  [date: string]: {
    [timeIndex: number]: boolean;
  };
}

const { wire, mingleData } = defineProps<{
  wire: {
    save: (
      selectedTimeSlots: Record<string, boolean[]>,
    ) => Promise<{ success?: boolean; message: string }>;
    on: (event: string, callback: () => void) => void;
    refresh: () => Promise<MingleData>;
  };
  mingleData: MingleData;
}>();

wire.on('upcoming-schedule-updated', async () => {
  isSaving.value = false;

  try {
    const response = await wire.refresh();

    selectedTimeSlots.value = response.selectedTimeSlots;
    originalTimeSlots.value = JSON.parse(
      JSON.stringify(response.selectedTimeSlots),
    );
    timeSlots.value = response.timeSlots;
    detailedSchedule.value = response.detailedSchedule;

    console.log('Schedule updated successfully');
  } catch (error) {
    console.error('Error refreshing schedule:', error);
  }
});

const isSaving = ref(false);
const selectedTimeSlots = ref<Record<string, boolean[]>>(
  mingleData.selectedTimeSlots,
);
const detailedSchedule = ref(mingleData.detailedSchedule);
const originalTimeSlots = ref(
  JSON.parse(JSON.stringify(mingleData.selectedTimeSlots)),
);
const today = dayjs().startOf('day');
const currentDate = ref(today);
const maxDate = ref(today.add(mingleData.daysToDisplay - 1, 'day'));
const timeSlots = ref<Record<string, TimeSlot[]>>(mingleData.timeSlots);

const getDaysInWeek = (startDate: dayjs.Dayjs): dayjs.Dayjs[] => {
  return Array(7)
    .fill(null)
    .map((_, i) => startDate.add(i, 'day'));
};

const days = computed(() => getDaysInWeek(currentDate.value));

const times = computed(() => {
  const allTimes = new Set<string>();
  Object.values(timeSlots.value).forEach((daySlots) => {
    daySlots.forEach((slot) => {
      allTimes.add(slot.start);
    });
  });
  return Array.from(allTimes).sort();
});

const formatDate = (date: dayjs.Dayjs): { day: string; date: string } => {
  return {
    day: date.format('ddd').toUpperCase(),
    date: date.format('D'),
  };
};

const canNavigateBack = computed(() => {
  return currentDate.value.isAfter(today, 'day');
});

const canNavigateForward = computed(() => {
  return currentDate.value.add(6, 'day').isBefore(maxDate.value, 'day');
});

const navigateWeek = (direction: 'prev' | 'next') => {
  if (
    (direction === 'next' && canNavigateForward.value) ||
    (direction === 'prev' && canNavigateBack.value)
  ) {
    currentDate.value = currentDate.value.add(
      direction === 'next' ? 7 : -7,
      'day',
    );
  }
};

const isTimeSlotAvailable = (day: dayjs.Dayjs, time: string): boolean => {
  if (day.isAfter(maxDate.value)) {
    return false;
  }
  const dateString = day.format('YYYY-MM-DD');
  const timeSlot = timeSlots.value[dateString]?.find(
    (slot) => slot.start === time,
  );

  return Boolean(timeSlot?.schedule_template_id);
};

const toggleTimeSlot = (date: dayjs.Dayjs, timeIndex: number) => {
  const dateString = date.format('YYYY-MM-DD');
  const slot = timeSlots.value[dateString]?.[timeIndex];

  if (!slot) return;

  // Update selectedTimeSlots
  selectedTimeSlots.value = {
    ...selectedTimeSlots.value,
    [dateString]: selectedTimeSlots.value[dateString].map((value, index) =>
      index === timeIndex ? !value : value,
    ),
  };

  // Update timeSlots to reflect the change
  timeSlots.value = {
    ...timeSlots.value,
    [dateString]: timeSlots.value[dateString].map((slot, index) =>
      index === timeIndex ? { ...slot, is_override: !slot.is_override } : slot,
    ),
  };
};

const saveReservationHours = async () => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;

    const changes = getTimeSlotChanges();
    if (Object.keys(changes).length === 0) {
      console.log('No changes to save');
      return;
    }

    // Log the changes before formatting
    console.log('Changes before formatting:', changes);

    // Send the full state for changed days
    const formattedChanges: Record<string, boolean[]> = {};
    Object.keys(changes).forEach((date) => {
      // Send the complete current state for the changed day
      formattedChanges[date] = selectedTimeSlots.value[date];
    });

    // Log the formatted changes
    console.log('Formatted changes:', formattedChanges);

    const result = await wire.save(formattedChanges);
    if (result.success) {
      originalTimeSlots.value = JSON.parse(
        JSON.stringify(selectedTimeSlots.value),
      );
    }
    console.log(result.message);
  } catch (error) {
    console.error('Error saving reservation hours:', error);
  } finally {
    isSaving.value = false;
  }
};

const getTimeSlotChanges = (): TimeSlotChanges => {
  const changes: TimeSlotChanges = {};

  Object.keys(selectedTimeSlots.value).forEach((date) => {
    const currentSlots = selectedTimeSlots.value[date];
    const originalSlots = originalTimeSlots.value[date] || [];

    currentSlots.forEach((isSelected, timeIndex) => {
      if (isSelected !== originalSlots[timeIndex]) {
        if (!changes[date]) {
          changes[date] = {};
        }
        changes[date][timeIndex] = isSelected;
      }
    });
  });

  console.log('Checking changes - Selected:', selectedTimeSlots.value);
  console.log('Checking changes - Original:', originalTimeSlots.value);

  return changes;
};

const hasChanges = computed(() => {
  for (const date in selectedTimeSlots.value) {
    const currentSlots = selectedTimeSlots.value[date];
    const originalSlots = originalTimeSlots.value[date] || [];

    for (let i = 0; i < currentSlots.length; i++) {
      if (currentSlots[i] !== originalSlots[i]) {
        return true;
      }
    }
  }
  return false;
});

const isSchedulePrime = (day: dayjs.Dayjs, timeIndex: number): boolean => {
  const dateString = day.format('YYYY-MM-DD');
  const slot = timeSlots.value[dateString]?.[timeIndex];
  return slot?.schedule_prime ?? false;
};

const hasOverride = (day: dayjs.Dayjs, timeIndex: number): boolean => {
  const dateString = day.format('YYYY-MM-DD');
  const slot = timeSlots.value[dateString]?.[timeIndex];
  return slot?.is_override ?? false;
};
</script>

<template>
  <div class="mx-auto">
    <div class="mb-4 flex items-center justify-between">
      <div class="inline-flex rounded-md shadow-sm" role="group">
        <button
          :disabled="!canNavigateBack"
          class="rounded-l-lg border border-gray-200 bg-white p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-900"
          @click="navigateWeek('prev')"
        >
          <ArrowLeft class="size-4" />
        </button>
        <button
          :disabled="!canNavigateForward"
          class="rounded-r-lg border border-gray-200 bg-white p-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-indigo-700 focus:z-10 focus:text-indigo-700 focus:ring-2 focus:ring-indigo-700 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-900"
          @click="navigateWeek('next')"
        >
          <ArrowRight class="size-4" />
        </button>
      </div>
      <button
        class="flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
        :disabled="isSaving || !hasChanges"
        @click="saveReservationHours"
      >
        <LoaderCircle v-if="isSaving" class="mr-2 size-4 animate-spin" />
        <Save v-else class="mr-2 size-4" />
        Save
      </button>
    </div>
    <div class="-mx-4 overflow-hidden rounded-lg shadow-lg sm:mx-auto">
      <div
        class="grid grid-cols-[80px_repeat(7,_minmax(0,_1fr))] items-center bg-white"
      >
        <div class="p-2 text-center text-xs font-medium uppercase sm:text-sm">
          {{ currentDate.format('MMM') }}
        </div>
        <div
          v-for="day in days"
          :key="day.toISOString()"
          class="p-2 text-center"
        >
          <div class="text-xs font-medium text-gray-500">
            {{ formatDate(day).day }}
          </div>
          <div class="text-sm font-semibold">
            {{ formatDate(day).date }}
          </div>
        </div>
      </div>
      <div
        class="grid grid-cols-[80px_repeat(7,_minmax(0,_1fr))] divide-x divide-y divide-white"
      >
        <template v-for="(time, timeIndex) in times" :key="time">
          <div class="bg-white py-4 text-center text-xs sm:text-sm">
            {{ formatTime(time, 'h:mm A') }}
          </div>
          <div
            v-for="day in days"
            :key="`${time}-${day.toISOString()}`"
            :class="[
              'flex items-center justify-center p-4',
              isTimeSlotAvailable(day, time)
                ? {
                    'cursor-pointer': true,
                    'bg-green-200':
                      isSchedulePrime(day, timeIndex) &&
                      !hasOverride(day, timeIndex),
                    'bg-indigo-50': hasOverride(day, timeIndex),
                    'bg-white':
                      !isSchedulePrime(day, timeIndex) &&
                      !hasOverride(day, timeIndex),
                  }
                : 'cursor-not-allowed bg-gray-50',
            ]"
          >
            <template v-if="isTimeSlotAvailable(day, time)">
              <input
                type="checkbox"
                class="size-4 rounded text-indigo-600 sm:size-5"
                :checked="hasOverride(day, timeIndex)"
                @change="toggleTimeSlot(day, timeIndex)"
              />
            </template>
            <template v-else>
              <span class="text-xs text-gray-400 sm:text-sm"> N/A </span>
            </template>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
