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
import ShiftSetting from "./Shift/ShiftSetting.vue";
import SecuritySettings from "./SecuritySettings/SecuritySettings.vue";
import PrintingSettings from "./PrintingSettings/PrintingSettings.vue";

const home = ref({
    label: 'Configuration',
});

const props = defineProps({
    ActivePromotions: Array,
    InactivePromotions: Array,
    merchant: Object,
    selectedTab: Number,
})

const tabs = ref([
    { key: 'Discount Settings', title: 'Discount Settings', disabled: false },
    { key: 'Employee Commission', title: 'Employee Commission', disabled: false },
    { key: 'Employee Incentive Programme', title: 'Employee Incentive Programme', disabled: false }, 
    { key: 'Employee Shift Settings', title: 'Employee Shift Settings', disabled: false },
    { key: 'Promotion', title: 'Promotion', disabled: false },
    { key: 'Invoice Setting', title: 'Invoice Setting', disabled: false },
    { key: 'Points Settings', title: 'Points Settings', disabled: false }, 
    { key: 'Security Settings', title: 'Security Settings', disabled: false }, 
    { key: 'Printing Settings', title: 'Printing Settings', disabled: false }
]);

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
            <template #employee-shift-settings>
                <ShiftSetting />
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
            <template #security-settings>
                <SecuritySettings />
            </template>
            <template #printing-settings>
                <PrintingSettings />
            </template>
        </TabView>
    </AuthenticatedLayout>

</template>
