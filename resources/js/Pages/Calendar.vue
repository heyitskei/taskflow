<template>
    <div class="col-span-6 bg-red-100">
        <VCalendar/>
    </div>
    <!--        <h2>{{currentMonth}}</h2>-->
    <!--        <table class="bg-gray-200 h-96 w-full">-->
    <!--            <thead>-->
    <!--            <tr class="text-center ml-4 mr-4">-->
    <!--                <th>1</th>-->
    <!--                <th>2</th>-->
    <!--                <th>3</th>-->
    <!--                <th>4</th>-->
    <!--                <th>5</th>-->
    <!--                <th>6</th>-->
    <!--                <th>7</th>-->
    <!--            </tr>-->
    <!--            </thead>-->
    <!--            <tbody>-->
    <!--            <tr v-for="day in daysInMonth" class="text-center ml-4 mr-4">-->
    <!--&lt;!&ndash;                <td>{{ day }}</td>&ndash;&gt;-->
    <!--            </tr>-->
    <!--            </tbody>-->
    <!--        </table>-->
    <!--    </div>-->
</template>


<script setup>
import {VCalendar} from 'vuetify/labs/VCalendar'

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

//7 slots
//days of month are

//division - double vfor? put the numbers in the slots


// const rows = [
//     {
//         id: 1,
//         data: 'hi'
//     }
// ]
</script>
