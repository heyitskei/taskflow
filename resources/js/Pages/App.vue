<script setup>
import Calendar from "../Components/Calendar.vue";
import DailyDetailedView from "../Components/DailyDetailedView.vue";
import DailyNewsDigest from "../Components/DailyNewsDigest.vue";
import AiChat from "../Components/AiChat.vue";
import {nextTick, ref, watch} from 'vue';
import {useDarkMode} from '../stores/darkMode';
import axios from "axios";

const selectedDate = ref(new Date());
const events = ref([]);
const {isDark, toggleDarkMode} = useDarkMode();
const activeTab = ref('news'); // 'news' or 'ai'
const isAiChatFullscreen = ref(false);
const chatMessages = ref([]);

function handleDateSelected(date) {
    console.log('Date selected in App:', date);
    selectedDate.value = date;
}

function updateEvents(newEvents) {
    events.value = newEvents;
}

function toggleAiChatFullscreen() {
    isAiChatFullscreen.value = !isAiChatFullscreen.value;
}

function updateChatMessages(messages) {
    chatMessages.value = messages;
}

function handleEventCreated(newEvent) {
    events.value = [...events.value, {...newEvent, color: '#3B82F6'}];
}

// Scroll to bottom when new messages are added
watch(() => chatMessages.value.length, () => {
    nextTick(() => {
        const container = document.querySelector('.messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
});

function formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

async function sendMessage() {
    if (!chatMessages.value.trim()) return;

    const updatedMessages = [...chatMessages.value, {
        id: Date.now(),
        content: chatMessages.value,
        timestamp: new Date(),
        isUser: true
    }];

    updateChatMessages(updatedMessages);
    const userMessage = chatMessages.value;
    chatMessages.value = '';

    try {
        const result = await axios.post('/api/openai', {
            prompt: userMessage
        });

        if (result.data.error) {
            throw new Error(result.data.error);
        }

        // If events were created, update the events list
        if (result.data.events && result.data.events.length > 0) {
            const newEvents = result.data.events.map(event => ({...event, color: '#3B82F6'}));
            events.value = [...events.value, ...newEvents];
        }

        // Update chat messages with AI response
        updateChatMessages([...updatedMessages, {
            id: Date.now(),
            content: result.data.chat.choices[0].message.content,
            timestamp: new Date(),
            isUser: false
        }]);
    } catch (error) {
        console.error('AI Chat Error:', error);
        updateChatMessages([...updatedMessages, {
            id: Date.now(),
            content: 'Sorry, I encountered an error. ' + (error.response?.data?.error || error.message || 'Please try again.'),
            timestamp: new Date(),
            isUser: false
        }]);
    }
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
                <!-- Left Column with Tabs -->
                <div :class="[
                    'min-h-0 flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-lg transition-all duration-200',
                    isAiChatFullscreen && activeTab === 'ai' ? 'col-span-12' : 'col-span-3'
                ]">
                    <!-- Tab Buttons -->
                    <div class="flex border-b border-gray-200 dark:border-gray-700">
                        <button
                            :class="[
                                'flex-1 px-4 py-4 font-semibold transition-colors duration-200',
                                activeTab === 'news'
                                    ? 'bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent border-b-2 border-blue-600 dark:border-blue-400'
                                    : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400'
                            ]"
                            @click="activeTab = 'news'"
                        >
                            Daily News Digest
                        </button>
                        <button
                            :class="[
                                'flex-1 px-4 py-4 font-semibold transition-colors duration-200',
                                activeTab === 'ai'
                                    ? 'bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent border-b-2 border-blue-600 dark:border-blue-400'
                                    : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400'
                            ]"
                            @click="activeTab = 'ai'"
                        >
                            AI Assistant
                        </button>
                        <button
                            v-if="activeTab === 'ai'"
                            :title="isAiChatFullscreen ? 'Exit Full Screen' : 'Full Screen'"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200"
                            @click="toggleAiChatFullscreen"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    v-if="isAiChatFullscreen"
                                    d="M4 8V4h4M4 4l6 6M16 4h4v4M20 4l-6 6M4 16v4h4M4 20l6-6M16 20h4v-4M20 20l-6-6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                                <path
                                    v-else
                                    d="M4 8V4h4M4 4l6 6M16 4h4v4M20 4l-6 6M4 16v4h4M4 20l6-6M16 20h4v-4M20 20l-6-6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="flex-1 min-h-0">
                        <Transition
                            enter-active-class="transition-opacity duration-200"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition-opacity duration-200"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                            mode="out-in"
                        >
                            <DailyNewsDigest v-if="activeTab === 'news'" class="h-full"/>
                            <AiChat
                                v-else
                                :messages="chatMessages"
                                :events="events"
                                class="h-full"
                                @update:messages="updateChatMessages"
                                @update:events="updateEvents"
                            />
                        </Transition>
                    </div>
                </div>

                <!-- Center Column -->
                <div v-show="!isAiChatFullscreen || activeTab !== 'ai'" class="col-span-6 min-h-0">
                    <Calendar
                        v-model:selected-date="selectedDate"
                        v-model:events="events"
                        class="h-full"
                    />
                </div>

                <!-- Right Column -->
                <div v-show="!isAiChatFullscreen || activeTab !== 'ai'" class="col-span-3 flex flex-col gap-6 min-h-0">
                    <div class="flex-[2] min-h-0">
                        <DailyDetailedView
                            v-model:events="events"
                            :selected-date="selectedDate"
                            class="h-full"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

