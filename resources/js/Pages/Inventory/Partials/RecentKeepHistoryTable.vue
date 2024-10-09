<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3';
import { CircledArrowHeadRightIcon2 } from '@/Components/Icons/solid';
import { UndetectableIllus } from '@/Components/Icons/illus';
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue'
import Table from '@/Components/Table.vue'
import Button from '@/Components/Button.vue'
import dayjs from 'dayjs';

const props = defineProps({
    errors: Object,
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    itemCategoryArr: {
        type: Array,
        default: () => [],
    },
    rowType: {
        type: Object,
        required: true,
    },
    actions: {
        type: Object,
        default: () => {},
    },
})

</script>

<template>
    <div class="flex flex-col w-full p-6 gap-6 items-center rounded-[5px] border border-primary-100">
        <div class="flex items-center justify-between w-full">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap">Recent Keep History</span>
            <Link :href="route('inventory.viewKeepHistories')">
                <CircledArrowHeadRightIcon2  
                    class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                />
            </Link>
        </div>
        <div class="flex flex-col gap-4 justify-center overflow-y-auto w-full">
            <Table 
                v-if="rows.length > 0"
                :variant="'list'"
                :rows="rows"
                :columns="columns"
                :rowType="rowType"
                :actions="actions"
                :paginator="false"
                minWidth="min-w-[544px]"
            >
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #quantity="row">
                    {{ parseFloat(row.qty) > parseFloat(row.cm) ? `x ${row.qty}` : `${row.cm} cm` }}
                </template>
                <template #keep_date="row">
                    {{ dayjs(row.keep_date).format('DD/MM/YYYY') }}
                </template>
                <template #keep_for="row">
                    <Link :href="route('customer')" class="line-clamp-1 underline text-ellipsis text-sm font-semibold text-primary-900 hover:text-primary-700">
                        {{ row.keep_item?.customer?.full_name ?? 0 }}
                    </Link>
                </template>
            </Table>
            
            <div class="flex flex-col items-center justify-center" v-else>
                <UndetectableIllus class="w-56 h-56" />
                <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
            </div>
        </div>
    </div>
</template>
