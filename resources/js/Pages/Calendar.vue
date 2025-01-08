<template>
    <div class="col-span-6 bg-red-100">
        <VCalendar
            v-model="value"
            :events="events"
            @click:date="handleDateClick"
        />
        <button @click="addEvent">Add Event</button>
    </div>
</template>

<script setup>
import {VCalendar} from 'vuetify/labs/VCalendar'
import {reactive, ref} from 'vue'

const value = ref(new Date());

const events = reactive([]);

function handleDateClick(event) {
    console.log('Clicked date:', event);
}

function addEvent() {
    const newEvent = {
        title: `New Event ${events.length + 1}`,
        start: new Date(),
        end: new Date(),
        color: 'blue',
        allDay: true,
    };
    events.push(newEvent);
}
</script>

