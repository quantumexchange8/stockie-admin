<script setup>
import { ref, onMounted } from 'vue';
import Tag from '@/Components/Tag.vue';
import Button from '@/Components/Button.vue';
import AddOrderItems from './AddOrderItems.vue';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { DeleteIcon, PlusIcon } from '@/Components/Icons/solid.jsx';
import axios from 'axios';

const props = defineProps({
    errors: Object,
    selectedTable: {
        type: Object,
        default: () => {},
    },
})

const drawerIsVisible = ref(false);
const actionType = ref(null);
const categoryArr = ref([]);

const openDrawer = (action) => {
    actionType.value = action;

    if (!drawerIsVisible.value) {
        drawerIsVisible.value = true;
    }
};

const closeDrawer = () => {
    drawerIsVisible.value = false;
    actionType.value = null;
}

onMounted(async() => {
    try {
        const categoryResponse = await axios.get('/inventory/inventory/getAllCategories');
        categoryArr.value = categoryResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
});

</script>

<template>
    <RightDrawer 
        :header="actionType === 'keep' ? 'Keep Item' : `Order for ${selectedTable.table_no}`" 
        previousTab
        v-model:show="drawerIsVisible"
        @close="closeDrawer"
    >
        <template v-if="actionType === 'keep'">
        </template>

        <template v-if="actionType === 'add'">
            <AddOrderItems @close="closeDrawer" :categoryArr="categoryArr"/>
        </template>
    </RightDrawer>

    <div class="w-full flex flex-col gap-6 items-start rounded-[5px] pr-1 max-h-[calc(100dvh-23rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex gap-3 py-3 items-start justify-between self-stretch">
                <div class="flex flex-col gap-2 items-start">
                    <p class="text-grey-900 text-sm font-medium">Order No.</p>
                    <p class="text-grey-800 text-md font-semibold">#</p>
                </div>
                <div class="flex flex-col gap-2 items-start">
                    <p class="text-grey-900 text-sm font-medium">No. of pax</p>
                    <p class="text-grey-800 text-md font-semibold">{{ selectedTable.pax }}</p>
                </div>
                <div class="flex flex-col gap-2 items-start">
                    <p class="text-grey-900 text-sm font-medium">Ordered by</p>
                    <div class="size-6 bg-primary-100 rounded-full"></div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch">
                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">Pending Serve</p>
                        <DeleteIcon
                            class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="showDeleteReservationForm($event, row.id)"
                        />
                    </div>
                    <div class="flex justify-between items-center py-3" v-for="(item, index) in [1, 2]" :key="index">
                        <div class="flex gap-3 items-center">
                            <div class="p-2 bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ '1' }}</div>
                            <div class="p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                            <div class="flex flex-col gap-2 items-start justify-center self-stretch">
                                <p class="text-base font-medium text-grey-900 self-stretch overflow-hidden text-ellipsis">
                                    <span class="text-primary-800">(/)</span> item
                                </p>
                                <div class="flex flex-nowrap gap-2 items-center">
                                    <Tag value="Set"/>
                                    <p class="text-base font-medium text-primary-950 self-stretch overflow-hidden text-ellipsis">RM {{ '200' }}</p>
                                </div>
                            </div>
                        </div>
                        <Button
                            type="button"
                            class="!w-fit"
                        >
                            Serve Now
                        </Button>
                    </div>
                </div>

                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">Served</p>
                        <DeleteIcon
                            class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="showDeleteReservationForm($event, row.id)"
                        />
                    </div>
                    <div class="flex justify-between items-center py-3" v-for="(item, index) in [1, 2, 3]" :key="index">
                        <div class="flex gap-3 items-center">
                            <div class="p-2 bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ '4' }}</div>
                            <div class="p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                            <div class="flex flex-col gap-2 items-start justify-center self-stretch">
                                <p class="text-base font-medium text-grey-900 self-stretch overflow-hidden text-ellipsis">
                                    <span class="text-grey-600">(/)</span> item
                                </p>
                                <p class="text-base font-medium text-primary-950 self-stretch overflow-hidden text-ellipsis">RM {{ '440' }}</p>
                                <!-- <Tag variant="blue" value="Keep"/> -->
                            </div>
                        </div>
                        <div class="flex flex-col justify-center items-end gap-2 self-stretch">
                            <p class="text-md font-medium text-primary-800 self-stretch overflow-hidden text-ellipsis text-end">+{{ '440' }}pts</p>
                            <div class="flex flex-nowrap gap-1 items-center">
                                <div class="p-2 size-4 bg-primary-100 rounded-full border-[0.3px] border-grey-100"></div>
                                <p class="text-xs text-grey-900 font-medium">Benjamin Hew</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2.5 items-center self-stretch">
                    <Button
                        type="button"
                        variant="tertiary"
                        size="lg"
                        @click="openDrawer('keep')"
                    >
                        Keep Item
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        iconPosition="left"
                        size="lg"
                        @click="openDrawer('add')"
                    >
                        <template #icon>
                            <PlusIcon class="w-6 h-6 text-primary-900 hover:text-primary-800" />
                        </template>
                        More Order
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
