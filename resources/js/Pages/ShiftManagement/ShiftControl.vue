<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Toast from '@/Components/Toast.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import OpenedShift from './Partials/OpenedShift.vue';
import ShiftControlListing from './Partials/ShiftControlListing.vue';

const props = defineProps({
    shiftTransactions: Array,
});

const home = ref({
    label: 'Shift Control',
})

const shiftTransactionsList = ref(props.shiftTransactions);
const selectedShift = ref(null);

watch(() => props.shiftTransactions, (newValue) => {
    shiftTransactionsList.value = newValue;
    selectedShift.value = newValue.find((shift) => shift.status === 'opened') ?? null;
}, { immediate: true });

</script>

<template>
    <Head title="Shift Control" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />
        
        <div class="grid grid-cols-1 md:grid-cols-12 items-center gap-5 h-full">
            <ShiftControlListing
                :shiftTransactions="shiftTransactionsList"
                :currentSelectedShift="selectedShift"
                @update:shift-listing="shiftTransactionsList = $event"
                @update:selected-shift="selectedShift = $event"
             />
            <OpenedShift
                :currentSelectedShift="selectedShift"
                @update:shift-listing="shiftTransactionsList = $event" 
            />
        </div>
    </AuthenticatedLayout>        
</template>

