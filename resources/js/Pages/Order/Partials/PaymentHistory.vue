<script setup>
import axios from 'axios';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import Tag from '@/Components/Tag.vue';
import { ArrowUpIcon, CircledArrowHeadDownIcon, CircledArrowHeadUpIcon } from '@/Components/Icons/solid';

const props = defineProps({
    selectedTable: Object,
    order: Object
})

const emit = defineEmits(['close', 'fetchZones']);

const tableOrders = ref([]);
const taxes = ref([]);
const collapsedSections = ref({}); // Object to keep track of each section's collapsed state

const fetchOrderDetails = async () => {
    try {
        const response = await axios.get(route('orders.getOccupiedTablePayments', props.selectedTable.id));
        tableOrders.value = response.data.table_orders;
        taxes.value = response.data.taxes;

        if (tableOrders.value) {
            tableOrders.value.forEach(order => {
                collapsedSections.value[order.order_no] = true; // Collapsed by default
            });
        }
    } catch (error) {
        console.error(error);
        tableOrders.value = [];
    } finally {

    }
};

onMounted(() => fetchOrderDetails());

const toggleCollapse = (orderNo) => collapsedSections.value[orderNo] = !collapsedSections.value[orderNo];

const getItemTypeName = (type) => {
    switch (type) {
        case 'Keep': return 'Keep item';
        case 'Redemption': return 'Redeemed product'
        case 'Reward': return 'Entry reward'
    };
};

const getKeepItemName = (item) => {
    var itemName = '';
    item.sub_items.forEach(subItem => {
        itemName = item.product.product_items.find(productItem => productItem.id === subItem.product_item_id).inventory_item.item_name;
    });
    if (itemName) return itemName;
};
</script>

<template>
    <div class="w-full max-h-[calc(100dvh-9.5rem)] pr-2 overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col gap-y-6 px-6 py-4 items-start rounded-[5px]">
            <div 
                v-if="tableOrders && tableOrders.length > 0"
                v-for="(order, index) in tableOrders" 
                :key="index"
                class="flex flex-col items-center gap-y-5 p-4 w-full self-stretch rounded bg-grey-50 shadow-[0px_4px_15.8px_0px_rgba(13,13,13,0.08)] !z-[1020]" 
            >
                <div class="flex flex-row items-center self-stretch justify-between pl-2 border-l-[6px] border-primary-800">
                    <div class="flex flex-col gap-y-2 items-start justify-center">
                        <p class="text-grey-950 text-md font-normal">Bill #{{ order.payment.receipt_no }}</p>
                        <div class="flex flex-row gap-x-3 items-center">
                            <p class="text-grey-900 text-base font-normal">#{{ order.order_no }}</p>
                            <span class="text-grey-200">&#x2022;</span>
                            <div class="flex flex-row gap-x-2 items-center">
                                <!-- <div class="size-6 bg-primary-100 rounded-full" v-if="order.customer"></div> -->
                                <img 
                                    :src="order.customer?.image ? order.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="size-6 rounded-full"
                                    v-if="order.customer"
                                >
                                <p class="text-grey-900 text-base font-normal">{{ order.customer?.full_name ?? 'Guest' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center items-end gap-y-1">
                        <p class="text-grey-950 text-md font-bold truncate">RM {{ parseFloat(order.payment.grand_total ?? 0).toFixed(2) }}</p>
                        <p class="text-primary-800 text-base font-normal truncate">(+{{ order.payment.point_earned }} pts)</p>
                    </div>
                </div>
                <div class="w-full flex flex-col gap-y-2 items-start self-stretch">
                    <div class="grid grid-cols-12 gap-3 items-center py-3" v-for="(item, index) in order.order_items" :key="index">
                        <div class="col-span-8 grid grid-cols-12 gap-3 items-center">
                            <!-- <div class="col-span-3 p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div> -->
                            <img 
                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="col-span-3 size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100"
                            >
                            <div class="col-span-8 flex flex-col gap-2 items-start justify-center self-stretch w-full">
                                <div class="flex flex-row gap-x-2 self-stretch items-center">
                                    <Tag :value="getItemTypeName(item.type)" variant="blue" v-if="item.type !== 'Normal'"/>
                                    <p class="text-sm font-medium text-grey-900 truncate flex-shrink">{{ item.type === 'Normal' ? item.product.product_name : item.item_name }}</p>
                                </div>

                                <p class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink">x{{ item.item_qty }}</p>
                            </div>
                        </div>

                        <p class="col-span-4 text-grey-950 text-base font-medium text-right">RM {{ parseFloat(item.amount ?? 0).toFixed(2) }}</p>
                    </div>
                </div>

                <transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <div class="flex flex-col gap-y-1 items-start self-stretch" v-show="!collapsedSections[order.order_no]">
                        <div class="flex flex-row justify-between items-start self-stretch">
                            <p class="text-grey-900 text-base font-normal">Sub-total</p>
                            <p class="text-grey-900 text-base font-medium">RM {{ parseFloat(order.payment.total_amount ?? 0).toFixed(2) }}</p>
                        </div>
                        <div class="flex flex-row justify-between items-start self-stretch">
                            <p class="text-grey-900 text-base font-normal">SST ({{ Math.round(taxes['SST'] ?? 0) }}%)</p>
                            <p class="text-grey-900 text-base font-medium">RM {{ parseFloat(order.payment.sst_amount ?? 0).toFixed(2) }}</p>
                        </div>
                        <div class="flex flex-row justify-between items-start self-stretch">
                            <p class="text-grey-900 text-base font-normal">Service Tax ({{ Math.round(taxes['Service Tax'] ?? 0) }}%)</p>
                            <p class="text-grey-900 text-base font-medium">RM {{ parseFloat(order.payment.service_tax_amount ?? 0).toFixed(2) }}</p>
                        </div>
                    </div>
                </transition>

                <div class="flex justify-center items-center">
                    <div class="flex flex-row gap-x-2 cursor-pointer" @click="toggleCollapse(order.order_no)">
                        <CircledArrowHeadDownIcon :class="{ 'rotate-180': !collapsedSections[order.order_no] }" class="text-primary-700 transition-transform duration-300" />
                        <p class="text-primary-900 text-sm font-normal cursor-pointer">
                            {{ collapsedSections[order.order_no] ? 'View Price Breakdown' : 'Hide Price Breakdown' }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else>
                <p class="text-grey-900 text-base font-medium">No bills have been paid yet.</p>
            </div>
        </div>
    </div>
</template>
    