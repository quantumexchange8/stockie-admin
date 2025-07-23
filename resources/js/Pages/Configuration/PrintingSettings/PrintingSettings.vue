<script setup>
import Button from '@/Components/Button.vue';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import CreatePrinterForm from './Partials/CreatePrinterForm.vue';
import EditPrinterForm from './Partials/EditPrinterForm.vue';
import { DeleteIcon, EditIcon, PlusIcon } from '@/Components/Icons/solid';
import { DeleteIllus, UndetectableIllus } from '@/Components/Icons/illus';
import { useCustomToast } from '@/Composables/index.js';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';

const isLoading = ref(false);
const isCreatePrinterOpen = ref(false);
const isEditPrinterOpen = ref(false);
const isDeletePrinterOpen = ref(false);
const selectedPrinter = ref(null);
const rows = ref([]);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);

const { showMessage } = useCustomToast();

const columns = ref([
    { field: 'name', header: 'Printer Name', width: '30', sortable: false},
    { field: 'ip_address', header: 'IP', width: '15', sortable: false},
    { field: 'port_number', header: 'Port', width: '10', sortable: false},
    { field: 'kick_cash_drawer', header: 'Open Cash Drawer', width: '23', sortable: false},
    { field: 'action', header: 'Action', width: '22', sortable: false},
])

const getAllPrinters = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/getAllPrinters');
        rows.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const openCreatePrinter = () => {
    isDirty.value = false;
    isCreatePrinterOpen.value = true;
}

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
}

const openEditPrinter = (event, rows) => {
    handleDefaultClick(event);
    isDirty.value = false;
    isEditPrinterOpen.value = true;
    selectedPrinter.value = rows;
}

const openDeletePrinter = (event, id) => {
    handleDefaultClick(event);
    isDeletePrinterOpen.value = true;
    selectedPrinter.value = id;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isCreatePrinterOpen.value = false;
                isEditPrinterOpen.value = false;
                isDeletePrinterOpen.value = false;
                selectedPrinter.value = null;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave' :{
            isUnsavedChangesOpen.value = false;
            isCreatePrinterOpen.value = false;
            isEditPrinterOpen.value = false;
            isDeletePrinterOpen.value = false;
            selectedPrinter.value = null;
        }
    }
} 

const deletePrinter = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post(`/configurations/deletePrinter/${selectedPrinter.value}`);
        rows.value = response.data;

        closeModal('close');
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Printer has been successfully deleted.',
            });
        }, 200);
        
    } catch (err) {
        console.error("Print failed:", err);
        showMessage({
            severity: 'error',
            summary: 'Delete printer failed',
            detail: err.message
        });
    } finally {
        isLoading.value = false;
    }
}

const testPrint = async (printer_name) => {
    try {
        const params = {
            printer_name: printer_name
        };

        const response = await axios.post('/configurations/getPrinterTest', params);
        const printer = response.data.printer;

        if (!printer || !printer.ip_address || !printer.port_number) {
            let summary = '';
            let detail = '';

            if (!printer) {
                summary = 'Unable to detect selected printer';
                detail = 'Please contact admin to setup the printer.';

            } else if (!printer.ip_address) {
                summary = 'Invalid printer IP address';
                detail = 'Please contact admin to setup the printer IP address.';

            } else if (!printer.port_number) {
                summary = 'Invalid printer port number';
                detail = 'Please contact admin to setup the printer port number.';
            }

            showMessage({
                severity: 'error',
                summary: summary,
                detail: detail,
            });

            return;
        }

        const base64 = response.data.data;
        const url = `stockie-app://print?base64=${base64}&ip=${printer.ip_address}&port=${printer.port_number}`;

        try {
            window.location.href = url;

        } catch (e) {
            console.error('Failed to open app:', e);
            alert(`Failed to open STOXPOS app \n ${e}`);

        }
        
    } catch (err) {
        console.error("Print failed:", err);
        showMessage({
            severity: 'error',
            summary: 'Print failed',
            detail: err.message
        });
    }
}

onMounted(() => {
    getAllPrinters();
})

</script>

<template>
    <div class="w-full flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]">
        <div class="flex w-full items-center justify-between gap-[10px]">
            <span class="text-md font-medium text-primary-900">
                Printer Listing
            </span>
            <!-- <Button
                :type="'button'"
                :size="'lg'"
                :iconPosition="'left'"
                class="!w-fit flex items-center gap-2"
                @click="openCreatePrinter"
            >
                <template #icon>
                    <PlusIcon />
                </template>
                Add Printer
            </Button> -->
        </div>

        <Table
            :columns="columns"
            :rows="rows"
            :variant="'list'"
            minWidth="min-w-[790px]"
        >
            <template #empty>
                <div class="flex w-full flex-col items-center justify-center gap-5 h-fit">
                    <UndetectableIllus />
                    <span class="text-primary-900 text-sm font-medium">You haven’t added any printer yet...</span>
                </div>
            </template>
            <template #name="row">
                <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ row.name }}</span>
            </template>
            <template #ip_address="row">
                <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ row.ip_address }}</span>
            </template>
            <template #port_number="row">
                <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ row.port_number }}</span>
            </template>
            <template #kick_cash_drawer="row">
                <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ !!row.kick_cash_drawer ? 'Enabled' : 'Disabled' }}</span>
            </template>
            <template #action="row">
                <div class="flex justify-end items-center gap-2 size-full">
                    <Button
                        :type="'button'"
                        :size="'md'"
                        class="!w-fit"
                        @click="testPrint(row.name)"
                    >
                        Test Print
                    </Button>
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer flex-shrink-0"
                        @click="openEditPrinter($event, row)"
                    />
                    <!-- <DeleteIcon
                        class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer flex-shrink-0"
                        @click="openDeletePrinter($event, row.id)"
                    /> -->
                </div>
            </template>
        </Table>
    </div>

    <Modal
        :show="isCreatePrinterOpen"
        :maxWidth="'sm'"
        :closeable="true"
        :title="'Add Printer'"
        @close="closeModal('close')"
    >
        <CreatePrinterForm
            @closeModal="closeModal"
            @isDirty="isDirty = $event"
            @update:printers="rows = $event"
        />
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        >
        </Modal>
    </Modal>

    <Modal
        :show="isEditPrinterOpen"
        :maxWidth="'sm'"
        :closeable="true"
        :title="'Edit Printer'"
        @close="closeModal('close')"
    >
        <EditPrinterForm
            :printer="selectedPrinter"
            @isDirty="isDirty = $event"
            @closeModal="closeModal"
            @update:printers="rows = $event"
        />
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        >
        </Modal>

    </Modal>

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :show="isDeletePrinterOpen"
        @close="closeModal('close')"
        :withHeader="false"
    >
        <form @submit.prevent="deletePrinter">
            <div class="w-full flex flex-col gap-9" >
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] pt-6 mx-[-24px] mt-[-24px]">
                    <DeleteIllus />
                </div>
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1 text-center">
                        <span class="text-primary-900 text-lg font-medium self-stretch">Delete this printer?</span>
                        <span class="text-grey-900 text-base font-medium self-stretch">The action that is linked to the printer will be unable to print anything. Are you sure you want to delete this printer?</span>
                    </div>
                </div>

                <div class="flex gap-3 w-full">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('close')"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

    <!-- <Modal 
        :show="isDeletePrinterOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/configurations/deleteAchievement/${selectedIncent}`"
        :confirmationTitle="'Delete achievement?'"
        :confirmationMessage="'Are you sure you want to delete this achievement? All the data in this achievement will be lost.'"
        :toastMessage="'Achievement has been deleted.'"
        @close="closeDeleteAchivementModal"
        v-if="selectedIncent"
    /> -->
</template>

