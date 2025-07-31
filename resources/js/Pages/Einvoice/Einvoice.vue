<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import { ref } from 'vue';
import TabView from "@/Components/TabView.vue";
import Toast from "@/Components/Toast.vue";
import SalesTransaction from './Partials/SalesTransaction.vue';
import RefundTransaction from './Partials/RefundTransaction.vue';

const home = ref({
    label: 'e-Invoice Submission',
});

const props = defineProps({
    selectedTab: Number,
})

const tabs = ref([
    { key: 'Sales Transaction', title: 'Sales Transaction', disabled: false },
    // { key: 'Refund Transaction', title: 'Refund Transaction', disabled: false },
]);



</script>

<template>
    <Head title="e-Invoice Submission" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />

        <TabView :tabs="tabs" :selectedTab="props.selectedTab ? props.selectedTab : 0">
            <template #sales-transaction>
                <SalesTransaction />
            </template>
            <!-- <template #refund-transaction>
                <RefundTransaction />
            </template> -->
        </TabView>
        <div>
        
        </div>
    </AuthenticatedLayout>

</template>