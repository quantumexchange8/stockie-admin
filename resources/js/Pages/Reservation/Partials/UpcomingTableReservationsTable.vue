<script setup>
import { CardIcon, CashIcon, EWalletIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs';
import { ref, watch } from 'vue';

const props = defineProps({
    upcomingReservations: Array,
});

const reservations = ref(props.upcomingReservations);

watch(() => props.upcomingReservations, (newvalue) => {
    reservations.value = newvalue;
});

</script>

<template>

    <div class="flex flex-col bg-[#FCFCFC]">
        <div class="flex w-full items-center py-2 px-3 gap-3 bg-gray-100">
            <div class="w-1/3 text-sm text-gray-950 font-semibold">Date</div>
            <div class="w-1/3 text-sm text-gray-950 font-semibold">Time</div>
            <div class="w-1/3 text-sm text-gray-950 font-semibold">Table</div>
        </div>
        <div class="flex flex-col p-3 gap-4">
            <template v-for="(item, index) in reservations" :key="item.id" >
                <hr v-if="index > 0" class="w-full h-[1px] bg-grey-100">
                <div class="flex w-full items-center gap-3">
                    <div class="w-1/3">{{ dayjs(item.reservation_date).format('DD/MM/YYYY') }}</div>
                    <div class="w-1/3">{{ dayjs(item.reservation_date).format('hh:mm A') }}</div>
                    <div class="w-1/3">{{ item.table_no.map((table) => table.name).join(', ') }}</div>
                </div>
            </template>
        </div>
    </div>

</template>