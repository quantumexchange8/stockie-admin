<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TabView from "@/Components/TabView.vue";
import SaleTransactionListing from './Partials/SaleTransactionListing.vue';
import RefundTransactionListing from './Partials/RefundTransactionListing.vue';
import Toast from '@/Components/Toast.vue';
import { wTrans } from 'laravel-vue-i18n';

const home = ref({
    label: wTrans('public.transaction_listing_header'),
});

const props = defineProps({
    selectedTab: Number,
})

const tabs = ref([
    { key: 'Sales Transaction', title: wTrans('public.sales_transaction'), disabled: false },
    { key: 'Refund Transaction', title: wTrans('public.refund_transaction'), disabled: false },
]);

</script>

<template>
    <Head :title="$t('public.transaction_listing_header')" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />

        <div class="w-full p-6 flex flex-col border border-primary-100 rounded-[5px]">
            <div class="text-primary-900 text-md font-medium">
                {{ $t('public.transaction_header') }}
            </div>
            <div class="flex flex-col gap-6">
                <TabView :tabs="tabs" :selectedTab="props.selectedTab ? props.selectedTab : 0">
                    <template #sales-transaction>
                        <SaleTransactionListing />
                    </template>
                    <template #refund-transaction>
                        <RefundTransactionListing />
                    </template>
                </TabView>
            </div>
        </div>
    </AuthenticatedLayout>

</template>
