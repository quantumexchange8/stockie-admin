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
import { usePhoneUtils } from '@/Composables';

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

const { formatPhone } = usePhoneUtils();

const getTimeDifference = (date) => {
    const createdDate = new Date(date);
    const now = new Date();
    
    let months = now.getMonth() - createdDate.getMonth() + (12 * (now.getFullYear() - createdDate.getFullYear()));
    let days = now.getDate() - createdDate.getDate();
    let hours = now.getHours() - createdDate.getHours();

    // Adjust for negative days
    if (days < 0) {
        months -= 1;
        const previousMonth = new Date(now.getFullYear(), now.getMonth(), 0);
        days += previousMonth.getDate();
    }

    if (months > 0) {
        return `${months} m`;
    } else if (days > 0) {
        return `${days} d`;
    } else if (hours > 0) {
        return `${hours} h`;
    }
};

</script>

<template>
    <div class="flex flex-col w-full p-6 gap-6 items-center rounded-[5px] border border-primary-100">
        <div class="flex items-center justify-between w-full">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap">Recent Keep History</span>
            <Link :href="route('activeKeptItem')">
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
                class="[&>div>div>table>tbody>tr>td]:px-3 [&>div>div>table>tbody>tr>td]:py-2"
            >
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #keep_date="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.keep_date).format('DD/MM/YYYY') }}</span>
                </template>
                <template #keep_item.customer.full_name="row">
                    <div class="flex items-center gap-2">
                        <img 
                            :src="row.customer_image ? row.customer_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="CustomerImage"
                            class="size-9 object-contain rounded-full"
                        >
                        <div class="flex flex-col justify-center items-start flex-[1_0_0]">
                            <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-semibold">{{ row.keep_item.customer.full_name }}</span>
                            <span class="self-stretch text-grey-900 text-sm font-normal line-clamp-1">{{ formatPhone(row.keep_item.customer.phone) }}</span>
                        </div>
                    </div>
                </template>
                <template #item_name="row">
                    <span class="line-clamp-1 flex-[1_0_0] text-grey-900 text-sm font-semibold text-ellipsis">{{ row.item_name }}</span>
                </template>
                <template #qty="row">
                    <span class="text-grey-900 text-sm font-medium">{{ parseFloat(row.qty) > parseFloat(row.cm) ? `x ${row.qty}` : `${row.cm} cm` }}</span>
                </template>
                <template #keep_item.expired_to="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.keep_item.expired_to ? getTimeDifference(row.keep_item.expired_to) : '-' }}</span>
                </template>
            </Table>
            
            <div class="flex flex-col items-center justify-center" v-else>
                <UndetectableIllus class="w-56 h-56" />
                <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
            </div>
        </div>
    </div>
</template>
