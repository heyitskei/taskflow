<script setup>
import Calendar from "../Components/Calendar.vue";
import DailyDetailedView from "../Components/DailyDetailedView.vue";
import DailyNewsDigest from "../Components/DailyNewsDigest.vue";
import AiChat from "../Components/AiChat.vue";
import {ref} from 'vue';
import {useDarkMode} from '../stores/darkMode';

const selectedDate = ref(new Date());
const events = ref([]);
const {isDark, toggleDarkMode} = useDarkMode();

function handleDateSelected(date) {
    console.log('Date selected in App:', date);
    selectedDate.value = date;
}

function updateEvents(newEvents) {
    events.value = newEvents;
}
</script>

<template>
  <div class="h-screen bg-gray-50 dark:bg-gray-900 p-6 transition-colors duration-200">
        <div class="h-full max-w-[1920px] mx-auto flex flex-col">
            <!-- Header -->
          <div class="mb-4 flex items-center justify-between">
            <div class="text-center flex-1">
              <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                TaskFlow
              </h1>
              <p class="text-sm text-gray-600 dark:text-gray-400">Your AI-Powered Productivity Assistant</p>
            </div>
            <!-- Dark Mode Toggle -->
            <button
                aria-label="Toggle Dark Mode"
                class="p-2.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-all duration-200
                           text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                @click="toggleDarkMode"
            >
              <div class="relative w-6 h-6">
                <!-- Sun icon -->
                <svg
                    :class="isDark ? 'rotate-90 opacity-0' : 'rotate-0 opacity-100'"
                    class="absolute inset-0 w-6 h-6 transition-transform duration-200"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                  />
                </svg>
                <!-- Moon icon -->
                <svg
                    :class="isDark ? 'rotate-0 opacity-100' : '-rotate-90 opacity-0'"
                    class="absolute inset-0 w-6 h-6 transition-transform duration-200"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                  />
                </svg>
              </div>
            </button>
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

    <!-- AI Chat Component -->
        <AiChat/>
    </div>
</template>

