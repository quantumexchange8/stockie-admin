<script setup>
import Button from '@/Components/Button.vue';
import { DeleteIllus, EmptyWaiterIllus } from '@/Components/Icons/illus';
import { DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import Table from '@/Components/Table.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EditWaiter from './EditWaiter.vue';
import { usePhoneUtils, useCustomToast } from '@/Composables';

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
    actionType: 'update'
})

const emit = defineEmits(['update:waiters']);

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const waiters = ref(props.rows);
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

const submit = async () => {
    try {
        const response = await axios.post(`waiter/deleteWaiter/${form.id}`, form);
        waiters.value = response.data.waiters;

        showMessage({
            severity: 'success',
            summary: `Selected waiter has been successfully deleted.`,
        })
        
        setTimeout(() => {
            emit('update:waiters', response.data);
            closeModal('leave');
        }, 200);

    } catch (error) {
        console.error(error);
    }
}

watch(() => props.rows, (newValue) => {
    waiters.value = newValue;
}, { immediate: true });

</script>

<template>
    <Table
        :columns="columns"
        :rows="waiters"
        :actions="actions"
        :variant="'list'"
        :rowType="rowType"
        :totalPages="totalPages"
        :rowsPerPage="rowsPerPage"
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
        @close="closeModal('leave')"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="w-full flex flex-col gap-9" >
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] pt-6 mx-[-24px] mt-[-24px]">
                    <DeleteIllus />
                </div>
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1 text-center">
                        <span class="text-primary-900 text-lg font-medium self-stretch">Delete this waiter?</span>
                        <span class="text-grey-900 text-base font-medium self-stretch">Are you sure you want to delete the selected waiter? This action cannot be undone.</span>
                    </div>
                </div>

                <div class="flex gap-3 w-full">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('leave')"
                    >
                        Keeps
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

