<template>
    <div class="h-full flex flex-col">
        <div class="flex-1 overflow-auto">
            <div v-if="news.length === 0" class="flex-1 flex flex-col items-center justify-center p-4 pt-16">
                <div class="text-gray-400 mb-2">
                    📰
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                    Latest updates will appear here
                </p>
            </div>
            <div v-else class="space-y-3 p-4">
                <div v-for="item in news"
                     :key="item.id"
                     class="bg-white dark:bg-gray-900 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ item.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ item.content }}</p>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ formatTime(item.timestamp) }}</div>
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
