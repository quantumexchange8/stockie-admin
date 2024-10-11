<script setup>
import { DeleteIcon, EditIcon, PercentageIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import Button from '@/Components/Button.vue';
import Toast from "@/Components/Toast.vue";
import Table from "@/Components/Table.vue";
import { useCustomToast } from "@/Composables";
import { DeleteIllus } from "@/Components/Icons/illus";

const props = defineProps({
    merchant: Object,
})

const taxes = ref([]);
const isLoading = ref(false);
const selectedTax = ref(null);
const merchantDetailsModal = ref(false);
const isDeleteTaxOpen = ref(false);
const isEditingName = ref(false);
const isEditingPercentage = ref(false);
const isAddTaxClicked = ref(false);
const actionVal = ref(null);
const { showMessage } = useCustomToast();

const getResults = async () => {
    isLoading.value = true
    try {
        let url = `/configurations/getTax`;

        const response = await axios.get(url);
        taxes.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

const form = useForm({
    merchant_id: props.merchant.id ?? '',
    merchant_name: props.merchant.merchant_name ?? '',
    merchant_contact: props.merchant.merchant_contact ?? '',
    merchant_address: props.merchant.merchant_address ?? '',
    name: '',
    percentage: '',
});

const editTaxForm = useForm({
    id: taxes.id,
    name: taxes.name,
    percentage: taxes.percentage,
})

const deleteForm = useForm({
    id: selectedTax.value,
})

const merchantColumn = ref([
    { field: 'name', header: 'Tax Type', width: '70', sortable: false},
    { field: 'percentage', header: 'Percentage', width: '17', sortable: false},
    { field: 'action', header: '', width: '13', sortable: false}
])

const actionColumn = ref(merchantColumn.value.find(col => col.field === 'action'));
const defaultActionWidth = actionColumn.value ? actionColumn.value.width : null;

const actions = {
    edit: () => ``,
    delete: () => ``,
};


const editDetails = () => {
    merchantDetailsModal.value = true;
}

const closeEditDetails = () => {
    merchantDetailsModal.value = false;
    isDeleteTaxOpen.value = false;
}

const openDeleteTax = (tax) => {
    isDeleteTaxOpen.value = true;
    deleteForm.id = tax;
}

const startEditing = (event, tax, type) => {
    event.preventDefault();
    event.stopPropagation();
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    if (actionCol) {
        actionCol.width = '0';
    }

    const percentageColumn = merchantColumn.value.find(col => col.field === 'percentage');
    if (percentageColumn) {
        percentageColumn.width = '30'; 
    }

    switch(type){
        case 'all' : {
            isEditingName.value = true;
            isEditingPercentage.value = true;
            break;
        }
        case 'name' : {
            isEditingName.value = true;
            isEditingPercentage.value = false;
            break;
        }
        case 'percentage' : {
            isEditingName.value = false;
            isEditingPercentage.value = true;
            break;
        }
        default : {
            isEditingName.value = false;
            isEditingPercentage.value = false;
        }
    };

    actionVal.value = 'edit';
    editTaxForm.id = tax.id;
    editTaxForm.name = tax.name;
    editTaxForm.percentage = tax.percentage.toString();
}


const addTax = (event) => {
    event.preventDefault();
    event.stopPropagation();
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    if (actionCol) {
        actionCol.width = '0';
    }

    const percentageColumn = merchantColumn.value.find(col => col.field === 'percentage');
    if (percentageColumn) {
        percentageColumn.width = '30'; 
    }
    isAddTaxClicked.value = true;
    actionVal.value = 'add';
    const tempId = Date.now();
    taxes.value.push({ id: tempId, name: '', percentage: '' });

    const newTax = taxes.value[taxes.value.length - 1];
    isEditingPercentage.value = true;
    isEditingName.value = true;
    editTaxForm.id = newTax.id;
    editTaxForm.name = newTax.name;
    editTaxForm.percentage = newTax.percentage.toString();

}

const stopEditing = () => {
    
    isAddTaxClicked.value = false;
    isEditingName.value = false;
    isEditingPercentage.value = false;
    
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    if (actionCol && defaultActionWidth) {
        actionCol.width = defaultActionWidth; 
    }

    const percentageColumn = merchantColumn.value.find(col => col.field === 'percentage');
    if (percentageColumn) {
        percentageColumn.width = '17';
    }

    if (editTaxForm.name.trim() !== '' && editTaxForm.percentage.trim() !== '') {
        taxSubmit();
    } else {
        showMessage({
            severity: 'error',
            summary: 'Both name and percentage must be filled out.',
        });
        getResults();
        const lastTax = taxes.value[taxes.value.length - 1];
        if (lastTax && lastTax.name.trim() === '' && lastTax.percentage.toString().trim() === '') {
            taxes.value.pop();
        }
    }
}

const formSubmit = () => { 
    form.post(route('configurations.updateMerchant'), {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'You’ve successfully edited the merchant detail.',
                    closeable: false,
                });
            }, 200);
            closeEditDetails();
        }
    })
};

const taxSubmit = () => {

    const currentAction = actionVal.value;

    editTaxForm.post(route('configurations.addTax'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            editTaxForm.reset();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: `${currentAction === 'add' ? 'New t' : 'T'}ax type has been ${currentAction}ed successfully.`,
                    closeable: false,
                });
            }, 200);
            actionVal.value = null;
            getResults();
        },
        onError: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'error',
                    summary: editTaxForm.errors.name,
                });
            }, 200);
            getResults();
            const lastTax = taxes.value[taxes.value.length - 1];
            if (lastTax && lastTax.name.trim() === '' && lastTax.percentage.toString().trim() === '') {
                taxes.value.pop();
            }
        }
    })
}

const deleteSubmit = () => {
    isLoading.value = true;
    try {
        deleteForm.delete(route('configuration.deleteTax', deleteForm.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                deleteForm.reset();
                setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: 'Tax type deleted successfully.',
                    });
                }, 200)
                closeEditDetails();
                getResults();
            },
            onError: () => {
                showMessage({
                    severity: 'error',
                    summary: 'Error occurred. Please contact administrator.'
                });
            },
        })
    } catch(error) {
        console.error('Error occurred. ', error)
    } finally {
        isLoading.value = false;
    }
}


// Validate input to only allow numeric value to be entered
const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};


getResults()

watch(() => taxes.value, (newValue) => {
    taxes.value = newValue ? newValue : {};
}, { immediate: true });

</script>

<template>
    <Head title="Configuration" />

    <div class="w-full flex flex-col gap-5">
        <Toast
            inline
            severity="info"
            actionLabel="OK"
            summary="Set up your merchant detail"
            detail="All the detail here will be displayed in the customer’s ‘Order Invoice’. "
            :closable="false"
        />

        <!-- merchant detail -->
        <div class="flex flex-col p-6 items-start gap-6 rounded-[5px] border border-solid border-primary-100">
            <div class="flex items-center justify-between w-full self-stretch">
                <span class="text-primary-900 text-md font-medium ">
                    Merchant detail
                </span>
                <EditIcon class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" @click="editDetails" />
            </div>
            <div class="w-full flex items-center gap-6 ">
                <div class="w-[240px] h-[240px] bg-gray-200">
                
                </div>
                <div class="w-full flex flex-col justify-between items-start flex-[1_0_0] self-stretch divide-y divide-grey-100">
                    <div class="flex h-full justify-between items-center self-stretch">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="text-grey-600 align-center text-sm font-medium">Merchant Name</span>
                            <span class="text-grey-900 align-center text-md font-medium">{{ props.merchant.merchant_name }}</span>
                        </div>
                    </div>
                    <div class="w-full flex h-full justify-between items-center self-stretch">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="text-grey-600 align-center text-sm font-medium">Merchant Contact No.</span>
                            <span class="text-grey-900 align-center text-md font-medium">{{ props.merchant.merchant_contact }}</span>
                        </div>
                    </div>
                    <div class="w-full flex h-full justify-between items-center self-stretch">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="text-grey-600 align-center text-sm font-medium">Merchant Address</span>
                            <span class="text-grey-900 align-center text-md font-medium">{{ props.merchant.merchant_address }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- tax setting -->
        <!-- <form @submit.prevent="taxSubmit"> -->
            <div class="w-full flex flex-col p-6 items-start gap-6 rounded-[5px] border border-solid border-primary-100">
                <div class="text-primary-900 text-md font-medium">
                    Tax Setting
                </div>
                <div class="w-full flex flex-col gap-5">
                    <Table
                        :columns="merchantColumn"
                        :rows="taxes"
                        :variant="'list'"
                        :actions="actions"
                        :paginator="false"
                    >
                        <template #editAction="taxes">
                            <EditIcon
                                class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                v-show="!isEditingName && !isEditingPercentage"
                                @click="startEditing($event, taxes, 'all')"
                            />
                        </template>
                        <template #deleteAction="taxes">
                            <DeleteIcon
                                class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                v-show="!isEditingName && !isEditingPercentage"
                                @click="openDeleteTax(taxes.id)"
                            />
                        </template>
                        <template #name="taxes">
                            <span 
                                class="flex-[1_0_0] text-grey-900 text-sm font-medium"
                                :errorMessage="editTaxForm.errors?.name"
                                :disabled="editTaxForm.processing"
                                @keydown.enter.prevent="stopEditing()"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'name')" 
                                @blur="isAddTaxClicked ? null : stopEditing()"
                                v-show="!isEditingName || editTaxForm.id !== taxes.id"
                            >
                                {{ taxes.name }}
                            </span>
                            <TextInput
                                v-model="editTaxForm.name"
                                v-show="isEditingName && editTaxForm.id == taxes.id" 
                                :errorMessage="editTaxForm.errors?.name"
                                :disabled="editTaxForm.processing"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'name')" 
                                @blur="isAddTaxClicked ? null : stopEditing()"
                                @keydown.enter.prevent="stopEditing()"
                            />
                        </template>
                        <template #percentage="taxes">
                            <TextInput
                                v-model="editTaxForm.percentage"
                                v-if="isEditingPercentage && editTaxForm.id == taxes.id"
                                :errorMessage="editTaxForm.errors?.percentage"
                                :disabled="editTaxForm.processing"
                                iconPosition="'right'"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'percentage')"
                                @blur="isAddTaxClicked ? null : stopEditing()"
                                @keydown.enter.prevent="stopEditing()"
                                @keypress="isNumber($event)"
                            >
                                <template #prefix>
                                    <PercentageIcon />
                                </template>
                            </TextInput>
                            <template v-else>
                                <div class="!p-3 !items-center !text-center content-center !border !rounded-[5px] !border-solid !border-primary-100 !w-full !flex">
                                    <div 
                                        class="flex-[1_0_0] text-grey-900 text-sm font-medium"
                                        @click="startEditing($event, taxes, 'percentage')"
                                    >
                                        {{ taxes.percentage }}
                                    </div>
                                    <PercentageIcon />
                                </div>
                            </template>
                        </template>

                    </Table>

                    <div>
                        <Button variant="tertiary" size="lg" @click="isAddTaxClicked ? taxSubmit() : addTax($event)">
                            <PlusIcon />
                            Tax Type
                        </Button>
                    </div>
                </div>
            </div>
        <!-- </form> -->
    </div>

    <Modal
        :title="'Edit Merchant Details'"
        :show="merchantDetailsModal" 
        :maxWidth="'lg'" 
        :closeable="true" 
        @close="closeEditDetails"
    >
        <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
            <div class="flex gap-6">
                <div class="w-1/3 h-[372px] bg-gray-200">
                    
                </div>
                <div class="flex flex-col gap-4 w-2/3">
                    <div>
                        <TextInput
                            :inputName="'merchant_name'"
                            :labelText="'Merchant Name'"
                            :placeholder="'eg: Heineken B1F1 Promotion'"
                            :errorMessage="form.errors.merchant_name"
                            v-model="form.merchant_name"
                        />
                    </div>
                    <div>
                        <TextInput
                            :inputName="'merchant_contact'"
                            :labelText="'Merchant Contact No.'"
                            :placeholder="'eg: Heineken B1F1 Promotion'"
                            :errorMessage="form.errors.merchant_contact"
                            v-model="form.merchant_contact"
                        />
                    </div>
                    <div>
                        <TextInput
                            :inputName="'merchant_address'"
                            :labelText="'Merchant Address'"
                            :placeholder="'eg: Heineken B1F1 Promotion'"
                            :errorMessage="form.errors.merchant_address"
                            v-model="form.merchant_address"
                        />
                    </div>
                </div>
            </div>
            <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="closeEditDetails"
                >
                    Cancel
                </Button>
                <Button
                    :size="'lg'"
                    :disabled="form.processing"
                >
                    Save Changes
                </Button>
            </div>
        </form>
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="isDeleteTaxOpen"
        @close="closeEditDetails"
        :withHeader="false"
    >
        <form @submit.prevent="deleteSubmit">
            <div class="w-full flex flex-col gap-9" >
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] pt-6 mx-[-24px] mt-[-24px]">
                    <DeleteIllus />
                </div>
                <div class="flex flex-col gap-1" >
                    <span class="text-primary-900 text-lg font-medium text-center self-stretch" >
                        Delete tax?
                    </span>
                    <span class="text-grey-900 text-center text-base font-medium self-stretch" >
                        Are you sure you want to delete the selected tax and its settings? This action cannot be undone.
                    </span>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeEditDetails"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>
