<template>
    <div class="bg-white rounded-xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl h-full flex flex-col">
        <!-- Calendar Header -->
        <div class="flex items-center justify-between mb-6">
            <button
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors duration-200"
                @click="previousMonth"
            >
                <span class="text-gray-600">←</span>
            </button>

            <div class="flex flex-col items-center relative">
                <div class="relative inline-block">
                    <button
                        ref="monthPickerButton"
                        class="text-xl font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent
                               hover:opacity-80 transition-opacity duration-200 px-2 py-1 rounded"
                        @click="toggleMonthPicker"
                    >
                        {{ monthName }}
                    </button>
                    <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div
                            v-if="isMonthPickerOpen"
                            ref="monthPickerDropdown"
                            class="absolute left-1/2 -translate-x-1/2 top-full mt-1 bg-white dark:bg-gray-800 shadow-lg rounded-lg py-2 pt-1 z-20
                                   grid grid-cols-3 gap-2 w-64 border border-gray-200 dark:border-gray-700"
                        >
                            <button
                                v-for="(month, index) in monthNames"
                                :key="month"
                                :class="[
                                    'px-2 py-1.5 text-sm rounded transition-colors duration-200',
                                    currentMonth === index
                                        ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400'
                                        : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                                @click="selectMonth(index)"
                            >
                                {{ month }}
                            </button>
                        </div>
                    </Transition>
                </div>

                <div class="relative inline-block">
                    <button
                        ref="yearPickerButton"
                        class="text-sm text-gray-500 mt-0.5 hover:text-gray-700 dark:hover:text-gray-300
                               transition-colors duration-200 px-2 py-0.5 rounded"
                        @click="toggleYearPicker"
                    >
                        {{ currentYear }}
                    </button>
                    <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div
                            v-if="isYearPickerOpen"
                            ref="yearPickerDropdown"
                            class="absolute left-1/2 -translate-x-1/2 top-full mt-1 bg-white dark:bg-gray-800 shadow-lg rounded-lg p-2 z-20
                                   grid grid-cols-3 gap-1 w-48 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700"
                        >
                            <button
                                v-for="year in availableYears"
                                :key="year"
                                :class="[
                                    'px-2 py-1 text-sm rounded transition-colors duration-200',
                                    currentYear === year
                                        ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400'
                                        : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                                @click="selectYear(year)"
                            >
                                {{ year }}
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    class="px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-full hover:bg-blue-100 transition-colors duration-200"
                    @click="resetToToday"
                >
                    Today
                </button>
                <button
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors duration-200"
                    @click="nextMonth"
                >
                    <span class="text-gray-600">→</span>
                </button>
            </div>
        </div>

        <!-- Calendar Body -->
        <div class="flex-1 flex flex-col min-h-0">
            <!-- Weekday Headers -->
            <div class="grid grid-cols-7 mb-2">
                <div
                    v-for="day in weekDays"
                    :key="day"
                    class="text-center py-2 text-sm font-medium text-gray-500"
                >
                    {{ day }}
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="flex-1 grid grid-cols-7 gap-[1px] bg-gray-100 rounded-lg p-[1px]">
                <div
                    v-for="day in calendarDays"
                    :key="day.date"
                    :class="[
                        'bg-white p-1.5 flex flex-col min-h-[90px] transition-all duration-200',
                        day.isCurrentMonth ? 'hover:bg-gray-50' : 'opacity-50 hover:opacity-75',
                        isSelectedDate(day.date) && 'ring-2 ring-blue-400 bg-blue-50',
                        isToday(day.date) && !isSelectedDate(day.date) && 'ring-1 ring-blue-200'
                    ]"
                    @click="handleDayClick(day)"
                    @dragleave="handleDragLeave"
                    @dragover="handleDragOver($event, day.date)"
                    @drop="handleDrop($event, day.date)"
                >
                    <!-- Day Header -->
                    <div class="flex items-center justify-between mb-1">
                        <span
                            :class="[
                                'text-sm font-medium rounded-full w-7 h-7 flex items-center justify-center',
                                isToday(day.date) ? 'bg-blue-600 text-white' : 'text-gray-700'
                            ]"
                        >
                            {{ day.date.getDate() }}
                        </span>
                        <div
                            v-if="getDayEvents(day.date).length > 0"
                            class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"
                        >
                            {{ getDayEvents(day.date).length }}
                        </div>
                    </div>

                    <!-- Events -->
                    <div class="flex-1 space-y-1 overflow-hidden">
                        <template v-for="(event, index) in getDayEvents(day.date)" :key="event.id">
                            <div
                                v-if="index < 2"
                                :style="{ backgroundColor: event.color || '#3B82F6' }"
                                :title="event.title"
                                class="text-xs px-2 py-1 rounded text-white truncate cursor-move transition-all duration-200"
                                draggable="true"
                                @dragstart="handleDragStart($event, event)"
                                @dragend="handleDragEnd"
                            >
                                {{ event.title }}
                            </div>
                        </template>
                        <div
                            v-if="getDayEvents(day.date).length > 2"
                            class="text-xs text-gray-500 px-2"
                        >
                            +{{ getDayEvents(day.date).length - 2 }} more
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {updateEvent} from '../services/eventService';
import {toLocalDate, toUTCString} from '../utils/dateTime';

const props = defineProps({
    events: {
        type: Array,
        required: true,
        default: () => []
    },
    selectedDate: {
        type: Date,
        required: true
    }
});

const emit = defineEmits(['update:selectedDate', 'update:events']);

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const currentMonth = ref(props.selectedDate.getMonth());
const currentYear = ref(props.selectedDate.getFullYear());
const isMonthPickerOpen = ref(false);
const isYearPickerOpen = ref(false);

const monthPickerButton = ref(null);
const monthPickerDropdown = ref(null);
const yearPickerButton = ref(null);
const yearPickerDropdown = ref(null);

watch(() => props.selectedDate, (newDate) => {
    currentMonth.value = newDate.getMonth();
    currentYear.value = newDate.getFullYear();
}, {deep: true});

const availableYears = computed(() => {
    const currentYearNum = currentYear.value;
    const years = [];
    for (let i = currentYearNum - 5; i <= currentYearNum + 5; i++) {
        years.push(i);
    }
    return years;
});

const monthName = computed(() => {
    return monthNames[currentMonth.value];
});

const calendarDays = computed(() => {
    const days = [];
    const firstDay = new Date(currentYear.value, currentMonth.value, 1);
    const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0);
    let start = firstDay.getDay();

    for (let i = start - 1; i >= 0; i--) {
        const date = new Date(currentYear.value, currentMonth.value - 1, lastDay.getDate() - i);
        days.push({date, isCurrentMonth: false});
    }

    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(currentYear.value, currentMonth.value, i);
        days.push({date, isCurrentMonth: true});
    }

    const totalDays = Math.ceil((start + lastDay.getDate()) / 7) * 7;
    const remainingDays = totalDays - days.length;

    for (let i = 1; i <= remainingDays; i++) {
        const date = new Date(currentYear.value, currentMonth.value + 1, i);
        days.push({date, isCurrentMonth: false});
    }

    return days;
});

function getDayEvents(date) {
    return props.events.filter(event => {
        const eventDate = toLocalDate(event.start_datetime);
        return eventDate.toDateString() === date.toDateString();
    });
}

function selectMonth(monthIndex) {
    currentMonth.value = monthIndex;
    isMonthPickerOpen.value = false;
    updateSelectedDate();
}

function selectYear(year) {
    currentYear.value = year;
    isYearPickerOpen.value = false;
    updateSelectedDate();
}

function updateSelectedDate() {
    const newDate = new Date(
        currentYear.value,
        currentMonth.value,
        props.selectedDate.getDate()
    );

    if (newDate.getMonth() !== currentMonth.value) {
        newDate.setDate(0);
    }

    emit('update:selectedDate', newDate);
}

function toggleMonthPicker(event) {
    event.stopPropagation();
    isYearPickerOpen.value = false;
    isMonthPickerOpen.value = !isMonthPickerOpen.value;
}

function toggleYearPicker(event) {
    event.stopPropagation();
    isMonthPickerOpen.value = false;
    isYearPickerOpen.value = !isYearPickerOpen.value;
}

onMounted(() => {
    const handleClickOutside = (event) => {
        if (isMonthPickerOpen.value &&
            !monthPickerButton.value?.contains(event.target) &&
            !monthPickerDropdown.value?.contains(event.target)) {
            isMonthPickerOpen.value = false;
        }

        if (isYearPickerOpen.value &&
            !yearPickerButton.value?.contains(event.target) &&
            !yearPickerDropdown.value?.contains(event.target)) {
            isYearPickerOpen.value = false;
        }
    };

    document.addEventListener('click', handleClickOutside);

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
    });
});

function previousMonth() {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
    updateSelectedDate();
}

function nextMonth() {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
    updateSelectedDate();
}

function resetToToday() {
    const today = new Date();
    currentMonth.value = today.getMonth();
    currentYear.value = today.getFullYear();
    emit('update:selectedDate', today);
}

function handleDayClick(day) {
    emit('update:selectedDate', day.date);
    if (!day.isCurrentMonth) {
        currentMonth.value = day.date.getMonth();
        currentYear.value = day.date.getFullYear();
    }
}

function handleDragStart(event, calendarEvent) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify(calendarEvent));
}

function handleDragEnd() {
}

function handleDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
}

function handleDragLeave(event) {
    event.currentTarget.classList.remove('drag-over');
}

async function handleDrop(event, targetDate) {
    event.preventDefault();
    const draggedEvent = JSON.parse(event.dataTransfer.getData('text/plain'));
    const originalStartDate = toLocalDate(draggedEvent.start_datetime);
    const originalEndDate = toLocalDate(draggedEvent.end_datetime);

    const newStartDate = new Date(Date.UTC(
        targetDate.getUTCFullYear(),
        targetDate.getUTCMonth(),
        targetDate.getUTCDate(),
        originalStartDate.getUTCHours(),
        originalStartDate.getUTCMinutes(),
        originalStartDate.getUTCSeconds()
    ));

    const duration = originalEndDate.getTime() - originalStartDate.getTime();
    const newEndDate = new Date(newStartDate.getTime() + duration);

    const eventData = {
        title: draggedEvent.title,
        start_datetime: toUTCString(newStartDate),
        end_datetime: toUTCString(newEndDate)
    };

    try {
        const response = await updateEvent(draggedEvent.id, eventData);
        const updatedEvents = props.events.map(e =>
            e.id === draggedEvent.id ? {...response, color: '#3B82F6'} : e
        );
        emit('update:events', updatedEvents);
    } catch {
        alert('Failed to update event. Please try again.');
    }
}

function isToday(date) {
    const today = new Date();
    return date.toDateString() === today.toDateString();
}

function isSelectedDate(date) {
    return props.selectedDate.toDateString() === date.toDateString();
}
</script>

<style scoped>
.overflow-y-auto {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.overflow-y-auto::-webkit-scrollbar {
    display: none;
}

.calendar-enter-active,
.calendar-leave-active {
    transition: all 0.3s ease;
}

.calendar-enter-from,
.calendar-leave-to {
    opacity: 0;
    transform: translateY(30px);
}

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

.dragging {
    opacity: 0.5;
    transform: scale(0.95);
}

.drag-over {
    @apply ring-2 ring-blue-400 bg-blue-50;
}

[draggable="true"] {
    cursor: move;
    user-select: none;
}

[draggable="true"]:hover {
    opacity: 0.8;
    transform: scale(1.02);
}
</style>

