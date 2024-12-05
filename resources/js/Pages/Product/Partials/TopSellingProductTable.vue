<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import Tag from '@/Components/Tag.vue';
import Table from '@/Components/Table.vue'

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
            <span class="w-full text-start text-md font-medium text-primary-900 whitespace-nowrap">Top Selling Product</span>
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
                minWidth="min-w-[560px]"
            >
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #product="row">
                    <div class="flex flex-nowrap items-start gap-4">
                        <img class="bg-grey-50 border border-grey-200 h-10 w-10 object-contain" 
                            :src="row.image ? row.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                            alt=""
                        />
                        <div class="flex flex-col flex-nowrap items-start justify-center gap-1">
                            <span class="text-grey-900 text-sm font-medium overflow-hidden text-ellipsis">{{ row.product_name }}</span>
                            <span class="text-grey-500 text-xs font-medium">RM {{ row.price }}</span>
                        </div>
                    </div>
                </template>
                <template #category="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row['category']['name'] }}</span>
                </template>
                <template #sold="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.totalProductSaleQty }}</span>
                </template>
                <template #status="row">
                    <Tag
                        :variant="row.status === 'Out of stock' 
                                        ? 'red' 
                                        : row.status === 'Low in stock' 
                                            ? 'yellow' 
                                            : 'green'"
                        :value="row.status"
                    />
                </template>
            </Table>
            
            <div class="flex flex-col items-center justify-center" v-else>
                <UndetectableIllus class="w-56 h-56" />
                <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
            </div>
        </div>
    </div>
</template>
