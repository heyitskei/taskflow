<template>
    <div class="relative">
        <!-- Toggle button (only shown when completely minimized) -->
        <button
            v-show="!isVisible"
            class="fixed bottom-6 right-6 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg
                   hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] z-50"
            @click="isVisible = true"
        >
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path clip-rule="evenodd"
                          d="M18 10c0 4.418-3.582 8-8 8s-8-3.582-8-8 3.582-8 8-8 8 3.582 8 8zm-2 0c0 3.314-2.686 6-6 6s-6-2.686-6-6 2.686-6 6-6 6 2.686 6 6z"
                          fill-rule="evenodd"/>
                    <path clip-rule="evenodd" d="M10 12a2 2 0 100-4 2 2 0 000 4z" fill-rule="evenodd"/>
                </svg>
                AI Assistant
            </span>
        </button>

        <!-- Small Chat Window -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-show="isVisible && !isExpanded"
                 class="fixed bottom-6 right-6 w-[400px] h-[500px] bg-white rounded-xl shadow-2xl flex flex-col z-50"
            >
                <!-- Small Window Header -->
                <div class="flex items-center justify-between p-3 border-b">
                    <h2 class="text-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        AI Assistant
                    </h2>
                    <div class="flex gap-2">
                        <button
                            class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                            title="Maximize"
                            @click="isExpanded = true"
                        >
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2"/>
                            </svg>
                        </button>
                        <button
                            class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                            title="Minimize"
                            @click="isVisible = false"
                        >
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Small Window Messages -->
                <div class="flex-1 overflow-auto p-3 space-y-3 messages-container">
                    <div v-for="message in messages"
                         :key="message.id"
                         :class="[
                             'max-w-[80%] rounded-lg p-3',
                             message.isUser ?
                                 'bg-blue-50 ml-auto' :
                                 'bg-gray-50'
                         ]"
                    >
                        <div :class="message.isUser ? 'text-blue-800' : 'text-gray-800'" class="text-sm">
                            {{ message.content }}
                        </div>
                        <div :class="message.isUser ? 'text-blue-400' : 'text-gray-400'" class="text-xs mt-1">
                            {{ formatTime(message.timestamp) }}
                        </div>
                    </div>
                </div>

                <!-- Small Window Input -->
                <div class="p-3 border-t bg-white">
                    <div class="flex gap-2 items-end">
                        <textarea
                            v-model="newMessage"
                            class="flex-1 px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition-all duration-200 resize-none bg-gray-50 text-sm"
                            placeholder="Ask your AI assistant..."
                            rows="2"
                            @keydown.enter.prevent="sendMessage"
                        ></textarea>
                        <button
                            :disabled="!newMessage.trim()"
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg
                                   hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] whitespace-nowrap
                                   disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                            @click="sendMessage"
                        >
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Full Screen Chat Window -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-show="isExpanded"
                 class="fixed inset-6 bg-white rounded-xl shadow-2xl flex flex-col z-50"
            >
                <!-- Full Screen Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h2 class="text-xl font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        AI Assistant
                    </h2>
                    <button
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                        title="Exit Full Screen"
                        @click="isExpanded = false"
                    >
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    </button>
                </div>

                <!-- Full Screen Messages -->
                <div class="flex-1 overflow-auto p-4 space-y-4 messages-container">
                    <div v-for="message in messages"
                         :key="message.id"
                         :class="[
                             'max-w-[80%] rounded-lg p-4',
                             message.isUser ?
                                 'bg-blue-50 ml-auto' :
                                 'bg-gray-50'
                         ]"
                    >
                        <div :class="message.isUser ? 'text-blue-800' : 'text-gray-800'" class="text-base">
                            {{ message.content }}
                        </div>
                        <div :class="message.isUser ? 'text-blue-400' : 'text-gray-400'" class="text-xs mt-2">
                            {{ formatTime(message.timestamp) }}
                        </div>
                    </div>
                </div>

                <!-- Full Screen Input -->
                <div class="p-4 border-t bg-white">
                    <div class="flex gap-4 items-end max-w-4xl mx-auto">
                        <textarea
                            v-model="newMessage"
                            class="flex-1 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition-all duration-200 resize-none bg-gray-50 text-base"
                            placeholder="Ask your AI assistant..."
                            rows="3"
                            @keydown.enter.prevent="sendMessage"
                        ></textarea>
                        <button
                            :disabled="!newMessage.trim()"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg
                                   hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] whitespace-nowrap
                                   disabled:opacity-50 disabled:cursor-not-allowed text-base font-medium"
                            @click="sendMessage"
                        >
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Backdrop (only for full screen mode) -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="isExpanded"
                class="fixed inset-0 bg-black bg-opacity-25 z-40"
                @click="isExpanded = false"
            ></div>
        </Transition>
    </div>
</template>

<script setup>
import {nextTick, ref, watch} from 'vue';
import axios from "axios";

const messages = ref([]);
const newMessage = ref('');
const isVisible = ref(false);
const isExpanded = ref(false);

// Scroll to bottom when new messages are added
watch(() => messages.value.length, () => {
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
    if (!newMessage.value.trim()) return;

    messages.value.push({
        id: Date.now(),
        content: newMessage.value,
        timestamp: new Date(),
        isUser: true
    });

    const result = await axios.post('/api/openai', {
        prompt: newMessage.value
    });

    console.log(result.data);
    newMessage.value = '';
    const answer = result.data.choices[0].message.content;

    messages.value.push({
        id: Date.now(),
        content: answer,
        timestamp: new Date(),
        isUser: false
    });
}
</script>

<style scoped>
.messages-container {
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 3px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateY(20px);
    opacity: 0;
}
</style>
