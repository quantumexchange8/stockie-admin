<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MerchantDetail from "./MerchantDetail/MerchantDetail.vue";
import Commision from "./EmployeeCommission/EmployeeCommision.vue";
import IncentiveProg from "./IncentiveProgram/IncentiveProgram.vue";
import Promotion from "@/Pages/Configuration/Promotion/Promotion.vue";
import { onMounted, ref, watch } from 'vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TabView from "@/Components/TabView.vue";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables";
import PointsSettings from "./PointsSettings/PointsSettings.vue";
import DiscountSettings from "./DiscountSettings/DiscountSettings.vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";

const home = ref({
    label: 'Configuration',
});

const props = defineProps({
    ActivePromotions: Array,
    InactivePromotions: Array,
    merchant: Object,
    selectedTab: Number,
})

const tabs = ref(["Discount Settings", "Employee Commission", "Employee Incentive Programme", "Promotion", "Invoice Setting", "Points Settings"]);
const merchant = ref(props.merchant);

const refetchMerchant = async() => {
    const response = await axios.get(route('configurations.refetchMerchant'));
    merchant.value = response.data;
}

const { flashMessage } = useCustomToast();

onMounted(() => {
    flashMessage();
});

</script>

<template>
    <Head title="Configuration" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />

        <TabView :tabs="tabs" :selectedTab="props.selectedTab ? props.selectedTab : 0">
            <template #discount-settings>
                <DiscountSettings />
            </template>
            <template #employee-commission>
                <Commision />
            </template>
            <template #employee-incentive-programme>
                <IncentiveProg />
            </template>
            <template #promotion>
                <Promotion 
                    :ActivePromotions="ActivePromotions"
                    :InactivePromotions="InactivePromotions"
                />
            </template>
            <template #invoice-setting>
                <MerchantDetail 
                    :merchant="merchant" 
                    @refetchMerchant="refetchMerchant"
                />
            </template>
            <template #points-settings>
                <PointsSettings />
            </template>
        </TabView>
    </AuthenticatedLayout>

</template>
