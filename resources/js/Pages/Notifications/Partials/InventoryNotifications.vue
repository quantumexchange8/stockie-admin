<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DateInput from '@/Components/Date.vue';
import { EmptyIllus, UndrawFreshIllust } from '@/Components/Icons/illus';
import { CircledArrowHeadRightIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

// const props = defineProps({
//     notifications: Object,
// })
const originalData = ref([]);
const notifications = ref([]);
// const tabs = ref(['Beer', 'Wine', 'Liquor', 'Others'])
const date_filter = ref('');
const searchQuery = ref('');
const checkedFilters = ref({
    date: '',
    category: [],
})

const getNotification = async (filters = []) => {
    try {
        const response = await axios.get('/notifications/filterNotification', {
            method: 'GET',
            params: {
                checkedFilters: filters,
            }
        });
        originalData.value = response.data;
        notifications.value = response.data;

    } catch (error) {
        console.error(error);
    }
}

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

    if (checkedFilters.value.category.length > 0) {
        notifications.value = notifications.value.filter(notification => {
            return checkedFilters.value.category.some(keyword => notification.data.categories.includes(keyword));
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

onMounted(() => {
    getNotification({ category: ['Inventory'] });
});

watch(() => date_filter.value, () => toggleDateFilter(date_filter.value));

watch(
    () => searchQuery.value,
    (newValue) => {
        if (newValue === '') {
            notifications.value = [...originalData.value];
            return;
        }

        const query = newValue.toLowerCase();

        notifications.value = originalData.value.filter(notification => {
            const notificationType = notification.type?.toLowerCase() || '';
            const categories = notification.data.categories?.map(category => category.toLowerCase()).join(' ') || '';
            const inventoryName = notification.data.inventory_name?.toLowerCase() || '';

            return (
                notificationType.includes(query) ||
                categories.includes(query) ||
                inventoryName.includes(query)
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
            :placeholder="$t('public.search')"
            v-model="searchQuery"
        >
            <template #default="{ hideOverlay }">
                <div class="flex flex-col self-stretch gap-6 items-center">
                    <div class="flex flex-col items-center gap-4 self-stretch">
                        <span class="text-grey-900 text-base font-semibold self-stretch">{{ $t('public.date') }}</span>
                        <DateInput
                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                            :range="true"
                            :inputName="'date_filter'"
                            v-model="checkedFilters.date"
                            @change="toggleDateFilter"
                        />
                    </div>

                    <!-- <div class="flex flex-col items-center gap-4 self-stretch">
                        <span class="text-grey-900 text-base font-semibold self-stretch">{{ $t('public.category') }}</span>
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
                    </div> -->

                </div>
                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="clearFilters(hideOverlay)"
                    >
                        {{ $t('public.action.clear_all') }}
                    </Button>

                    <Button
                        :size="'lg'"
                        @click="applyCheckedFilters(hideOverlay)"
                    >
                        {{ $t('public.action.apply') }}
                    </Button>
                </div>
            </template>
        </SearchBar>

        <div class="flex flex-col w-full items-start flex-[1_0_0 self-stretch] divide-y divide-grey-100" v-if="originalData">
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
                                                    {{ $t('public.item') }} 
                                                    '<span class="text-grey-900 text-sm font-semibold">{{ notification.data.inventory_name }}</span>' 
                                                    {{ $t('public.almost_no_stock') }}
                                                </template>
                                                <template v-else>
                                                    {{ $t('public.item') }} 
                                                    '<span class="text-grey-900 text-sm font-semibold">{{ notification.data.inventory_name }}</span>' 
                                                    {{ $t('public.is_no_stock') }}
                                                </template>
                                            </span>
                                            <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(notification.created_at) }}</span>
                                        </div>
                                        <div class="flex p-3 justify-between items-center self-stretch rounded-[5px] bg-gradient-to-b from-[#fff9f9ab] to-white/[.67]">
                                            <div class="flex flex-col justify-center items-start gap-2">
                                                <span class="text-primary-900 text-xs font-medium">{{ $t('public.product_affected') }}</span>
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
                                            <div class="flex items-center gap-1 group">
                                                <Link class="flex items-center gap-1" :href="route('inventory')">
                                                    <span class="text-primary-900 text-xs font-medium group-hover:text-primary-700">{{ $t('public.view_stock') }}</span>
                                                    <CircledArrowHeadRightIcon class="text-primary-900 size-4 group-hover:text-primary-700" />
                                                </Link>
                                            </div>
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
                <span class="text-primary-900 text-center text-sm font-medium">{{ $t('public.empty.no_notification') }}</span>
            </div>
            <div class="flex flex-col justify-center items-center gap-5" v-else>
                <EmptyIllus />
                <span class="text-primary-900 text-center text-sm font-medium">{{ $t('public.empty.no_result_found') }}</span>
            </div>
        </div>
    </div>        
</template>

