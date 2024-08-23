<script setup>
import Button from '@/Components/Button.vue';
import { UploadIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
    dateFilter: Array,
    order: Array,
})

const defaultLatest7Days = computed(() => {
    let currentDate = dayjs();
    let last7Days = currentDate.subtract(7, 'day');

    return [last7Days.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatest7Days.value);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});


</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Daily Sales Report</span>
            <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton
                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
                        Export
                        <UploadIcon class="size-4 cursor-pointer" />
                    </MenuButton>
                </div>

                <transition 
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0" 
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100" 
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <MenuItems
                        class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg"
                        >
                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="saleHistories.length === 0" @click="exportToCSV">
                            CSV
                        </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            // { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="saleHistories.length === 0">
                            PDF
                        </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
            <div class="w-full flex gap-5 flex-wrap sm:flex-nowrap">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="false"
                    v-model="filters['global'].value"
                />
                <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="col-span-full md:col-span-5"
                v-model="date_filter"
            />
            </div>
        <div class="" v-if="!dateFilter">
            asd
        </div>

        <div class="" v-if="dateFilter">
            das
        </div>
    </div>
</template>
