<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DateInput from '@/Components/Date.vue';
import { EmptyIllus, UndrawFreshIllust } from '@/Components/Icons/illus';
import { CircledArrowHeadRightIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import { Link } from '@inertiajs/vue3';
import { FilterMatchMode } from 'primevue/api';
import { ref, watch } from 'vue';

const props = defineProps ({
    notifications: Object,
})
const tabs = ref(['Inventory', 'Waiter Check in / out', 'Table / Room Activity'])
const notifications = ref(props.notifications);
const date_filter = ref('');
const searchQuery = ref('');

const checkedFilters = ref({
    date: '',
    category: [],
})

const emit = defineEmits(["applyCheckedFilters"]);

const toggleDateFilter = (date) => {
    checkedFilters.value.date.push(date)
}

const toggleCategory = (category) => {
    const index = checkedFilters.value.category.indexOf(category);
    if (index === -1){
        checkedFilters.value.category.push(category);
    } else {
        checkedFilters.value.category.splice(index, 1);
    }
}

const resetFilters = () => {
    return {
        date: '',
        category: [],
    }
}

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const applyCheckedFilters = (close) => {
    emit('applyCheckedFilters', checkedFilters.value);
    close();
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
watch(() => date_filter.value, () => toggleDateFilter(date_filter.value));

watch(() => props.notifications, (newValue) => {
    notifications.value = newValue;
}, { immediate: true });

watch(
    () => searchQuery.value,
    (newValue) => {
        if (newValue === '') {
            notifications.value = [...props.notifications];
            return;
        }

        const query = newValue.toLowerCase();

        notifications.value = props.notifications.filter(notification => {
            const notificationType = notification.type?.toLowerCase() || '';
            const inventoryName = notification.data.inventory_name?.toLowerCase() || '';
            const waiterName = notification.data.waiter_name?.toLowerCase() || '';
            const tableNo = notification.data.table_no?.toString().toLowerCase() || '';
            const assignerName = notification.data.assigner_name?.toLowerCase() || '';

            return (
                notificationType.includes(query) ||
                inventoryName.includes(query) ||
                waiterName.includes(query) ||
                tableNo.includes(query) ||
                assignerName.includes(query)
            );
        });
    },
    { immediate: true }
);



</script>
<template>
    <div class="flex flex-col p-6 w-full items-start gap-4 rounded-[5px] border border-solid border-primary-100">
        <SearchBar
            :showFilter="true"
            :placeholder="'Search'"
            v-model="searchQuery"
        >
            <template #default="{ hideOverlay }">
                <div class="flex flex-col self-stretch gap-6 items-center">
                    <div class="flex flex-col items-center gap-4 self-stretch">
                        <span class="text-grey-900 text-base font-semibold self-stretch">Date</span>
                        <DateInput
                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                            :range="true"
                            :inputName="'date_filter'"
                            v-model="checkedFilters.date"
                            @change="toggleDateFilter"
                        />
                    </div>

                    <div class="flex flex-col items-center gap-4 self-stretch">
                        <span class="text-grey-900 text-base font-semibold self-stretch">Category</span>
                        <div class="flex justify-center items-start content-start gap-3 self-stretch flex-wrap">
                            <div v-for="(tab, index) in tabs" :key="index"
                                class="flex py-2 px-3 gap-2 items-center border border-white rounded-[5px]"
                            >
                                <Checkbox 
                                    :checked="checkedFilters.category.includes(tab)"
                                    @click="toggleCategory(tab)"
                                />
                                <span class="text-grey-700 text-sm font-medium">{{ tab }}</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="clearFilters(hideOverlay)"
                    >
                        Clear All
                    </Button>

                    <Button
                        :size="'lg'"
                        @click="applyCheckedFilters(hideOverlay)"
                    >
                        Apply
                    </Button>
                </div>
            </template>
        </SearchBar>

        <div class="flex flex-col w-full items-start flex-[1_0_0 self-stretch] divide-y divide-grey-100" v-if="props.notifications">
            <template v-for="notification in notifications">
                <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                    <template v-if="notification.type.includes('Inventory')">
                        <div class="flex flex-col items-end gap-[13px] self-stretch">
                            <div class="flex flex-col py-6 items-end gap-[13px] self-stretch">
                                <div class="flex justify-end items-center gap-[13px] self-stretch">
                                    <div class="flex flex-col items-end gap-[13px] flex-[1_0_0]">
                                        <div class="flex items-start justify-between gap-[13px] self-stretch">
                                            <span class="text-grey-900 text-sm font-normal">
                                                <template v-if="notification.type.includes('RunningOutOfStock')">
                                                    Item '<span class="text-grey-900 text-sm font-semibold">{{ notification.data.inventory_name }}</span>' is almost running out of stock!
                                                </template>
                                                <template v-else>
                                                    Item '<span class="text-grey-900 text-sm font-semibold">{{ notification.data.inventory_name }}</span>' is out of stock.
                                                </template>
                                            </span>
                                            <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(notification.created_at) }}</span>
                                        </div>
                                        <div class="flex p-3 justify-between items-center self-stretch rounded-[5px] bg-gradient-to-b from-[#fff9f9ab] to-white/[.67]">
                                            <div class="flex flex-col justify-center items-start gap-2">
                                                <span class="text-primary-900 text-xs font-medium">Product affected:</span>
                                                <div class="flex items-center gap-3">
                                                    <div v-for="images in notification.data.product_image">
                                                        <img 
                                                            :src="images ? images : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                            alt=""
                                                            class="size-10"
                                                        >
                                                    </div>
                                                    <div class="w-[1px] h-[34px] bg-primary-200" 
                                                        v-if="(notification.data.product_image && notification.data.product_image.length > 0) && 
                                                            (notification.data.redeem_item_image && notification.data.redeem_item_image.length > 0)"
                                                    >
                                                    </div>
                                                    <div v-for="images in notification.data.redeem_item_image">
                                                        <img 
                                                            :src="images ? images : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                            alt=""
                                                            class="size-10"
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <Link class="flex items-center gap-1" :href="route('inventory')">
                                                    <span class="text-primary-900 text-xs font-medium hover:text-primary-700">View Stock</span>
                                                    <CircledArrowHeadRightIcon class="text-primary-900 size-4 hover:text-primary-700" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-if="notification.type.includes('Order')">
                        <div class="flex flex-col items-start gap-[13px] self-stretch">
                            <div class="flex flex-col py-6 items-end gap-[13px] self-stretch">
                                <div class="flex justify-end items-center gap-[13px] self-stretch">
                                    <div class="flex flex-col items-end gap-[13px] flex-[1_0_0]">
                                        <div class="flex items-start gap-[13px] self-stretch" v-if="notification.type.includes('CheckInCustomer')">
                                            <div class="flex relative size-9">
                                                <img 
                                                    :src="notification.data.waiter_image ? notification.data.waiter_image
                                                                                        : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                    alt=""
                                                    class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                                >
                                                <div class="flex justify-center items-center border border-solid border-white bg-primary-800 
                                                            w-6 h-6 rounded-full absolute right-0 bottom-0"
                                                >
                                                    <span class="text-primary-25 text-2xs font-medium">
                                                        {{ notification.data.table_no.split(',')[0].trim() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                                <div class="flex items-center gap-1 self-stretch">
                                                    <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis text-xs font-medium">{{ notification.data.table_no }}</span>
                                                    <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(notification.created_at) }}</span>
                                                </div>
                                                <span class="text-grey-900 text-sm font-normal">New customer check-in by 
                                                    <span class="text-grey-900 text-sm font-semibold">{{ notification.data.waiter_name }}.</span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-[13px] self-stretch" v-if="notification.type.includes('Placed')">
                                            <div class="flex relative size-9">
                                                <img 
                                                    :src="notification.data.waiter_image ? notification.data.waiter_image
                                                                                        : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                                    alt=""
                                                    class="w-full h-full rounded-full"
                                                />
                                            </div>
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                                <div class="flex items-center gap-1 self-stretch">
                                                    <span class="size-2 bg-green-600 rounded-full"></span>
                                                    <span class="line-clamp-1 text-primary-900 text-ellipsis text-xs font-medium">{{ notification.data.waiter_name }}</span>
                                                </div>
                                                <span class="text-grey-900 text-sm font-normal">placed an order for
                                                    <span class="text-grey-900 text-sm font-semibold">{{ notification.data.table_no }}.</span>
                                                </span>
                                            </div>
                                            <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(notification.created_at) }}</span>
                                        </div>

                                        <div class="flex items-start gap-[13px] self-stretch" v-if="notification.type.includes('Assigned')">
                                            <div class="flex relative size-9">
                                                <img 
                                                    :src="notification.data.assigner_image ? notification.data.assigner_image
                                                                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                    alt=""
                                                    class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                                >
                                                <img 
                                                    :src="notification.data.waiter_image ? notification.data.waiter_image
                                                                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                    alt=""
                                                    class="w-6 h-6 rounded-full right-0 bottom-0 absolute border border-solid border-white"
                                                >
                                            </div>
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                                <div class="flex items-center gap-1 self-stretch">
                                                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-xs font-medium text-primary-900">{{ notification.data.assigner_name }}</span>
                                                </div>
                                                <span class="text-grey-900 text-sm font-normal">Assigned
                                                    <span class="text-grey-900 text-sm font-semibold">{{ notification.data.waiter_name }}</span>
                                                    to serve
                                                    <span class="text-grey-900 text-sm font-semibold">{{ notification.data.table_no }}.</span>
                                                </span>
                                            </div>
                                            <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(notification.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="flex justify-center items-center gap-2.5 flex-[1_0_0] self-stretch h-dvh" v-if="!notifications.length">
            <div class="flex flex-col justify-center items-center gap-5" v-if="!checkedFilters.category.length && !checkedFilters.date && !searchQuery">
                <UndrawFreshIllust />
                <span class="text-primary-900 text-center text-sm font-medium">No notification yet...</span>
            </div>
            <div class="flex flex-col justify-center items-center gap-5" v-else>
                <EmptyIllus />
                <span class="text-primary-900 text-center text-sm font-medium">We couldn't find any result...</span>
            </div>
        </div>
    </div>        
</template>

