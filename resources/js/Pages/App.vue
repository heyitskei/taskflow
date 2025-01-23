<script setup>
import Calendar from "../Components/Calendar.vue";
import DailyDetailedView from "../Components/DailyDetailedView.vue";
import DailyNewsDigest from "../Components/DailyNewsDigest.vue";
import AiChat from "../Components/AiChat.vue";
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
    console.log('Date selected in App:', date);
    selectedDate.value = date;
}

function updateEvents(newEvents) {
    events.value = newEvents;
}
</script>

<template>
    <div class="h-screen bg-gray-50 p-6">
        <div class="h-full max-w-[1920px] mx-auto flex flex-col">
            <!-- Header -->
            <div class="mb-4 text-center">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    TaskFlow
                </h1>
                <p class="text-sm text-gray-600">Your AI-Powered Productivity Assistant</p>
            </div>

            <!-- Main Content -->
            <div class="flex-1 grid grid-cols-12 gap-6 min-h-0">
                <!-- Left Column -->
                <div class="col-span-3 flex flex-col gap-6 min-h-0">
                    <!-- News Digest -->
                    <div class="flex-1 min-h-0">
                        <DailyNewsDigest/>
                    </div>

                    <!-- AI Chat -->
                    <div class="flex-1 min-h-0">
                        <AiChat/>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="col-span-6 min-h-0">
                    <Calendar
                        :currentMonth="currentMonth"
                        :events="events"
                        class="h-full"
                        @date-selected="handleDateSelected"
                    />
                </div>

                <!-- Daily Detailed View -->
                <div class="col-span-3 min-h-0">
                    <DailyDetailedView
                        :events="events"
                        :selected-date="selectedDate"
                        class="h-full"
                        @update:events="updateEvents"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

