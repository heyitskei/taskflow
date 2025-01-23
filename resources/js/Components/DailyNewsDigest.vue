<template>
    <div class="bg-white rounded-xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl h-full flex flex-col">
        <h2 class="text-lg font-semibold mb-3 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            Daily News Digest
        </h2>
        <div class="flex-1 bg-gray-50 rounded-lg p-4 flex flex-col overflow-auto">
            <div v-if="news.length === 0" class="flex-1 flex flex-col items-center justify-center">
                <div class="text-gray-400 mb-2">
                    📰
                </div>
                <p class="text-sm text-gray-600 text-center">
                    Latest updates will appear here
                </p>
            </div>
            <div v-else class="space-y-3">
                <div v-for="item in news"
                     :key="item.id"
                     class="bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <h3 class="font-medium text-gray-800">{{ item.title }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ item.content }}</p>
                    <div class="text-xs text-gray-400 mt-2">{{ formatTime(item.timestamp) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';

const news = ref([]);

function formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}
</script>
