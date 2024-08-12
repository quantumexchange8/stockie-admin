<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Stock from "./Stock/Stock.vue";
import MerchantDetail from "./MerchantDetail/MerchantDetail.vue";
import Commision from "./EmployeeCommission/EmployeeCommision.vue";
import IncentiveProg from "./IncentiveProgram/IncentiveProgram.vue";
import Promotion from "@/Pages/Configuration/Promotion/Promotion.vue";
import { ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';

const home = ref({
    label: 'Configuration',
});

const props = defineProps({
    ActivePromotions: Array,
    InactivePromotions: Array,
    merchant: Object,
})

</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <TabGroup>
            <TabList class="flex border-b border-gray-200">
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                        'p-3 text-sm font-medium leading-none',
                        'focus:outline-none',
                        selected
                            ? 'text-primary-900 border-b-2 border-primary-900'
                            : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                        ]"
                    >
                        Stock
                    </button>
                </Tab>
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                        'p-3 text-sm font-medium leading-none',
                        'focus:outline-none',
                        selected
                            ? 'text-primary-900 border-b-2 border-primary-900'
                            : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                        ]"
                    >
                        Employee Commission
                    </button>
                </Tab>
                
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                        'p-3 text-sm font-medium leading-none',
                        'focus:outline-none',
                        selected
                            ? 'text-primary-900 border-b-2 border-primary-900'
                            : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                        ]"
                    >
                        Employee Incentive Programme
                    </button>
                </Tab>
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                        'p-3 text-sm font-medium leading-none',
                        'focus:outline-none',
                        selected
                            ? 'text-primary-900 border-b-2 border-primary-900'
                            : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                        ]"
                    >
                        Promotion
                    </button>
                </Tab>
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                        'p-3 text-sm font-medium leading-none',
                        'focus:outline-none',
                        selected
                            ? 'text-primary-900 border-b-2 border-primary-900'
                            : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                        ]"
                    >
                        Invoice Setting
                    </button>
                </Tab>
            </TabList>

            <TabPanels class="mt-2">
                <TabPanel
                    :class="[
                        'rounded-xl bg-white p-3',
                        'focus:outline-none',
                    ]"
                >
                    <Stock />
                </TabPanel>
                <TabPanel
                    :class="[
                        'rounded-xl bg-white p-3',
                        'focus:outline-none',
                    ]"
                >
                    <Commision />
                </TabPanel>
                <TabPanel
                    :class="[
                        'rounded-xl bg-white p-3',
                        'focus:outline-none',
                    ]"
                >
                    <IncentiveProg />
                </TabPanel>
                <TabPanel
                    :class="[
                        'rounded-xl bg-white p-3',
                        'focus:outline-none',
                    ]"
                >
                    <Promotion 
                        :ActivePromotions="ActivePromotions"
                        :InactivePromotions="InactivePromotions"
                    />
                </TabPanel>
                <TabPanel
                    :class="[
                        'rounded-xl bg-white p-3',
                        'focus:outline-none',
                    ]"
                >
                    <MerchantDetail 
                        :merchant="merchant"
                    />
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </AuthenticatedLayout>

</template>
