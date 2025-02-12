<template>
    <div class="h-full flex flex-col p-4 bg-white dark:bg-gray-800 rounded-lg">
        <!-- Messages -->
        <div class="flex-1 overflow-auto space-y-4 messages-container">
            <div v-for="message in messages"
                 :key="message.id"
                 :class="[
                     'max-w-[90%] rounded-lg p-3',
                     message.isUser ?
                         'bg-gray-100 dark:bg-gray-900 ml-auto border border-gray-200 dark:border-gray-700' :
                         'bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700'
                 ]"
            >
                <div :class="message.isUser ? 'text-gray-900 dark:text-gray-50' : 'text-gray-900 dark:text-gray-50'"
                     class="text-sm">
                    {{ message.content }}
                </div>
                <div :class="message.isUser ? 'text-gray-600 dark:text-gray-300' : 'text-gray-600 dark:text-gray-300'"
                     class="text-xs mt-1">
                    {{ formatTime(message.timestamp) }}
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex gap-2 items-end">
                <textarea
                    v-model="newMessage"
                    class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600
                           focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent
                           transition-all duration-200 resize-none bg-white dark:bg-gray-900
                           text-sm text-gray-900 dark:text-gray-50 placeholder-gray-500 dark:placeholder-gray-400
                           hover:border-gray-300 dark:hover:border-gray-500"
                    placeholder="Ask your AI assistant..."
                    rows="2"
                    @keydown.enter.prevent="sendMessage"
                ></textarea>
                <button
                    :disabled="!newMessage.trim()"
                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-500 dark:to-purple-500 text-white rounded-lg
                           hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] whitespace-nowrap
                           disabled:opacity-50 disabled:cursor-not-allowed text-sm
                           hover:from-blue-700 hover:to-purple-700 dark:hover:from-blue-600 dark:hover:to-purple-600"
                    @click="sendMessage"
                >
                    Send
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {nextTick, ref, watch} from 'vue';
import axios from "axios";

const props = defineProps({
    messages: {
        type: Array,
        required: true,
        default: () => []
    },
    events: {
        type: Array,
        required: true,
        default: () => []
    }
});

const emit = defineEmits(['update:messages', 'update:events']);
const newMessage = ref('');

// Scroll to bottom when new messages are added
watch(() => props.messages.length, () => {
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

    const updatedMessages = [...props.messages, {
        id: Date.now(),
        content: newMessage.value,
        timestamp: new Date(),
        isUser: true
    }];

    emit('update:messages', updatedMessages);
    const userMessage = newMessage.value;
    newMessage.value = '';

    try {
        const result = await axios.post('/api/openai', {
            prompt: userMessage
        });

        if (result.data.error) {
            throw new Error(result.data.error);
        }

        // Update messages with AI response
        const aiResponse = result.data.chat.choices[0].message.content;
        const newMessages = [...updatedMessages, {
            id: Date.now(),
            content: aiResponse,
            timestamp: new Date(),
            isUser: false
        }];
        emit('update:messages', newMessages);

        if (result.data.events && result.data.events.length > 0) {
            const updatedEvents = [...props.events, ...result.data.events];
            emit('update:events', updatedEvents);
        }
    } catch (error) {
        console.error('AI Chat Error:', error);
        emit('update:messages', [...updatedMessages, {
            id: Date.now(),
            content: 'Sorry, I encountered an error. ' + (error.response?.data?.error || error.message || 'Please try again.'),
            timestamp: new Date(),
            isUser: false
        }]);
    }
}
</script>

<style scoped>
.messages-container {
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) rgba(0, 0, 0, 0);
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

@media (prefers-color-scheme: dark) {
    .messages-container {
        scrollbar-color: rgba(156, 163, 175, 0.3) rgba(0, 0, 0, 0);
    }

    .messages-container::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.3);
    }
}
</style>
