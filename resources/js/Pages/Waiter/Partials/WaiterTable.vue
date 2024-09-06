<script setup>
import Button from '@/Components/Button.vue';
import { EmptyWaiterIllus } from '@/Components/Icons/illus';
import { DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import Table from '@/Components/Table.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EditWaiter from './EditWaiter.vue';

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


const isEditWaiterOpen = ref(false);
const isDeleteWaiterOpen = ref(false);
const selectedWaiter = ref(null);

function formatPhone(phone) {
    if (!phone || phone.length < 10) 
        return phone; 

    if (phone.startsWith('+6')) 
        phone = phone.slice(2);
    const totalLength = phone.length;
    const cutPosition = totalLength - 4;

    const firstPart = phone.slice(0, 3);
    const middlePart = phone.slice(3, cutPosition)
    const lastPart = phone.slice(cutPosition);       

    return `${firstPart} ${middlePart} ${lastPart}`;
}

const showEditWaiterForm = (event, rows) => {
    handleDefaultClick(event);
    isEditWaiterOpen.value = true;
    selectedWaiter.value = rows;
}

const closeModal = () => {
    isEditWaiterOpen.value = false;
    isDeleteWaiterOpen.value = false;
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
        minWidth="min-w-[860px]"
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
        <template #staffid="rows">
            <span class="text-grey-900 text-sm font-medium">{{ rows.staffid }}</span>
        </template>
        <template #name="rows">
            <template class="flex flex-row gap-[10px] items-center">
                <span class="w-[32px] h-[32px] flex-shrink-0 rounded-full bg-primary-700"></span>
                <span class="text-grey-900 text-sm font-medium">{{ rows.name }}</span>
            </template>
        </template>
        <template #phone="rows">
            <span class="text-grey-900 text-sm font-medium">{{ formatPhone(rows.phone) }}</span>   
        </template>
        <template #stockie_email="rows">
            <span class="text-grey-900 text-sm font-medium truncate">{{ rows.stockie_email }}</span>
        </template>
    </Table>

    <!-- edit modal -->
    <Modal
        :title="'Edit'"
        :maxWidth="'lg'"
        :closeable="true"
        :show="isEditWaiterOpen"
        @close="closeModal"
        class="!h-[373px]"
        v-if="isEditWaiterOpen"
    >
        <template v-if="selectedWaiter">
            <EditWaiter 
                :waiters="selectedWaiter"
            />
        </template>
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
    @close="closeModal"
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
                        @click="closeModal"
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

