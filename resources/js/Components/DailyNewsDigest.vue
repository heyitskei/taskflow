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
                    <a :href="item.url" class="cursor-pointer hover:underline" target="_blank"><h3
                        class="font-medium text-gray-900 dark:text-gray-100">{{ item.title }}</h3></a>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ item.description }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ item.source }}</p>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ formatTime(item.published_at) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import axios from "axios";

const news = ref([]);

function formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

onMounted(async () => {
    const params = new URLSearchParams();
    params.append('api_token', import.meta.env.VITE_THE_NEWS_API);
    params.append('locale', 'us, ca, gb, ru, kr, cn');
    params.append('language', 'en');
    params.append('include_similar', 'false');
    // params.append('search', 'AI');
    params.append('search_fields', 'title, description, keywords, main_text');
    params.append('categories', 'general, science, business, entertainment, tech, politics');
    const response = await axios.get('https://api.thenewsapi.com/v1/news/top', {params})

    news.value = response.data.data;
})
</script>
