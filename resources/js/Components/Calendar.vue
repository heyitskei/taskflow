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

            <div class="flex flex-col items-center">
                <h2 class="text-xl font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    {{ currentMonthName }}
                </h2>
                <span class="text-sm text-gray-500 mt-0.5">{{ currentYear }}</span>
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
                            {{ day.dayOfMonth }}
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
import {computed, ref} from 'vue';
import axios from 'axios';

const props = defineProps({
    selected_date: {
        type: Date,
        required: false,
        default: null
    },
    events: {
        type: Array,
        required: true,
        default: () => []
    }
});

const emit = defineEmits(['update:events', 'update:selected-date']);

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const currentDate = ref(new Date());
const selectedDate = computed({
    get: () => props.selected_date,
    set: (value) => emit('update:selected-date', value)
});

// Drag and drop state
const isDragging = ref(false);
const draggedEvent = ref(null);

function handleDragStart(event, calendarEvent) {
    isDragging.value = true;
    draggedEvent.value = calendarEvent;
    event.dataTransfer.setData('text/plain', JSON.stringify(calendarEvent));
    event.dataTransfer.effectAllowed = 'move';

    // Add dragging class to the element
    event.target.classList.add('dragging');
}

function handleDragEnd(event) {
    isDragging.value = false;
    draggedEvent.value = null;
    event.target.classList.remove('dragging');
}

function handleDragOver(event, date) {
    event.preventDefault();
    if (isDragging.value) {
        event.currentTarget.classList.add('drag-over');
    }
}

function handleDragLeave(event) {
    event.currentTarget.classList.remove('drag-over');
}

async function handleDrop(event, targetDate) {
    event.currentTarget.classList.remove('drag-over');
    const draggedEventData = JSON.parse(event.dataTransfer.getData('text/plain'));

    try {
        const eventDate = new Date(draggedEventData.start);
        const timeDiff = eventDate.getTime() - new Date(eventDate.toDateString()).getTime();

        // Create new dates preserving the time
        const newStartDate = new Date(targetDate);
        newStartDate.setTime(newStartDate.getTime() + timeDiff);

        const newEndDate = new Date(newStartDate);
        const duration = new Date(draggedEventData.end).getTime() - new Date(draggedEventData.start).getTime();
        newEndDate.setTime(newEndDate.getTime() + duration);

        // Update event in the database
        const eventData = {
            title: draggedEventData.title,
            start_datetime: newStartDate.toISOString().slice(0, 19).replace('T', ' '),
            end_datetime: newEndDate.toISOString().slice(0, 19).replace('T', ' '),
        };

        const response = await axios.put(`/events/${draggedEventData.id}`, eventData);

        // Update local events with the response data
        const updatedEvent = response.data.event;
        const updatedEvents = props.events.map(event => {
            if (event.id === draggedEventData.id) {
                return {
                    ...event,
                    start: new Date(updatedEvent.start_datetime),
                    end: new Date(updatedEvent.end_datetime)
                };
            }
            return event;
        });

        emit('update:events', updatedEvents);
    } catch (error) {
        console.error('Error updating event:', error);
        // TODO: Implement proper error handling UI
        alert('Failed to update event. Please try again.');
    }
}

// Computed properties
const currentMonthName = computed(() => {
    return currentDate.value.toLocaleString('default', {month: 'long'});
});

const currentYear = computed(() => {
    return currentDate.value.getFullYear();
});

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];

    // Previous month days
    const firstDayWeekday = firstDay.getDay();
    for (let i = firstDayWeekday - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({
            date,
            dayOfMonth: date.getDate(),
            isCurrentMonth: false
        });
    }

    // Current month days
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        days.push({
            date,
            dayOfMonth: i,
            isCurrentMonth: true
        });
    }

    // Next month days to complete the row
    const lastDayWeekday = lastDay.getDay();
    const remainingDays = 6 - lastDayWeekday;
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

function resetToToday() {
    currentDate.value = new Date();
    selectedDate.value = currentDate.value;
}

function handleDayClick(day) {
    selectedDate.value = day.date;

    if (!day.isCurrentMonth) {
        currentDate.value = new Date(
            day.date.getFullYear(),
            day.date.getMonth(),
            1
        );
    }
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

/* Drag and drop styles */
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

