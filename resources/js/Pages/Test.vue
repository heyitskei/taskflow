<script setup>
import Calendar from "./Calendar.vue";

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
</script>

<template>
    <div class="flex flex-col">
        <div>
            <p class="text-center mb-10 text-3xl">TaskFlow</p>
        </div>
        <div class="grid grid-cols-12 gap-4">
            <div class="text-center col-span-3 bg-gray-100">
                <p class="text-2xl">Daily News Digest</p>
                <div class="bg-gray-200 h-96">Hi</div>
            </div>

            <Calendar :currentMonth="currentMonth"/>

            <div class="text-center col-span-3 bg-green-100">
                <p class="text-2xl">Daily Detailed View</p>
                <div class="bg-gray-200 h-96">Hi</div>
            </div>
        </div>
        <div class="container mx-auto text-center h-40 bg-orange-100">
            <div class="my-4">
                <textarea class="w-2/3" placeholder="AI Reply..."></textarea>
            </div>

            <div class="my-4">
                <input class="align-bottom" placeholder="Here is your AI" type="text"/>
                <button class="align-bottom" type="submit">Enter</button>
            </div>
        </div>
    </div>
</template>

