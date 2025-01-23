<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DateInput from '@/Components/Date.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { DefaultIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';

const home = ref({
    label: 'Activity Logs',
});

const props = defineProps({
    logs: {
        type: Array,
        default: () => [],
    }
})

const logs = ref(props.logs);
const actions = ref(['Added', 'Updated', 'Deleted', 'Cancelled', 'Kept', 'Redeemed', 'Submitted', 'Refunded']);
const lastMonth = computed(() => {
    let currentDate = dayjs();
    let lastMonth = currentDate.subtract(1, 'month');

    return [lastMonth.toDate(), currentDate.toDate()];
});
const date_filter = ref(lastMonth.value);
const searchQuery = ref('');
const checkedFilters = ref({
    action: [],
})
const isLoading = ref(false);
const initialLogs = ref(props.logs);

const getLogs = async (filters = {}, checkedFilters = {}) => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('activity-logs.filter-logs'), {
            params: {
                date_filter: filters,
                checkedFilters: checkedFilters,
            }
        })
        logs.value = response.data;
        initialLogs.value = response.data;
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const toggleActions = (value) => {
    const index = checkedFilters.value.action.indexOf(value);
    if (index > -1 ) {
        checkedFilters.value.action.splice(index, 1);
    } else {
        checkedFilters.value.action.push(value);
    }
};

const resetFilters = () => {
    return {
        action: [],
    };
}

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    getLogs(date_filter.value, checkedFilters.value);
    close();
}

const applyCheckedFilters = (close) => {
    getLogs(date_filter.value, checkedFilters.value);
    close();
}

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        logs.value = [...initialLogs.value];
        return;
    }

    const query = newValue.toLowerCase();

    logs.value = initialLogs.value.filter(log => {
        const description = log.description.toLowerCase();
        const created_by = log.properties.created_by.toLowerCase();

        return description.includes(query) || created_by.includes(query);
    })
}, { immediate: true })

watch(() => date_filter.value, (newValue) => {
    getLogs(newValue, checkedFilters.value);
})
</script>

<template>
    <Head title="Activity Logs" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
            <span class="h-[25px] text-primary-900 text-md font-medium">Activity Logs</span>
            <div class="flex items-start gap-5 self-stretch">
                <SearchBar 
                    :showFilter="true"
                    :placeholder="'Search'"
                    v-model="searchQuery"
                >
            
                    <template #default="{ hideOverlay }">
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Action</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(action, index) in actions" 
                                    :key="index"
                                    class="flex py-2 px-[7px] gap-2 items-center border border-grey-100 rounded-[5px]"
                                >
                                    <Checkbox 
                                        :checked="checkedFilters.action.includes(action)"
                                        @click="toggleActions(action)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ action }}</span>
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

                <DateInput 
                    :range="true"
                    v-model="date_filter"
                />
            </div>
            <div class="flex flex-col items-start self-stretch divide-y divide-grey-100" v-if="logs.length > 0 && !isLoading">
                <template v-for="log in logs">
                    <div class="flex flex-col py-4 items-end gap-[13px] self-stretch">
                        <div class="flex justify-end items-start gap-[13px] self-stretch">
                            <img
                                :src="log.properties.image"
                                alt="AdminIcon"
                                class="size-9 rounded-full"
                                v-if="log.properties.image"
                            >
                            <DefaultIcon class="size-9 rounded-full" v-else />
                            <div class="flex flex-col justify-center items-start gap-4 flex-[1_0_0]">
                                <div class="flex flex-col items-start self-stretch">
                                    <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-bold">{{ log.properties.created_by }}</span>
                                    <span class="self-stretch text-grey-900 text-sm font-normal">{{ log.description }}</span>
                                </div>
                                <div class="flex items-start gap-1 self-stretch">
                                    <span class="text-grey-500 text-xs font-normal">{{ (log.event).charAt(0).toUpperCase() + (log.event).slice(1) }} on</span>
                                    <span class="text-grey-500 text-xs font-normal">{{ dayjs(log.created_at).format('DD/MM/YYYY, HH:mm:ss') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <template v-else>
                <div class="flex w-full flex-col items-center justify-center gap-5">
                    <UndetectableIllus />
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>        
</template>

