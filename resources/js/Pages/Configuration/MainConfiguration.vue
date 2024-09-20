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
    commission: {
        type: Array,
        required: true,
    },
    productNames: {
        type: Array,
        required: true,
    }
})

const { flashMessage } = useCustomToast();

const actions = {
    view: (productId) => `/configurations/productDetails/${productId}`,
    edit: () => ``,
    delete: () => ``,
};

const commissionColumn = ref([
    { field: 'comm_type', header: 'Type', width: '35', sortable: true},
    { field: 'rate', header: 'Rate', width: '15', sortable: true},
    { field: 'product', header: 'Product with this commission', width: '40', sortable: true},
    { field: 'action', header: '', width: '10', sortable: false},
])

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
                    :columns="commissionColumn"
                    :rows="commission"
                    :actions="actions"
                    :productNames="productNames"

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
