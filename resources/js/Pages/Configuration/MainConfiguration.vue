<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Stock from "./Stock/Stock.vue";
import MerchantDetail from "./MerchantDetail/MerchantDetail.vue";
import Commision from "./EmployeeCommission/EmployeeCommision.vue";
import IncentiveProg from "./IncentiveProgram/IncentiveProgram.vue";
import TabView from "@/Components/TabView.vue";
import { ref } from "vue";
const tabs = ref([
    "Stock",
    "Employee Commission",
    "Employee Incentive Programme",
    "Promotion",
    "Merchant Detail",
]);
</script>

<template>
    <AuthenticatedLayout>
        <template #header> Configuration </template>

        <TabView :tabs="tabs">
            <template #tab-0>
                <Stock></Stock>
            </template>
            <template #tab-1>
                <Commision />
            </template>
            <template #tab-2>
                <IncentiveProg />
            </template>
            <template #tab-3>
                <p class="m-0">
                    At vero eos et accusamus et iusto odio dignissimos ducimus
                    qui blanditiis praesentium voluptatum deleniti atque
                    corrupti quos dolores et quas molestias excepturi sint
                    occaecati cupiditate non provident, similique sunt in culpa
                    qui officia deserunt mollitia animi, id est laborum et
                    dolorum fuga. Et harum quidem rerum facilis est et expedita
                    distinctio. Nam libero tempore, cum soluta nobis est
                    eligendi optio cumque nihil impedit quo minus.
                </p>
            </template>
            <template #tab-4> <MerchantDetail /> </template>
            <template #tab-5>
                <MerchantDetail></MerchantDetail>
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
