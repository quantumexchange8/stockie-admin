<script setup>
import Button from '@/Components/Button.vue';
import { EmptyWaiterIllus } from '@/Components/Icons/illus';
import { DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import Table from '@/Components/Table.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EditWaiter from './EditWaiter.vue';
import { usePhoneUtils } from '@/Composables';

const props = defineProps({
    rows: {
        type: Array,
        required: true
    },
    columns: {
        type: Array,
        required: true,
    },
    actions: {
        type: Object,
        default: () => {},
    },
    rowType: Object,
    totalPages: Number,
    rowsPerPage: Number,
    searchFilter: Boolean,
    filters: Object,
})

const form = useForm({
    id: '',
    name: '',
    phone: '',
    email: '',
    staffid: '',
    salary: '',
    stockie_email: '',
    stockie_password: '',
})

const { formatPhone } = usePhoneUtils();
const isEditWaiterOpen = ref(false);
const isDeleteWaiterOpen = ref(false);
const selectedWaiter = ref(null);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const showEditWaiterForm = (event, rows) => {
    handleDefaultClick(event);
    isDirty.value = false;
    isEditWaiterOpen.value = true;
    selectedWaiter.value = rows;
}

// const closeModal = () => {
//     isEditWaiterOpen.value = false;
//     isDeleteWaiterOpen.value = false;
// }

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
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isEditWaiterOpen.value = false;
            isDeleteWaiterOpen.value = false;
        }
    }
}

const showDeleteWaiterForm = (event, id) => {
    handleDefaultClick(event);
    isDeleteWaiterOpen.value = true;
    form.id = id;
}

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
}

</script>

<template>
    <Table
        :columns="columns"
        :rows="rows"
        :actions="actions"
        :variant="'list'"
        :rowType="rowType"
        :totalPages="totalPages"
        :rowsPerPage="rowsPerPage"
        :searchFilter="true"
        :filters="filters"
    >
        <template #empty>
            <EmptyWaiterIllus />
                <span class="text-primary-900 text-sm font-medium">You haven’t added any waiter yet...</span>
        </template>
        <template #editAction="rows">
            <EditIcon
                class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                @click="showEditWaiterForm($event, rows)"
            />
        </template>
        <template #deleteAction="rows">
            <DeleteIcon
                class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                @click="showDeleteWaiterForm($event, rows.id)"
            />
        </template>
        <template #role_id="rows">
            <span class="text-grey-900 text-sm font-medium">{{ rows.role_id }}</span>
        </template>
        <template #full_name="rows">
            <template class="flex flex-row gap-[10px] items-center">
                <!-- <span class="w-[32px] h-[32px] flex-shrink-0 rounded-full bg-primary-700"></span> -->
                <img 
                    :src="rows.image ? rows.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                    alt=""
                    class="w-[32px] h-[32px] flex-shrink-0 rounded-full bg-primary-700"
                />
                <span class="text-grey-900 text-sm font-medium truncate">{{ rows.full_name }}</span>
            </template>
        </template>
        <template #phone="rows">
            <span class="text-grey-900 text-sm font-medium">{{ formatPhone(rows.phone) }}</span>   
        </template>
        <template #worker_email="rows">
            <span class="text-grey-900 text-sm font-medium truncate">{{ rows.worker_email }}</span>
        </template>
    </Table>

    <!-- edit modal -->
    <Modal
        :title="'Edit'"
        :maxWidth="'lg'"
        :closeable="true"
        :show="isEditWaiterOpen"
        @close="closeModal('close')"
        class="!h-[373px]"
    >
        <template v-if="selectedWaiter">
            <EditWaiter 
                :waiters="selectedWaiter"
                @close="closeModal"
                @isDirty="isDirty = $event"
            />
        </template>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

    <!-- delete modal -->
    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="isDeleteWaiterOpen"
        :deleteConfirmation="true"
        :deleteUrl="`/waiter/deleteWaiter/${form.id}`"
        :confirmationTitle="`Delete this waiter?`"
        :confirmationMessage="`Are you sure you want to delete the selected waiter? This action cannot be undone.`"
        @close="closeModal('leave')"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-9" >
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('leave')"
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

