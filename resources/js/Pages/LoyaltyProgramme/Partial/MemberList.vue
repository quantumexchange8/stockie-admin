<script setup>
import Table from "@/Components/Table.vue";
import { defineProps } from "vue";
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { transactionFormat, useFileExport } from "@/Composables";
import { UploadIcon } from "@/Components/Icons/solid";

const props = defineProps({
    id: String,
    columns: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    rowType: {
        type: Object,
        default: () => {},
    },
    actions: {
        type: Object,
        default: () => {},
    },
    totalPages: Number,
    rowsPerPage: Number,
    tierName: String,
});
const { formatAmount } = transactionFormat();
const { exportToCSV } = useFileExport();

const csvExport = () => {
    const tierName = props.tierName || 'Unknown tier';
    const mappedMemberList = props.rows.map(member => ({
        'Member name': member.full_name,
        'Joined on': member.joined_on,
        'Total spent': 'RM' + member.total_spending,
    }));
    exportToCSV(mappedMemberList, `${tierName}_Member List`);
}

</script>

<template>
    <div class="flex flex-col p-6 gap-6 rounded-[5px] border border-red-100 overflow-y-auto">
        <div class="flex gap-[10px]">
            <span class="w-full text-primary-900 text-md font-medium whitespace-nowrap">
                Member List ({{ rows.length }})
            </span>
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
                            { 'bg-grey-50 pointer-events-none': props.rows.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="props.rows.length === 0" @click="csvExport">
                            CSV
                        </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            // { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': props.rows.length === 0 },
                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="props.rows.length === 0">
                            PDF
                        </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
        <Table
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :actions="actions"
            :rowType="rowType"
            minWidth="min-w-[470px]"
        >
            <template #full_name="row">
                <div class="flex gap-[10px] items-center">
                    <!-- <div class="w-[32px] h-[32px] rounded-full bg-gray-500"></div> -->
                    <img 
                        :src="row.image ? row.image : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                        alt="" 
                        class="w-[32px] h-[32px] rounded-full object-contain"
                    />
                    <span class="shrink-0 text-grey-900 text-sm font-medium">{{ row.full_name }}</span>
                </div>
            </template>
            <template #created_at="row">
                <span class="text-grey-900 text-sm font-semibold"> {{ row.joined_on }}</span>
            </template>
            <template #total_spend="row">
                <span class="text-grey-900 text-sm font-semibold"> RM {{ row.total_spending }}</span>
            </template>
        </Table>
    </div>
</template>
