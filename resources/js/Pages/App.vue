<script setup>
import Calendar from "../Components/Calendar.vue";
import DailyDetailedView from "../Components/DailyDetailedView.vue";
import {ref} from 'vue';

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

const selectedDate = ref(null);
const events = ref([]);

function handleDateSelected(date) {
    selectedDate.value = date;
}

function updateEvents(newEvents) {
    events.value = newEvents;
}
</script>

<template>
    <div class="flex flex-col">
        <div>
            <p class="text-center mb-10 text-3xl">TaskFlow</p>
        </div>
        <div class="grid grid-cols-12 gap-4">
            <div class="text-center col-span-3 bg-gray-100">
                <p class="text-2xl">Daily News Digest</p>
                <div class="bg-gray-200 h-96">Hi</div>
            </div>

            <Calendar
                :currentMonth="currentMonth"
                :events="events"
                @date-selected="handleDateSelected"
            />

            <DailyDetailedView
                :events="events"
                :selected-date="selectedDate"
                @update:events="updateEvents"
            />
        </div>
        <div class="container mx-auto text-center h-40 bg-orange-100">
            <div class="my-4">
                <textarea class="w-2/3" placeholder="AI Reply..."></textarea>
            </div>

            <div class="my-4">
                <input class="align-bottom" placeholder="Here is your AI" type="text"/>
                <button class="align-bottom" type="submit">Enter</button>
            </div>
        </div>
    </div>
</template>

