<template>
    <div class="col-span-6 bg-red-100">
        <VCalendar v-model="value" :events="events" @click:date="(e) => console.log(e)"/>
    </div>
</template>


<script setup>
import {VCalendar} from 'vuetify/labs/VCalendar'
import {computed} from 'vue'

const props = defineProps({
    currentMonth: {
        type: String,
        required: true,
    },
    daysInMonth: {
        type: Number,
        required: true,
    },
});

const value = new Date();

const events = computed(() => [{
    title: 'my event',
    start: new Date(),
    end: new Date(),
    color: 'red',
    allDay: true,
}])

</script>
