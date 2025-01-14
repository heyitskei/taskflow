<template>
    <div class="col-span-6 bg-red-100">
        <VCalendar
            v-model="value"
            :event-color="getEventColor"
            :events="props.events"
            class="calendar-container"
            @click="handleClick"
        />
    </div>
</template>

<script setup>
import {VCalendar} from 'vuetify/labs/VCalendar'
import {ref} from 'vue'

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
const value = ref(new Date());

function handleClick(event) {
    console.log('Click event properties:', Object.keys(event));
    console.log('Full event object:', event);
}

function getEventColor(event) {
    return event.color;
}
</script>

<style scoped>
.calendar-container :deep(.v-calendar) {
    cursor: pointer;
}

.calendar-container :deep(.v-calendar-weekly__day) {
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-container :deep(.v-calendar-weekly__day:hover) {
    background-color: rgba(0, 0, 0, 0.05);
}
</style>

