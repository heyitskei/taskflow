<template>
    <div class="bg-white rounded-xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl flex flex-col h-full">
        <div class="flex-1 overflow-auto mb-4 space-y-4">
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

        <div class="flex gap-4 items-end">
            <textarea
                v-model="newMessage"
                class="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       transition-all duration-200 resize-none bg-gray-50"
                placeholder="Ask your AI assistant..."
                rows="2"
                @keydown.enter.prevent="sendMessage"
            ></textarea>
            <button
                :disabled="!newMessage.trim()"
                class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg
                       hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] whitespace-nowrap"
                @click="sendMessage"
            >
                Send
            </button>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import axios from "axios";

const messages = ref([]);
const newMessage = ref('');

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
  })
  console.log(result.data.choices[0].message.content);

  // TODO: Remove this temporary response once OpenAI is integrated
    setTimeout(() => {
        messages.value.push({
            id: Date.now(),
            content: "I'm processing your request...",
            timestamp: new Date(),
            isUser: false
        });
    }, 500);

  newMessage.value = '';
}
</script>
