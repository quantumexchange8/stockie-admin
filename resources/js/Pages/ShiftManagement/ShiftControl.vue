<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import Tag from '@/Components/Tag.vue';
import Toast from '@/Components/Toast.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import OpenedShift from './Partials/OpenedShift.vue';
import ShiftControlListing from './Partials/ShiftControlListing.vue';

const props = defineProps({
    shiftTransactions: Array,
});

const home = ref({
    label: 'Shift Control',
})

const shiftTransactionsList = ref(props.shiftTransactions);

const openedShift = computed(() => {
    return shiftTransactionsList.value.find((shift) => shift.status === 'opened');
});

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
                @update:shift-listing="shiftTransactionsList = $event"
             />
            <OpenedShift
                :currentOpenedShift="openedShift"
                @update:shift-listing="shiftTransactionsList = $event" 
            />
        </div>
    </AuthenticatedLayout>        
</template>

