<template>
    <div class="col-span-6 bg-white rounded-lg shadow p-4">
        <div class="flex justify-between items-center mb-4">
            <button class="p-2" @click="previousMonth">&lt;</button>
            <h2 class="text-xl font-semibold">{{ currentMonthName }} {{ currentYear }}</h2>
            <button class="p-2" @click="resetToToday">Today</button>
            <button class="p-2" @click="nextMonth">&gt;</button>
        </div>

        <!-- Week days header -->
        <div class="grid grid-cols-7 gap-1 mb-2">
            <div v-for="day in weekDays" :key="day" class="text-center p-2 font-semibold text-gray-600">
                {{ day }}
            </div>
        </div>

        <!-- Calendar grid -->
        <div class="grid grid-cols-7 gap-1">
            <div
                v-for="day in calendarDays"
                :key="day.date"
                :class="{
                    'bg-gray-100': !day.isCurrentMonth,
                    'bg-blue-50': isSelectedDate(day.date),
                    'font-bold': isToday(day.date)
                }"
                class="min-h-[100px] p-2 border rounded-lg cursor-pointer hover:bg-gray-50 relative flex flex-col"
                @click="handleDayClick(day)"
            >
                <span class="text-sm mb-1">{{ day.dayOfMonth }}</span>
                <!-- Event list -->
                <div class="flex-1 overflow-y-auto">
                    <div
                        v-for="event in getDayEvents(day.date)"
                        :key="event.id"
                        :style="{ backgroundColor: event.color || 'blue' }"
                        class="text-xs p-1 mb-1 rounded text-white truncate"
                        :title="event.title"
                    >
                        {{ event.title }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from 'vue';

const props = defineProps({
    currentMonth: {
        type: String,
        required: true,
    },
    events: {
        type: Array,
        required: true,
        default: () => []
    }
});

const emit = defineEmits(['date-selected', 'update:events']);

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const currentDate = ref(new Date());
const selectedDate = ref(null);

// Computed properties for current month and year
const currentMonthName = computed(() => {
    return currentDate.value.toLocaleString('default', {month: 'long'});
});

const currentYear = computed(() => {
    return currentDate.value.getFullYear();
});

// Generate calendar days
const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);

    const days = [];

    // Add days from previous month
    const firstDayWeekday = firstDayOfMonth.getDay();
    for (let i = firstDayWeekday - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({
            date,
            dayOfMonth: date.getDate(),
            isCurrentMonth: false
        });
    }

    // Add days of current month
    for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
        const date = new Date(year, month, i);
        days.push({
            date,
            dayOfMonth: i,
            isCurrentMonth: true
        });
    }

    // Add days from next month
    const remainingDays = 42 - days.length; // 6 rows * 7 days = 42
    for (let i = 1; i <= remainingDays; i++) {
        const date = new Date(year, month + 1, i);
        days.push({
            date,
            dayOfMonth: date.getDate(),
            isCurrentMonth: false
        });
    }

    return days;
});

// Navigation functions
function previousMonth() {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() - 1,
        1
    );
}

function nextMonth() {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + 1,
        1
    );
}

// Helper functions
function isToday(date) {
    const today = new Date();
    return date.toDateString() === today.toDateString();
}

function isSelectedDate(date) {
    return selectedDate.value?.toDateString() === date.toDateString();
}

function getDayEvents(date) {
    return props.events.filter(event => {
        const eventDate = new Date(event.start);
        return eventDate.toDateString() === date.toDateString();
    });
}

function resetToToday() {
    //if selected month is not the current month, reset to current month
    if (currentDate.value?.toDateString() !== isToday(currentDate.value)) {
        currentDate.value = new Date();
    }
}

// Click handler
function handleDayClick(day) {
    console.log('Day clicked:', day.date);
    selectedDate.value = day.date;

    // If clicking a day from previous or next month, switch to that month
    if (!day.isCurrentMonth) {
        currentDate.value = new Date(
            day.date.getFullYear(),
            day.date.getMonth(),
            1
        );
    }

    emit('date-selected', day.date);
}
</script>

<style scoped>
/* Hide scrollbar but keep functionality */
.overflow-y-auto {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.overflow-y-auto::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}
</style>

