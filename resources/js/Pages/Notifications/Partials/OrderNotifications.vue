<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DateInput from '@/Components/Date.vue';
import { EmptyIllus, UndrawFreshIllust } from '@/Components/Icons/illus';
import SearchBar from '@/Components/SearchBar.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    notifications: Object,
})
const notifications = ref(props.notifications);
const originalData = ref(props.notifications);
const tabs = ref(['Customer check-in', 'Place order', 'Waiter Assignment'])
const date_filter = ref('');
const searchQuery = ref('');
const checkedFilters = ref({
    date: '',
    status: [],
})

const toggleDateFilter = (date) => {
    checkedFilters.value.date.push(date)
}

const toggleStatus = (status) => {
    const index = checkedFilters.value.status.indexOf(status);
    if (index === -1){
        checkedFilters.value.status.push(status);
    } else {
        checkedFilters.value.status.splice(index, 1);
    }
}

const resetFilters = () => {
    return {
        date: '',
        status: [],
    }
}

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    notifications.value = originalData.value;
    close();
};

const applyCheckedFilters = (close) => {
    // Reset data before filtering
    notifications.value = originalData.value;

    if(checkedFilters.value.date.length > 0){
        // Single date filter
        if (checkedFilters.value.date[1] === null) {
            const selectedDate = new Date(checkedFilters.value.date[0]).toLocaleDateString('en-US'); 
            // Filter notifications by created_at date
            notifications.value = notifications.value.filter(notification => {
                const notificationDate = new Date(notification.created_at).toLocaleDateString('en-US'); 
                return notificationDate === selectedDate;
            });
    
        } else {
            const startDate = new Date(checkedFilters.value.date[0]).toLocaleDateString('en-US'); 
            const endDate = new Date(checkedFilters.value.date[1]).toLocaleDateString('en-US'); 
    
            notifications.value = notifications.value.filter(notification => {
                const notificationDate = new Date(notification.created_at).toLocaleDateString('en-US'); 
                return startDate <= notificationDate && notificationDate <= endDate;
            });
    
        }
    }

    if (checkedFilters.value.status.length > 0) {
        const statusKeywords = checkedFilters.value.status.map(status => {
            switch (status) {
                case 'Customer check-in':
                    return 'CheckInCustomer';
                case 'Place order':
                    return 'Placed';
                case 'Waiter Assignment':
                    return 'Assigned';
                default:
                    return null; // Handle unexpected status values
            }
        }).filter(keyword => keyword !== null);

        // Filter notifications for matching status keywords
        notifications.value = notifications.value.filter(notification => {
            return statusKeywords.some(keyword => notification.type.includes(keyword));
        });
    }

    close();
};

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
            const waiterName = notification.data.waiter_name?.toLowerCase() || '';
            const tableNo = notification.data.table_no?.toString().toLowerCase() || '';
            const assignerName = notification.data.assigner_name?.toLowerCase() || '';

            return (
                notificationType.includes(query) ||
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
                                    :checked="checkedFilters.status.includes(tab)"
                                    @click="toggleStatus(tab)"
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
            <div class="flex flex-col justify-center items-center gap-5" v-if="!checkedFilters.status.length && !checkedFilters.date && !searchQuery">
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

