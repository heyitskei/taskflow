<template>
    <div class="bg-white rounded-xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl h-full flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <button
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 flex items-center justify-center w-8 h-8"
                @click="previousMonth"
            >
                <span class="text-gray-600">&lt;</span>
            </button>
            <div class="flex flex-col items-center">
                <h2 class="text-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    {{ currentMonthName }}
                </h2>
                <span class="text-xs text-gray-500">{{ currentYear }}</span>
            </div>
            <button
                class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors duration-200 text-xs font-medium"
                @click="resetToToday"
            >
                Today
            </button>
            <button
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 flex items-center justify-center w-8 h-8"
                @click="nextMonth"
            >
                <span class="text-gray-600">&gt;</span>
            </button>
        </div>

        <!-- Week days header -->
        <div class="grid grid-cols-7 gap-1 mb-1">
            <div
                v-for="day in weekDays"
                :key="day"
                class="text-center p-1 text-xs font-medium text-gray-600"
            >
                {{ day }}
            </div>
        </div>

        <!-- Calendar grid -->
        <div class="flex-1 grid grid-cols-7 gap-1 min-h-0 overflow-hidden">
            <div
                v-for="day in calendarDays"
                :key="day.date"
                :class="{
                    'opacity-0': !day.isCurrentMonth && !shouldShowAdjacentDays(day),
                    'opacity-30': !day.isCurrentMonth && shouldShowAdjacentDays(day),
                    'bg-blue-50 ring-2 ring-blue-400': isSelectedDate(day.date),
                    'font-medium': isToday(day.date),
                    'ring-1 ring-blue-300 bg-blue-50/50': isToday(day.date) && !isSelectedDate(day.date)
                }"
                class="aspect-square p-1 rounded-lg cursor-pointer transition-all duration-200
                       hover:shadow-sm hover:bg-gray-50 relative flex flex-col
                       bg-white border border-gray-100 text-sm"
                @click="handleDayClick(day)"
            >
                <div class="flex justify-between items-start">
                    <span
                        :class="{
                            'bg-blue-600 text-white px-1.5 py-0.5 rounded-full text-xs': isToday(day.date),
                            'text-gray-700': !isToday(day.date)
                        }"
                    >
                        {{ day.dayOfMonth }}
                    </span>
                    <div v-if="getDayEvents(day.date).length > 0"
                         class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded-full">
                        {{ getDayEvents(day.date).length }}
                    </div>
                </div>

                <!-- Event list -->
                <div class="flex-1 overflow-hidden mt-1">
                    <div
                        v-for="(event, index) in getDayEvents(day.date).slice(0, 2)"
                        :key="event.id"
                        :style="{
                            backgroundColor: event.color || '#3B82F6',
                            opacity: index === 1 && getDayEvents(day.date).length > 2 ? 0.5 : 1
                        }"
                        class="text-[10px] px-1 py-0.5 rounded text-white truncate mb-0.5"
                        :title="event.title"
                    >
                        {{ event.title }}
                    </div>
                    <div
                        v-if="getDayEvents(day.date).length > 2"
                        class="text-[10px] text-gray-500"
                    >
                        +{{ getDayEvents(day.date).length - 2 }} more
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

    // Calculate how many days we need from next month to complete 5 rows (35 days)
    const totalDaysNeeded = 35;
    const remainingDays = totalDaysNeeded - days.length;

    // Only add days from next month if we need them to complete the current row
    if (remainingDays > 0) {
        for (let i = 1; i <= remainingDays; i++) {
            const date = new Date(year, month + 1, i);
            days.push({
                date,
                dayOfMonth: date.getDate(),
                isCurrentMonth: false
            });
        }
    }

    return days;
});

function shouldShowAdjacentDays(day) {
    if (day.isCurrentMonth) return true;

    const dayIndex = calendarDays.value.indexOf(day);

    // For previous month days (first row)
    if (dayIndex < 7) {
        return true; // Always show days in the first week
    }

    // For next month days
    const currentMonthDays = calendarDays.value.filter(d => d.isCurrentMonth);
    const lastCurrentMonthDay = currentMonthDays[currentMonthDays.length - 1];

    if (!lastCurrentMonthDay) return false;

    const lastDayOfWeek = new Date(lastCurrentMonthDay.date).getDay();
    const daysNeededToComplete = 6 - lastDayOfWeek; // 6 because Sunday is 0

    // Show only the days needed to complete the last week
    return dayIndex < calendarDays.value.length - daysNeededToComplete;
}

// Add this computed property to help with calculations
const firstDayOfWeek = computed(() => {
    const firstDay = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1);
    return firstDay.getDay();
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
    currentDate.value = new Date();
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
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.overflow-y-auto::-webkit-scrollbar {
    display: none;
}

/* Smooth transitions */
.calendar-enter-active,
.calendar-leave-active {
    transition: all 0.3s ease;
}

.calendar-enter-from,
.calendar-leave-to {
    opacity: 0;
    transform: translateY(30px);
}

/* Hover effects */
@keyframes subtle-bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-2px);
    }
}

.hover\:animate-bounce:hover {
    animation: subtle-bounce 0.3s ease;
}
</style>

