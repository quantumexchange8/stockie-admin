<script setup>
import { ref, watch } from 'vue';
import { EditIcon, DeleteIcon, PointsBoxIcon } from '@/Components/Icons/solid.jsx';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';
import EditPointForm from './EditPointForm.vue';

const props = defineProps({
    errors: Object,
    redeemableItem: {
        type: Object,
        default: () => {},
    },
    columns: Array,
    rows: Array,
    rowType: Object,
    actions: Object,
    totalPages: Number,
    rowsPerPage: Number,
    inventoryItems: {
        type: Array,
        default: () => [],
    },
})
const editPointFormIsOpen = ref(false);
const deletePointFormIsOpen = ref(false);

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showEditPointForm = (event, tier) => {
    handleDefaultClick(event);
    editPointFormIsOpen.value = true;
}

const hideEditPointForm = () => {
    editPointFormIsOpen.value = false;
}

const showDeletePointForm = (event, id) => {
    handleDefaultClick(event);
    deletePointFormIsOpen.value = true;
}

const hideDeletePointForm = () => {
    deletePointFormIsOpen.value = false;
}

</script>

<template>
    <div class="col-span-full lg:col-span-4 flex flex-col p-6 gap-6 items-start rounded-[5px] border border-red-100">
        <div class="flex items-center justify-between w-full">
            <span class="w-full text-start text-md font-medium text-primary-900 whitespace-nowrap">Item Detail</span>
            <div class="flex flex-nowrap gap-2">
                <EditIcon 
                    class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="showEditPointForm"
                />
                <DeleteIcon 
                    class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                    @click="showDeletePointForm"
                />
            </div>
        </div>

        <!-- <div class="w-full h-72 bg-primary-50 border border-grey-100 rounded-md"></div> -->
        <img 
            :src="redeemableItem.image ? redeemableItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
            alt="" 
            class="w-full h-72 bg-primary-50 border border-grey-100 rounded-md"
        />

        <span class="text-md text-grey-900 font-medium">{{ redeemableItem.full_name ?? redeemableItem.name }}</span>

        <div class="flex flex-col gap-4 items-start self-stretch">
            <div class="flex justify-start items-center w-full p-3 gap-4 border border-primary-100 bg-white rounded-[5px]">
                <div class="bg-primary-50 rounded-[5px] flex items-center justify-center p-2">
                    <PointsBoxIcon class="size-6 text-primary-900"/>
                </div>
                <span class="text-base text-grey-900 font-medium">
                    Redeem with <span class="text-primary-900">{{ redeemableItem.point ?? '' }} pts</span>
                </span>
            </div>
            <div class="flex items-start w-full rounded-[5px]">
                <div class="w-full">
                    <Table 
                        :variant="'list'"
                        :rows="rows"
                        :columns="columns"
                        :rowType="rowType"
                        :paginator="false"
                        class="w-full"
                        minWidth="min-w-64"
                    >
                        <template #item="row">
                            <span class="text-grey-900 text-sm font-medium">{{ row.item_qty + ' x ' + row.inventory_item.item_name}}</span>
                        </template>
                        <template #inventory_item.stock_qty="row">
                            <span class="text-primary-700 text-base font-medium">{{ row.inventory_item.stock_qty }}</span>
                        </template>
                    </Table>    
                </div>
            </div>
        </div>
    </div>
    <Modal 
        :title="'Edit Redeemable Item'"
        :show="editPointFormIsOpen" 
        :maxWidth="'lg'" 
        @close="hideEditPointForm"
    >
        <template v-if="redeemableItem">
            <EditPointForm
                :point="redeemableItem"
                :inventoriesArr="inventoryItems" 
                @close="hideEditPointForm"
            />
        </template>
    </Modal>
    <Modal 
        :show="deletePointFormIsOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="actions.delete(redeemableItem.id)"
        :confirmationTitle="'Delete this item?'"
        :confirmationMessage="'Are you sure you want to delete the selected redeemable item? This action cannot be undone.'"
        @close="hideDeletePointForm"
        v-if="redeemableItem.id"
    />
</template>
