<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3';
import { CircledArrowHeadRightIcon2 } from '@/Components/Icons/solid';
import { UndetectableIllus } from '@/Components/Icons/illus';
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue'
import Table from '@/Components/Table.vue'
import Button from '@/Components/Button.vue'

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
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
})

const selectedGroup = ref(null);

const handleLinkClick = (event) => {
    event.stopPropagation();  // Prevent the row selection event
    event.preventDefault();   // Prevent the default link action
    window.location.href = event.currentTarget.href;  // Manually handle the link navigation
};

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
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :rowType="rowType"
                :actions="actions"
            >
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #item_cat_id="row">
                    {{ itemCategoryArr.find((category) => category.value === row.item_cat_id).text }}
                </template>
                <template #keep="row">
                    {{ row.keep ? row.keep : 0 }}
                </template>
                <template #status="row">
                    <Tag
                        :variant="'green'"
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
