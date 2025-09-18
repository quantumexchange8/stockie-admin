<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import { ref } from 'vue';
import TabView from "@/Components/TabView.vue";
import Toast from "@/Components/Toast.vue";
import SalesTransaction from './Partials/SalesTransaction.vue';
import RefundTransaction from './Partials/RefundTransaction.vue';
import { wTrans } from 'laravel-vue-i18n';

const home = ref({
    label: wTrans('public.einvoice_submission_header'),
});

const props = defineProps({
    selectedTab: Number,
})

const tabs = ref([
    { key: 'Sales Transaction', title: wTrans('public.sales_transaction'), disabled: false },
    // { key: 'Refund Transaction', title: 'Refund Transaction', disabled: false },
]);



</script>

<template>
    <Head :title="$t('public.einvoice_submission_header')" />

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