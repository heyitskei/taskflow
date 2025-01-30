<script setup>
import Calendar from "../Components/Calendar.vue";
import DailyDetailedView from "../Components/DailyDetailedView.vue";
import DailyNewsDigest from "../Components/DailyNewsDigest.vue";
import AiChat from "../Components/AiChat.vue";
import {ref} from 'vue';

const selectedDate = ref(new Date());
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
                <div class="col-span-3 min-h-0">
                    <DailyNewsDigest class="h-full overflow-auto"/>
                </div>

                <!-- Center Column -->
                <div class="col-span-6 min-h-0">
                    <Calendar
                        v-model:selected-date="selectedDate"
                        v-model:events="events"
                        class="h-full"
                    />
                </div>

                <!-- Right Column -->
                <div class="col-span-3 flex flex-col gap-6 min-h-0">
                    <div class="flex-[2] min-h-0 overflow-auto">
                        <DailyDetailedView
                            v-model:events="events"
                            :selected-date="selectedDate"
                            class="h-full"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Chat Component-->
        <AiChat/>
    </div>
</template>

