<template>
    <div class="text-center col-span-3 bg-green-100">
        <p class="text-2xl">Daily Detailed View</p>
        <div v-if="selectedDate" class="p-4">
            <div class="mb-4">
                <h3 class="text-lg font-semibold">{{ formattedDate }}</h3>
            </div>
            <div class="space-y-4">
                <div v-for="event in dayEvents" :key="event.id" class="bg-white p-3 rounded shadow">
                    <div class="flex justify-between items-center">
                        <span>{{ event.title }}</span>
                        <button class="text-red-500" @click="deleteEvent(event)">&times;</button>
                    </div>
                    <p class="text-sm text-gray-600">{{ event.start }} - {{ event.end }}</p>
                </div>

                <div v-if="isEditing" class="bg-white p-4 rounded shadow">
                    <input v-model="newEvent.title" class="w-full mb-2 p-2 border rounded" placeholder="Event title">
                    <div class="grid grid-cols-2 gap-2">
                        <input v-model="newEvent.start" class="p-2 border rounded" type="time">
                        <input v-model="newEvent.end" class="p-2 border rounded" type="time">
                    </div>
                    <div class="mt-2 flex justify-end space-x-2">
                        <button class="px-3 py-1 bg-gray-200 rounded" @click="cancelEdit">Cancel</button>
                        <button class="px-3 py-1 bg-blue-500 text-white rounded" @click="saveEvent">Save</button>
                    </div>
                </div>

                <button v-else class="w-full py-2 bg-blue-500 text-white rounded" @click="startNewEvent">
                    Add Event
                </button>
            </div>
        </div>
        <div v-else class="bg-gray-200 h-96 flex items-center justify-center">
            Select a date to view details
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from 'vue';

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

    const endDate = new Date(eventDate);
    endDate.setHours(parseInt(endHours), parseInt(endMinutes));

    const event = {
        id: Date.now(),
        title: newEvent.value.title,
        start: startDate,
        end: endDate,
        color: 'blue'
    };

    const updatedEvents = [...props.events, event];
    emit('update:events', updatedEvents);

    isEditing.value = false;
}

function deleteEvent(event) {
    const updatedEvents = props.events.filter(e => e.id !== event.id);
    emit('update:events', updatedEvents);
}
</script>
