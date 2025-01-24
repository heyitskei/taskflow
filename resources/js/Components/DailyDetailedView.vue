<template>
    <div class="bg-white rounded-xl shadow-lg p-6 transition-all duration-300 hover:shadow-xl h-full flex flex-col">
        <h2 class="text-xl font-semibold mb-4 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            Daily Detailed View
        </h2>

        <div v-if="selectedDate" class="flex-1 space-y-4 overflow-auto">
            <div class="bg-gray-50 rounded-lg p-4 sticky top-0">
                <h3 class="text-lg font-medium text-gray-800">{{ formattedDate }}</h3>
            </div>

            <div class="space-y-3">
                <div
                    v-for="event in dayEvents"
                    :key="event.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 transition-all duration-200 hover:shadow-md"
                >
                    <div class="flex justify-between items-start">
                        <span class="font-medium text-gray-800">{{ event.title }}</span>
                        <button
                            class="text-gray-400 hover:text-red-500 transition-colors duration-200 p-1 hover:bg-red-50 rounded-full"
                            @click="handleDeleteEvent(event)"
                        >
                            ×
                        </button>
                    </div>
                    <div class="flex-1 text-sm text-gray-600">
                        {{ formatTime(event.start_datetime) }} - {{ formatTime(event.end_datetime) }}
                    </div>
                </div>

                <div v-if="isEditing" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
                    <input
                        v-model="newEvent.title"
                        class="w-full mb-3 px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               transition-all duration-200 bg-gray-50"
                        placeholder="Event title"
                    >
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                            <input
                                v-model="newEvent.start"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       transition-all duration-200 bg-gray-50"
                                type="time"
                            >
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Time</label>
                            <input
                                v-model="newEvent.end"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       transition-all duration-200 bg-gray-50"
                                type="time"
                            >
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>
                        <button
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg
                                   hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02]"
                            @click="saveEvent"
                        >
                            Save
                        </button>
                    </div>
                </div>

                <button
                    v-else
                    class="w-full py-2 px-4 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100
                           transition-colors duration-200 font-medium flex items-center justify-center"
                    @click="startNewEvent"
                >
                    <span class="mr-2">+</span> Add Event
                </button>
            </div>
        </div>

        <div
            v-else
            class="flex-1 bg-gray-50 rounded-lg p-8 flex flex-col items-center justify-center"
        >
            <div class="text-gray-400 mb-3">
                <!-- You can add an icon here -->
                📅
            </div>
            <p class="text-gray-600 text-center">
                Select a date to view and manage events
            </p>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import {createEvent, deleteEvent, fetchEvents} from '../services/eventService';
import {formatFullDate, formatTime, toLocalDate, toUTCString} from '../utils/dateTime';

const props = defineProps({
    selectedDate: {
        type: Date,
        required: false,
        default: null
    },
    events: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['update:events']);

const isEditing = ref(false);
const newEvent = ref({
    title: '',
    start: '',
    end: ''
});

const formattedDate = computed(() => {
    if (!props.selectedDate) return '';
    return formatFullDate(props.selectedDate);
});

const dayEvents = computed(() => {
    if (!props.selectedDate) return [];
    return props.events.filter(event => {
        const eventDate = toLocalDate(event.start_datetime);
        return eventDate.toDateString() === props.selectedDate.toDateString();
    });
});

function startNewEvent() {
    isEditing.value = true;
    newEvent.value = {
        title: '',
        start: '',
        end: ''
    };
}

function cancelEdit() {
    isEditing.value = false;
}

async function saveEvent() {
    if (!newEvent.value.title || !newEvent.value.start || !newEvent.value.end) {
        return;
    }

    const eventDate = props.selectedDate;
    const [startHours, startMinutes] = newEvent.value.start.split(':');
    const [endHours, endMinutes] = newEvent.value.end.split(':');

    const startDate = new Date(eventDate);
    startDate.setHours(parseInt(startHours), parseInt(startMinutes), 0, 0);

    const endDate = new Date(eventDate);
    endDate.setHours(parseInt(endHours), parseInt(endMinutes), 0, 0);

    if (endDate <= startDate) {
        alert('End time must be after start time');
        return;
    }

    const eventData = {
        title: newEvent.value.title,
        start_datetime: toUTCString(startDate),
        end_datetime: toUTCString(endDate)
    };

    try {
        const event = await createEvent(eventData);
        const updatedEvents = [...props.events, event];
        emit('update:events', updatedEvents);
        isEditing.value = false;
        newEvent.value = {title: '', start: '', end: ''};
    } catch (error) {
        alert('Failed to create event. Please try again.');
    }
}

async function handleDeleteEvent(event) {
    try {
        await deleteEvent(event.id);
        const updatedEvents = props.events.filter(e => e.id !== event.id);
        emit('update:events', updatedEvents);
    } catch (error) {
        alert('Failed to delete event');
    }
}

onMounted(async () => {
    try {
        const events = await fetchEvents();
        emit('update:events', events);
    } catch (error) {
        alert('Failed to load events. Please refresh the page.');
    }
});
</script>
