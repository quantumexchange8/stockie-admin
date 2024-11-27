<script setup>
import { DeleteIcon, EditIcon, MailIcon, PhoneIcon, SalaryIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { ref, watch } from 'vue';
import EditWaiter from './EditWaiter.vue';
import Button from '@/Components/Button.vue';
import Tag from '@/Components/Tag.vue';
import { transactionFormat, usePhoneUtils } from '@/Composables';

const props = defineProps({
    waiter: {
        type:Object,
        default: () => {},
    }
})
const waiterDetail = ref();
const isEditWaiterOpen = ref(false);
const isDeleteWaiterOpen = ref(false);
const selectedWaiter = ref(null);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const { formatPhone } = usePhoneUtils();
const { formatAmount } = transactionFormat();

const showEditWaiterForm = (waiterDetail) => {
    isEditWaiterOpen.value = true;
    selectedWaiter.value = waiterDetail;
}

const showDeleteWaiterForm = (id) => {
    isDeleteWaiterOpen.value = true;
    selectedWaiter.value = id;
}


const closeModal = (status) => {
    switch(status){
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isEditWaiterOpen.value = false;
            }
            break;
        }
        case 'stay':{
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave':{
            isUnsavedChangesOpen.value = false;
            isEditWaiterOpen.value = false;
            isDeleteWaiterOpen.value = false;
            isDirty.value = false;
        }
    }
}

watch( () => props.waiter, (newValue) => {
    waiterDetail.value = newValue ? newValue : {};
}, { immediate: true });

</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="relative flex gap-5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Waiter Detail</span>
            <div class="flex flex-nowrap gap-2">
                    <EditIcon 
                        class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditWaiterForm(waiterDetail)"
                    />
                    <DeleteIcon 
                        class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                        @click="showDeleteWaiterForm(waiterDetail.id)"
                    />
            </div>
        </div>

        <div class="flex flex-col justify-between">
            <div class="p-3 flex flex-row gap-6">
                <!-- <div class="w-[95px] h-[95px] rounded-[1.734px] bg-red-300"></div> -->
                <img :src="waiterDetail.image ? waiterDetail.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                    alt=""
                    class="w-[95px] h-[95px] rounded-[1.734px] bg-red-300">
                <div 
                    class="flex flex-col bg-white gap-1"
                >
                <div>
                    <Tag
                        :variant="'default'"
                        :value="waiterDetail.role_id"
                        class="px-[10px] py-2 w-auto"
                    /></div>
                    <span class="font-md text-grey-900">{{ waiterDetail.full_name }}</span>
                </div>
            </div>
            <div class="w-full grid grid-rows-3 divide-y divide-primary-50">
                <div class="w-full flex flex-row row-span-1 gap-4 items-center ">
                    <div class="flex w-8 h-8 items-center justify-center bg-primary-50">
                        <PhoneIcon class="text-primary-900"></PhoneIcon>
                    </div>
                    <span class="font-md text-grey-900 py-[14px]">{{ formatPhone(waiterDetail.phone) }}</span>
                </div>
                <div class="w-full flex flex-row row-span-1 gap-4 items-center">
                    <div class="flex w-8 h-8 items-center justify-center bg-primary-50">
                        <MailIcon class="text-primary-900"/>
                    </div>
                    <span class="font-md text-grey-900 py-[14px] truncate">{{ waiterDetail.worker_email }}</span>
                </div>
                <div class="w-full flex flex-row row-span-1 gap-4 items-center">
                    <div class="flex w-8 h-8 items-center justify-center bg-primary-50">
                        <SalaryIcon class="text-primary-900"/>
                    </div>
                    <span class="font-md text-grey-900 py-[14px]">RM {{ formatAmount(waiterDetail.salary) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- edit modal -->
    <Modal
        :title="'Edit'"
        :maxWidth="'lg'"
        :closeable="true"
        :show="isEditWaiterOpen"
        @close="closeModal('close')"
    >
        <template v-if="selectedWaiter">
            <EditWaiter 
                :waiters="selectedWaiter"
                @close="closeModal"
                @isDirty="isDirty = $event"
            />
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeModal('stay')"
                @leave="closeModal('leave')"
            />
        </template>

    </Modal>

    <!-- delete modal -->
    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="isDeleteWaiterOpen"
        :deleteConfirmation="true"
        :deleteUrl="`/waiter/deleteWaiter/${selectedWaiter}`"
        :confirmationTitle="`Delete this waiter?`"
        :confirmationMessage="`Are you sure you want to delete the selected waiter? This action cannot be undone.`"
        @close="closeModal('leave')"
        v-if="isDeleteWaiterOpen"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-9" >
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('stay')"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        type="submit"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

</template>
