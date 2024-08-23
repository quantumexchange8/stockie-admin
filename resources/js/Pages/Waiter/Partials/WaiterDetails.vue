<script setup>
import axios from 'axios';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TabView from '@/Components/TabView.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import EntitledIncentive from './EntitledIncentive.vue';
import WaiterDetailCard from './WaiterDetailCard.vue';
import Commission from './Commission.vue';
import { CommissionIcon, GrowthIcon, WaiterSalesIcon } from '@/Components/Icons/solid';
import Sales from './Sales.vue';
import Attendance from './Attendance.vue';

const props = defineProps({
    id: [Number, String],
    defaultDateFilter: Array,
    order: Array,
})

const home = ref({
    label: 'Waiter',
    route: '/waiter/waiter'
});

const items = ref([
    { label: 'Waiter Detail'},
]);

const waiter = ref(null);
const tabs = ref(['Sales', 'Commission', 'Attendance']);
const salesColumn = ref([
    {field: '', header: 'Date', width: '11.5', sortable: true},
    {field: 'order', header: 'Order', width: '21.5', sortable: true},
    {field: 'sales', header: 'Sales', width: '20', sortable: true},
    {field: 'commission', header: 'Commission', width: '35', sortable: true},
    {field: 'action', header: '', width: '20', sortable: false},
]);

onMounted(async () => {
    try{
    const response = await axios.get(`/waiter/waiter/waiterDetailsWithId/${props.id}`);
    waiter.value = response.data;
    } catch (error) {
        console.error(error);
    }
})

</script>

<template>
    <Head title="Waiter Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb
                :home="home"
                :items="items"
            />
        </template>

        <div class="w-full">
            <div class="w-full flex flex-col gap-5 justify-center">
                <div class="w-full flex flex-row gap-5">
                    <!-- sales and commission -->
                    <div class="flex flex-col gap-6">
                        <div 
                            class="w-full flex p-5 flex-col items-start gap-2.5 
                            grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100
                            bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative"
                        >
                            <div class="flex flex-col gap-1 self-stretch"> 
                                <GrowthIcon />
                                <span class="text-primary-100 text-sm font-medium whitespace-nowrap w-full">Total sales this month</span>
                                <span class="text-primary-25 text-lg font-medium ">RM 13,200.23</span>
                            </div>
                            <div class="absolute bottom-0 right-0">
                                <WaiterSalesIcon />
                            </div>
                        </div>
                        <div 
                            class="w-full flex p-5 flex-col items-start gap-2.5 
                            grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100"
                        >
                            <div class="flex flex-col gap-1 self-stretch">
                                <CommissionIcon />
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Commission in this month</span>
                                <span class="text-primary-900 text-lg font-medium whitespace-nowrap">RM 2,923.98</span>
                                <span class="text-primary-300 text-sm font-normal whitespace-nowrap">(RM 2,423.98 + RM500)</span>
                            </div>
                        </div>
                    </div>

                    <!-- entitled incentive -->
                    <div 
                        class="w-full p-4 bg-white rounded-[5px] col-span-4 border border-solid border-primary-100"
                    >
                        <EntitledIncentive />
                    </div>

                    <!-- waiter detail -->
                    <div 
                        class="w-full p-6 bg-white rounded-[5px] col-span-4 border border-solid border-primary-100"
                    >
                        <WaiterDetailCard :waiter="waiter" />
                    </div>
                </div>
                
                <!-- Daily Sales Report -->
                <div class="">
                    <TabView :tabs="tabs">
                        <template #sales>
                            <div>
                                <Sales :order="order" :dateFilter="defaultDateFilter"/>
                            </div>
                        </template>
                        <template #commission>
                            <div>
                                <Commission />
                            </div>
                        </template>
                        <template #attendance>
                            <div>
                                <Attendance />
                            </div>
                        </template>
                    </TabView>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
