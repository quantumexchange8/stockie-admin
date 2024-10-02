<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Stock from "./Stock/Stock.vue";
import MerchantDetail from "./MerchantDetail/MerchantDetail.vue";
import Commision from "./EmployeeCommission/EmployeeCommision.vue";
import IncentiveProg from "./IncentiveProgram/IncentiveProgram.vue";
import Promotion from "@/Pages/Configuration/Promotion/Promotion.vue";
import { onMounted, ref } from 'vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TabView from "@/Components/TabView.vue";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables";

const home = ref({
    label: 'Configuration',
});

const tabs = ref(["Stock", "Employee Commission", "Employee Incentive Programme", "Promotion", "Invoice Setting"]);

const props = defineProps({
    ActivePromotions: Array,
    InactivePromotions: Array,
    merchant: Object,
})

const { flashMessage } = useCustomToast();

onMounted(() => {
    flashMessage();
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />

        <TabView :tabs="tabs">
            <template #stock>
                <Stock />
            </template>
            <template #employee-commission>
                <Commision   
                />
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
                />
            </template>
        </TabView>
    </AuthenticatedLayout>

</template>
