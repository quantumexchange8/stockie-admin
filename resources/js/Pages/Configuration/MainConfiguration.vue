<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Stock from "./Stock/Stock.vue";
import MerchantDetail from "./MerchantDetail/MerchantDetail.vue";
import Commision from "./EmployeeCommission/EmployeeCommision.vue";
import IncentiveProg from "./IncentiveProgram/IncentiveProgram.vue";
</script>

<template>
    <AuthenticatedLayout>
        <template #header> Configuration </template>

        <TabView
            :pt="{
                inkbar: {
                    class: 'border-2 border-primary-900 absolute bottom-0 left-0 right-0',
                },
                navContent: {
                    class: 'w-fit',
                },
            }"
        >
            <TabPanel header="Stock"> <Stock></Stock></TabPanel>
            <TabPanel header="Employee Commission">
                <Commision />
            </TabPanel>
            <TabPanel header="Employee Incentive Programme">
                <IncentiveProg />
            </TabPanel>
            <TabPanel header="Promotion">
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
            </TabPanel>
            <TabPanel header="Merchant Detail">
                <MerchantDetail></MerchantDetail>
            </TabPanel>
        </TabView>
    </AuthenticatedLayout>
</template>
