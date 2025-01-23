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
                            @click="deleteEvent(event)"
                        >
                            ×
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ formatTime(event.start) }} - {{ formatTime(event.end) }}
                    </p>
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
import axios from 'axios';

// Add CSRF token to all requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(props.selectedDate);
});

const dayEvents = computed(() => {
    if (!props.selectedDate) return [];
    return props.events.filter(event => {
        const eventDate = new Date(event.start);
        return eventDate.toDateString() === props.selectedDate.toDateString();
    });
});

function formatTime(date) {
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

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

function saveEvent() {
    if (!newEvent.value.title || !newEvent.value.start || !newEvent.value.end) {
        return;
    }

    const eventDate = props.selectedDate;
    const [startHours, startMinutes] = newEvent.value.start.split(':');
    const [endHours, endMinutes] = newEvent.value.end.split(':');

    const startDate = new Date(eventDate);
    startDate.setHours(parseInt(startHours), parseInt(startMinutes));
    startDate.setSeconds(0);
    startDate.setMilliseconds(0);

    const endDate = new Date(eventDate);
    endDate.setHours(parseInt(endHours), parseInt(endMinutes));
    endDate.setSeconds(0);
    endDate.setMilliseconds(0);

    // Ensure end date is after start date
    if (endDate <= startDate) {
        console.error('End time must be after start time');
        return;
    }

    const eventData = {
        title: newEvent.value.title,
        start_datetime: startDate.toISOString().slice(0, 19).replace('T', ' '),
        end_datetime: endDate.toISOString().slice(0, 19).replace('T', ' '),
    };

    // Send POST request to create event
    axios.post('/events', eventData)
        .then(response => {
            const event = {
                id: response.data.id,
                title: response.data.title,
                start: new Date(response.data.start_datetime),
                end: new Date(response.data.end_datetime),
                color: '#3B82F6'
            };
            const updatedEvents = [...props.events, event];
            emit('update:events', updatedEvents);
            isEditing.value = false;
        })
        .catch(error => {
            console.error('Error creating event:', error);
            console.error('Validation errors:', error.response?.data?.errors);
            console.error('Event data sent:', eventData);
            // TODO: Add error handling UI
        });
}

function deleteEvent(event) {
    // Send DELETE request to remove event
    axios.delete(`/events/${event.id}`)
        .then(() => {
            const updatedEvents = props.events.filter(e => e.id !== event.id);
            emit('update:events', updatedEvents);
        })
        .catch(error => {
            console.error('Error deleting event:', error);
            // TODO: Add error handling UI
        });
}

onMounted(() => {
    // Fetch events when component mounts
    axios.get('/events')
        .then(response => {
            const events = response.data.map(event => ({
                id: event.id,
                title: event.title,
                start: new Date(event.start_datetime),
                end: new Date(event.end_datetime),
                color: '#3B82F6'
            }));
            emit('update:events', events);
        })
        .catch(error => {
            console.error('Error fetching events:', error);
            // TODO: Add error handling UI
        });
});
</script>
