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
                <template #keep_date="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.keep_date).format('DD/MM/YYYY') }}</span>
                </template>
                <template #item_name="row">
                    <span class="line-clamp-1 flex-[1_0_0] text-grey-900 text-sm font-semibold text-ellipsis">{{ row.item_name }}</span>
                </template>
                <template #qty="row">
                    <span class="text-grey-900 text-sm font-medium">{{ parseFloat(row.qty) > parseFloat(row.cm) ? `x ${row.qty}` : `${row.cm} cm` }}</span>
                </template>
                <template #keep_item.expired_to="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.keep_item.expired_to ? dayjs(row.keep_item.expired_to).format('DD/MM/YYYY') : '-' }}</span>
                </template>
                <template #keep_item.customer.full_name="row">
                    <div class="flex items-center gap-2">
                        <img 
                            :src="rows.customer_image ? rows.customer_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="CustomerImage"
                            class="size-4 object-contain rounded-full"
                        >
                        <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis text-sm font-semibold underline underline-offset-auto decoration-solid decoration-auto">
                            {{ row.keep_item.customer.full_name }}
                        </span>
                    </div>
                </template>
            </Table>
            
            <div class="flex flex-col items-center justify-center" v-else>
                <UndetectableIllus class="w-56 h-56" />
                <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
            </div>
        </div>
    </div>
</template>
