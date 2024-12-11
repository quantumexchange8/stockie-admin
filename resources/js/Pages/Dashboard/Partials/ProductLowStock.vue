<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { CircledArrowHeadRightIcon2 } from '@/Components/Icons/solid';
import Table from '@/Components/Table.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    rowType: Object,
})

</script>

<template>
    <div class="flex flex-col py-6 gap-6 items-end shrink-0 rounded-[5px] border border-solid border-primary-100">
        <div class="flex flex-col pl-3 items-start gap-[10px] self-stretch">
            <div class="flex pl-3 pr-6 justify-between items-center self-stretch">
                <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Product Low at Stock</span>
                <Link :href="route('products')">
                    <CircledArrowHeadRightIcon2  
                        class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                    />
                </Link>
            </div>
        </div>
        
        <template v-if="props.rows.length > 0">
            <Table
                :columns="columns"
                :rows="props.rows"
                :variant="'list'"
                :rowType="rowType"
                :paginator="false"
                class="px-6 min-h-[330px]"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #product_name="rows">
                    <div class="flex items-start gap-4 overflow-hidden">
                        <img 
                            :src="rows.image ? rows.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="ProductImage"
                            class="w-10 h-10 shrink-0"
                        />
                        <span class="self-center text-grey-900 overflow-hidden text-ellipsis whitespace-nowrap text-sm font-medium">{{ rows.product_name }}</span>                    
                    </div>
                </template>
                <template #category="rows">
                    <span class="text-grey-900 text-sm font-medium">{{ rows.category }}</span>
                </template>
                <template #item_name="rows">
                    <span class="text-grey-900 text-sm font-medium">{{ rows.item_name }}</span>
                </template>
                <template #stock_qty="rows">
                    <span class="text-primary-700 text-sm font-medium">{{ rows.stock_qty }}</span>
                </template>
            </Table>
        </template>
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </template>
    </div>

</template>

