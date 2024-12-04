<script setup>
import Button from '@/Components/Button.vue';
import { UndrawFreshIllust } from '@/Components/Icons/illus';
import { CircledArrowHeadRightIcon, TableRoomIcon, WaiterIcon } from '@/Components/Icons/solid';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    order_notifications: Object,
    inventory_notifications: Object,
    waiter_notifications: Object,
    all_notifications: Number,
    notificationLength: Number,
})
const showMoreInventory = ref(false);
const showMoreWaiter = ref(false);
const showMoreOrder = ref(false);

// Convert the object to an array
const inventoryArray = computed(() => Object.values(props.inventory_notifications));
const waiterArray = computed(() => Object.values(props.waiter_notifications));
const orderArray = computed(() => Object.values(props.order_notifications));

// Extract the first notification
const firstInventory = computed(() => inventoryArray.value[0]);
const remainingInventory = computed(() => inventoryArray.value.slice(1));

const firstWaiter = computed(() => waiterArray.value[0]);
const remainingWaiter = computed(() => waiterArray.value.slice(1));

const firstOrder = computed(() => orderArray.value[0]);
const remainingOrder = computed(() => orderArray.value.slice(1));

const openFoldedInventory = () => {
    showMoreInventory.value = true;
}

const openFoldedWaiter = () => {
    showMoreWaiter.value = true;
}

const openFoldedOrder = () => {
    showMoreOrder.value = true;
}

const calcTimeDiff = (created_at) => {
    const createdDate = new Date(created_at);
    const diffInMilliseconds = Date.now() - createdDate.getTime();
    const diffMinutes = Math.floor(diffInMilliseconds / (1000 * 60));
    const days = Math.floor(diffMinutes / (60 * 24));
    const hours = Math.floor((diffMinutes % (60 * 24)) / 60);
    const minutes = diffMinutes % 60;

    if (days > 7) {
        return createdDate.toISOString().split('T')[0];
    } else if (days > 0) {
        return `${days}d ${hours}h`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
};


</script>

<template>
    <div class="flex flex-col max-w-80 max-h-[calc(100dvh-20.5rem)] justify-between items-center shrink-0 rounded-[5px] bg-white/80 overflow-auto scrollbar-webkit scrollbar-thin">
        <div class="flex flex-col items-center shrink-0 self-stretch gap-6">
            <div class="flex items-start self-stretch sticky top-0 z-10 bg-white">
                <span class="text-primary-950 text-center text-md font-medium">Latest Notification</span>
            </div>
            <div class="flex flex-col items-center justify-center gap-6 self-stretch">
                <template v-if="props.notificationLength > 0">
                    <!-- Inventory -->
                    <div class="flex flex-col items-end gap-[13px] self-stretch" v-if="Object.keys(props.inventory_notifications).length > 0">
                        <div class="flex items-center gap-2 self-stretch">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M7.17479 5.57388L15.141 9.27247L13.0105 10.2185C12.4042 10.4972 11.5874 10.4888 10.9895 10.2269L4.63165 7.34733C5.03586 6.75604 5.62535 6.26639 6.34953 5.94541L7.17479 5.57388ZM17.6505 5.94541L13.9536 4.29897C13.3379 4.02743 12.6726 3.88721 12 3.88721C11.3274 3.88721 10.6621 4.02743 10.0464 4.29897L8.70742 4.89003L16.6652 8.58036L19.3684 7.34753C18.9641 6.75604 18.3747 6.26627 17.6505 5.94541ZM4.10958 8.49565C4.03488 8.77657 3.99805 9.06626 4.00008 9.35698V14.4575C4.00008 15.9184 4.90113 17.2273 6.34953 17.8691L10.0548 19.5158C10.4709 19.7069 10.914 19.8322 11.3684 19.8872V11.6454C11.0615 11.5942 10.7619 11.5063 10.4758 11.3836L4.10958 8.49565ZM19.8904 8.49565L13.5242 11.3836C13.2381 11.5063 12.9385 11.5942 12.6316 11.6454V19.8872C13.0885 19.8311 13.5342 19.7058 13.9536 19.5157L17.6505 17.869C19.0989 17.2273 19.9999 15.9183 19.9999 14.4575V9.35698C20.0019 9.06626 19.9651 8.77657 19.8904 8.49565Z" fill="#9F151A"/>
                            </svg>
                            <span class="line-clamp-1 w-full overflow-hidden text-primary-900 text-ellipsis text-sm font-medium">Inventory</span>
                        </div>
                        <div class="flex flex-col items-start gap-[13px] self-stretch rounded-[5px] p-3 bg-white/80 hover:bg-[#fff1f280]" v-if="!showMoreInventory">
                            <span class="self-stretch text-grey-900 text-sm font-normal" v-if="firstInventory.type === 'InventoryOutOfStock'">Item 
                                <span class="text-grey-900 text0sn font-semibold">'{{ firstInventory.data.inventory_name }}'</span>
                                is almost running out of stock!
                            </span>
                            <span class="self-stretch text-grey-900 text-sm font-normal" v-else>Item 
                                <span class="text-grey-900 text0sn font-semibold">'{{ firstInventory.data.inventory_name }}'</span>
                                is out of stock.
                            </span>
                            <div class="flex p-3 items-center gap-4 self-stretch rounded-[5px] bg-gradient-to-b from-[#fff9f9ab] to-white">
                                <div class="flex flex-col justify-center items-start gap-2">
                                    <span class="text-primary-900 text-xs font-medium">Product affected:</span>
                                    <div class="flex items-start gap-3">
                                        <!-- product affected images -->
                                        <img 
                                            :src="firstInventory.data.image ? firstInventory.data.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="size-10" 
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start gap-[13px] self-stretch cursor-pointer" @click="openFoldedInventory" v-if="remainingInventory.length > 0">
                                <span class="text-primary-600 text-2xs font-medium">+{{ remainingInventory.length }} notification</span>
                            </div>
                        </div>

                        <!-- expand inventory notification -->
                        <div class="flex flex-col items-start gap-[13px] self-stretch rounded-[5px] p-3 bg-white/80 hover:bg-[#fff1f280]" 
                            v-for="inventories in props.inventory_notifications" v-if="showMoreInventory">
                            <span class="self-stretch text-grey-900 text-sm font-normal" v-if="firstInventory.type === 'InventoryOutOfStock'">Item 
                                <span class="text-grey-900 text0sn font-semibold">'{{ inventories.data.inventory_name }}'</span>
                                is almost running out of stock!
                            </span>
                            <span class="self-stretch text-grey-900 text-sm font-normal" v-else>Item 
                                <span class="text-grey-900 text0sn font-semibold">'{{ inventories.data.inventory_name }}'</span>
                                is out of stock.
                            </span>
                            <div class="flex p-3 items-center gap-4 self-stretch rounded-[5px] bg-gradient-to-b from-[#fff9f9ab] to-white/[.67]">
                                <div class="flex flex-col justify-center items-start gap-2">
                                    <span class="text-primary-900 text-xs font-medium">Product affected:</span>
                                    <div class="flex items-start gap-3">
                                        <!-- product affected images -->
                                        <img 
                                            :src="inventories.data.image ? inventories.data.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="size-10" 
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center self-stretch">
                                <Link class="flex items-center gap-1" :href="route('inventory')">
                                    <span class="text-primary-900 text-xs font-medium">View Stock</span>
                                    <CircledArrowHeadRightIcon class="text-primary-900 size-4" />
                                </Link>
                                <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(inventories.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Waiter Check in / out -->
                    <div class="flex flex-col items-end gap-[13px] self-stretch" v-if="Object.keys(props.waiter_notifications).length > 0">
                        <div class="flex items-center gap-2 self-stretch">
                            <WaiterIcon class="text-primary-800" />
                            <span class="line-clamp-1 w-full overflow-hidden text-primary-900 text-ellipsis text-sm font-medium">Waiter Check in / out</span>
                        </div>
                        <div class="flex flex-col items-end gap-[13px] self-stretch rounded-[5px] bg-[#fff9f980]" v-if="!showMoreWaiter">
                            <div class="flex flex-col p-3 items-end gap-[13px] self-stretch">
                                <div class="flex justify-end items-start gap-[13px] self-stretch">
                                    <img 
                                        :src="firstWaiter.data.image ? firstWaiter.data.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt=""
                                        class="size-9" 
                                    >
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <template v-if="firstWaiter.type === 'WaiterCheckIn'">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="bg-green-600 size-2 rounded-full"></span>
                                                <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis textxs font-medium">{{ firstWaiter.data.waiter_name }}</span>
                                            </div>
                                            <span class="self-stretch text-grey-900 text-sm font-normal">
                                                Checked-in at <span class="self-stretch text-grey-900 text-sm font-semibold">{{ firstWaiter.data.checked_in }}</span>
                                            </span>
                                        </template>
                                        <template v-else>
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="bg-primary-600 size-2 rounded-full"></span>
                                                <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis textxs font-medium">{{ firstWaiter.data.waiter_name }}</span>
                                            </div>
                                            <span class="self-stretch text-grey-900 text-sm font-normal">
                                                Checked-out at <span class="self-stretch text-grey-900 text-sm font-semibold">{{ firstWaiter.data.checked_out }}</span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-start gap-[13px] self-stretch cursor-pointer" @click="openFoldedWaiter" v-if="remainingWaiter.length > 0">
                                    <span class="text-primary-600 text-2xs font-medium">+{{ remainingWaiter.length }} notification</span>
                                </div>
                            </div>
                        </div>

                        <!-- expand waiter notification -->
                        <div class="flex flex-col items-end gap-[13px] self-stretch rounded-[5px] bg-[#fff9f980]" v-for="waiters in props.waiter_notifications" v-if="showMoreWaiter">
                            <div class="flex flex-col items-end gap-[13px] self-stretch">
                                <div class="flex flex-col p-3 items-end gap-[13px] self-stretch">
                                    <div class="flex justify-end items-start gap-[13px] self-stretch">
                                        <img 
                                            :src="waiters.data.image ? waiters.data.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="size-9" 
                                        >
                                        <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                            <template v-if="waiters.type === 'WaiterCheckIn'">
                                                <div class="flex items-center gap-1 self-stretch">
                                                    <span class="bg-green-600 size-2 rounded-full"></span>
                                                    <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis textxs font-medium">{{ waiters.data.waiter_name }}</span>
                                                </div>
                                                <span class="self-stretch text-grey-900 text-sm font-normal">
                                                    Checked-in at <span class="self-stretch text-grey-900 text-sm font-semibold">{{ waiters.data.checked_in }}</span>
                                                </span>
                                            </template>
                                            <template v-else>
                                                <div class="flex items-center gap-1 self-stretch">
                                                    <span class="bg-primary-600 size-2 rounded-full"></span>
                                                    <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis textxs font-medium">{{ waiters.data.waiter_name }}</span>
                                                </div>
                                                <span class="self-stretch text-grey-900 text-sm font-normal">
                                                    Checked-out at <span class="self-stretch text-grey-900 text-sm font-semibold">{{ waiters.data.checked_out }}</span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-center gap-[13px] self-stretch">
                                        <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(waiters.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table / Room -->
                    <div class="flex flex-col items-end gap-[13px] self-stretch" v-if="Object.keys(props.order_notifications).length > 0">
                        <div class="flex items-center gap-2 self-stretch">
                            <TableRoomIcon class="text-primary-900" />
                            <span class="line-clamp-1 text-primary-900 text-ellipsis text-sm font-medium">Table / Room Activity</span>
                        </div>
                        <div class="flex flex-col items-end gap-[13px] self-stretch rounded-[5px] bg-white hover:bg-[#fff1f280] p-3" v-if="!showMoreOrder">
                            <div class="flex items-end gap-[13px] self-stretch"  v-if="firstOrder.type === 'OrderPlaced'">
                                <div class="flex justify-start items-start gap-[13px] self-stretch size-9">
                                    <img :src="firstOrder.data.waiter_image ? firstOrder.data.waiter_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                        alt=""
                                        class="w-full h-full rounded-full"
                                        v-if="firstOrder.type === 'OrderPlaced'"
                                    />
                                    <div class="flex relative size-9" v-else>
                                        <img 
                                            :src="firstOrder.data.image ? firstOrder.data.image
                                                                        : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <div class="flex justify-center items-center border border-solid border-white bg-primary-800 
                                                    w-6 h-6 rounded-full absolute right-0 bottom-0"
                                        >
                                            <span class="text-primary-25 text-2xs font-medium">
                                                {{ firstOrder.data.table_no.split(',')[0].trim() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]" v-if="firstOrder.type === 'OrderPlaced'">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <div class="size-2 rounded-full bg-green-600"></div>
                                        <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-primary-900 text-xs font-medium">
                                            {{ firstOrder.type !== 'CheckInCustomer' ? firstOrder.data.waiter_name : firstOrder.data.table_no }}
                                        </span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal" v-if="firstOrder.type === 'OrderPlaced'">
                                        placed an order for <span class="text-grey-900 text-sm font-semibold">{{ firstOrder.data.table_no }}</span>.
                                    </span>
                                    <span class="text-grey-900 text-sm font-normal" v-else>
                                        New customer check-in by <span class="text-grey-900 text-sm font-semibold">{{ firstOrder.data.waiter_name }}</span>.
                                    </span>
                                    
                                </div>
                            </div>
                            <div class="flex items-end gap-[13px] self-stretch"  v-if="firstOrder.type === 'OrderCheckInCustomer'">
                                <div class="flex justify-start items-start gap-[13px] self-stretch size-9">
                                    <div class="flex relative size-9">
                                        <img 
                                            :src="firstOrder.data.waiter_image ? firstOrder.data.waiter_image
                                                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <div class="flex justify-center items-center border border-solid border-white bg-primary-800 
                                                    w-6 h-6 rounded-full absolute right-0 bottom-0"
                                        >
                                            <span class="text-primary-25 text-2xs font-medium">
                                                {{ firstOrder.data.table_no.split(',')[0].trim() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-primary-900 text-xs font-medium">
                                            {{ firstOrder.data.table_no }}
                                        </span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal">New customer check-in by 
                                        <span class="text-grey-900 text-sm font-semibold">{{ firstOrder.data.waiter_name }}.</span>
                                    </span>
                                    
                                </div>
                            </div>
                            <div class="flex items-end gap-[13px] self-stretch"  v-if="firstOrder.type === 'OrderAssigned'">
                                <div class="flex justify-start items-start gap-[13px] self-stretch size-9">
                                    <div class="flex relative size-9">
                                        <img 
                                            :src="firstOrder.data.assigner_image ? firstOrder.data.assigner_image
                                                                        : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <img 
                                            :src="firstOrder.data.waiter_image ? firstOrder.data.waiter_image
                                                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full right-0 bottom-0 absolute"
                                        >
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-xs font-medium text-primary-900">{{ firstOrder.data.assigner_name }}</span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal">Assigned
                                        <span class="text-grey-900 text-sm font-semibold">{{ firstOrder.data.waiter_name }}</span>
                                        to serve
                                        <span class="text-grey-900 text-sm font-semibold">{{ firstOrder.data.table_no }}.</span>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-start gap-[13px] self-stretch cursor-pointer" @click="openFoldedOrder" v-if="remainingOrder.length > 0">
                                <span class="text-primary-600 text-2xs font-medium">+{{ remainingOrder.length }} notification</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-[13px] self-stretch rounded-[5px] bg-white hover:bg-[#fff1f280] p-3" v-for="orders in props.order_notifications" v-if="showMoreOrder">
                            <div class="flex items-end gap-[13px] self-stretch"  v-if="orders.type === 'OrderPlaced'">
                                <div class="flex justify-start items-start gap-[13px] self-stretch size-9">
                                    <img :src="orders.data.waiter_image ? orders.data.waiter_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                        alt=""
                                        class="w-full h-full rounded-full"
                                        v-if="orders.type === 'OrderPlaced'"
                                    />
                                    <div class="flex relative size-9" v-else>
                                        <img 
                                            :src="orders.data.image ? orders.data.image
                                                                        : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <div class="flex justify-center items-center border border-solid border-white bg-primary-800 
                                                    w-6 h-6 rounded-full absolute right-0 bottom-0"
                                        >
                                            <span class="text-primary-25 text-2xs font-medium">
                                                {{ orders.data.table_no.split(',')[0].trim() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]" v-if="orders.type === 'OrderPlaced'">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <div class="size-2 rounded-full bg-green-600"></div>
                                        <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-primary-900 text-xs font-medium">
                                            {{ orders.type !== 'CheckInCustomer' ? orders.data.waiter_name : orders.data.table_no }}
                                        </span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal" v-if="orders.type === 'OrderPlaced'">
                                        placed an order for <span class="text-grey-900 text-sm font-semibold">{{ orders.data.table_no }}</span>.
                                    </span>
                                    <span class="text-grey-900 text-sm font-normal" v-else>
                                        New customer check-in by <span class="text-grey-900 text-sm font-semibold">{{ orders.data.waiter_name }}</span>.
                                    </span>
                                    
                                </div>
                            </div>
                            <div class="flex items-end gap-[13px] self-stretch"  v-if="orders.type === 'OrderCheckInCustomer'">
                                <div class="flex justify-start items-start gap-[13px] self-stretch size-9">
                                    <div class="flex relative size-9">
                                        <img 
                                            :src="orders.data.waiter_image ? orders.data.waiter_image
                                                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <div class="flex justify-center items-center border border-solid border-white bg-primary-800 
                                                    w-6 h-6 rounded-full absolute right-0 bottom-0"
                                        >
                                            <span class="text-primary-25 text-2xs font-medium">
                                                {{ orders.data.table_no.split(',')[0].trim() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-primary-900 text-xs font-medium">
                                            {{ orders.data.table_no }}
                                        </span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal">New customer check-in by 
                                        <span class="text-grey-900 text-sm font-semibold">{{ orders.data.waiter_name }}.</span>
                                    </span>
                                    
                                </div>
                            </div>
                            <div class="flex items-end gap-[13px] self-stretch"  v-if="orders.type === 'OrderAssigned'">
                                <div class="flex justify-start items-start gap-[13px] self-stretch size-9">
                                    <div class="flex relative size-9">
                                        <img 
                                            :src="orders.data.assigner_image ? orders.data.assigner_image
                                                                        : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <img 
                                            :src="orders.data.waiter_image ? orders.data.waiter_image
                                                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-6 h-6 rounded-full right-0 bottom-0 absolute"
                                        >
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-xs font-medium text-primary-900">{{ orders.data.assigner_name }}</span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal">Assigned
                                        <span class="text-grey-900 text-sm font-semibold">{{ orders.data.waiter_name }}</span>
                                        to serve
                                        <span class="text-grey-900 text-sm font-semibold">{{ orders.data.table_no }}.</span>
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-end items-center gap-[13px] self-stretch">
                                <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(orders.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="flex flex-col justify-center items-center gap-[13px] shrink-0 self-stretch w-80 min-h-[calc(100dvh-32.5rem)]" v-else>
                    <UndrawFreshIllust />
                    <span class="text-primary-900 text-center text-sm font-medium">No new notification yet...</span>
                </div>
            </div>


                <!-- View all notification -->
                <div class="flex flex-col items-center">
                    <Button
                        :variant="'secondary'"
                        :type="'button'"
                        :size="'lg'"
                        :href="route('notifications')"
                        class="absolute bottom-0"
                    >
                        View all notification  <span v-if="all_notifications > 0"> ({{ props.all_notifications }})</span>
                    </Button>
                </div>
        </div>
    </div>  
</template>

