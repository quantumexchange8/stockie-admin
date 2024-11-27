<script setup>
import { DeleteIcon, EditIcon, PercentageIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import Button from '@/Components/Button.vue';
import Toast from "@/Components/Toast.vue";
import Table from "@/Components/Table.vue";
import { useCustomToast, useInputValidator, usePhoneUtils } from "@/Composables";
import { DeleteIllus } from "@/Components/Icons/illus";
import DragDropImage from "@/Components/DragDropImage.vue";

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
const isUnsavedChangesOpen = ref(false);
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();
const { formatPhone, transformPhone, formatPhoneInput } = usePhoneUtils();

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
    merchant_image: props.merchant.merchant_image ?? null,
    merchant_contact_temp: formatPhone(props.merchant.merchant_contact, true, true),
    merchant_address: props.merchant.merchant_address ?? '',
    name: '',
    value: '',
});

const editTaxForm = useForm({
    id: taxes.id,
    name: taxes.name,
    value: parseInt(taxes.value),
})

const deleteForm = useForm({
    id: selectedTax.value,
})

const merchantColumn = ref([
    { field: 'name', header: 'Tax Type', width: '70', sortable: false},
    { field: 'value', header: 'Percentage', width: '17', sortable: false},
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

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(form.isDirty){
                isUnsavedChangesOpen.value = true;
            } else {
                merchantDetailsModal.value = false;
            }
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            merchantDetailsModal.value = false;
            form.reset();
            isDeleteTaxOpen.value = false;
            break;
        }

    }
}

const openDeleteTax = (tax) => {
    isDeleteTaxOpen.value = true;
    deleteForm.id = tax;
}

const startEditing = (event, tax, type) => {
    // event.preventDefault();
    event.stopPropagation();
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    if (actionCol) {
        actionCol.width = '0';
    }

    const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
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
    editTaxForm.value = tax.value;
}


const addTax = (event) => {
    // event.preventDefault();
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
    taxes.value.push({ id: tempId, name: '', value: '' });

    const newTax = taxes.value[taxes.value.length - 1];
    isEditingPercentage.value = true;
    isEditingName.value = true;
    editTaxForm.id = newTax.id;
    editTaxForm.name = newTax.name;
    editTaxForm.value = newTax.value;

}

const stopEditing = () => {
    
    isAddTaxClicked.value = false;
    isEditingName.value = false;
    isEditingPercentage.value = false;
    
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    if (actionCol && defaultActionWidth) {
        actionCol.width = '13'; 
    }

    const percentageColumn = merchantColumn.value.find(col => col.field === 'percentage');
    if (percentageColumn) {
        percentageColumn.width = '17';
    }

    if (editTaxForm.name.trim() !== '' && editTaxForm.value.trim() !== '') {
        taxSubmit();
    } else {
        showMessage({
            severity: 'error',
            summary: 'Both name and percentage must be filled out.',
        });
        getResults();
        const lastTax = taxes.value[taxes.value.length - 1];
        if (lastTax && lastTax.name.trim() === '' && lastTax.value.trim() === '') {
            taxes.value.pop();
        }
    }
}

const formSubmit = () => { 
    form.merchant_contact = form.merchant_contact_temp ? transformPhone(form.merchant_contact_temp) : '';
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
            closeModal('leave');
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
            if (lastTax && lastTax.name.trim() === '' && lastTax.value.trim() === '') {
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
                closeModal('leave');
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

getResults()

watch(() => taxes.value, (newValue) => {
    taxes.value = newValue ? newValue : {};
}, { immediate: true });

// watch(() => form, (newValue) => {
//     console.log(form.isDirty);
// }, { immediate: true, deep: true });


</script>

<template>
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
                <div class="!w-[240px] !h-[240px]">
                    <img :src="props.merchant.merchant_image ? props.merchant.merchant_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" alt="">
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
                            <span class="text-grey-900 align-center text-md font-medium">{{ formatPhone(props.merchant.merchant_contact) }}</span>
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
                            <!-- blur = check is addtax clicked? if yes, then check if both input field is not empty? -->
                             <!-- if both not empty then return true -->
                            <span 
                                class="flex-[1_0_0] text-grey-900 text-sm font-medium"
                                :errorMessage="editTaxForm.errors?.name"
                                :disabled="editTaxForm.processing"
                                @keydown.enter.prevent="stopEditing()"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'name')" 
                                @blur="isAddTaxClicked && (!editTaxForm.name || !editTaxForm.value) ? null : stopEditing()"
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
                                @blur="isAddTaxClicked && (!editTaxForm.name || !editTaxForm.value) ? null : stopEditing()"
                                @keydown.enter.prevent="stopEditing()"
                            />
                        </template>
                        <template #value="taxes">
                            <TextInput
                                v-model="editTaxForm.value"
                                v-if="isEditingPercentage && editTaxForm.id == taxes.id"
                                :errorMessage="editTaxForm.errors?.value"
                                :disabled="editTaxForm.processing"
                                iconPosition="'right'"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'percentage')"
                                @blur="isAddTaxClicked && (!editTaxForm.name || !editTaxForm.value) ? null : stopEditing()"
                                @keydown.enter.prevent="stopEditing()"
                                @keypress="isValidNumberKey($event, true)"
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
                                        {{ taxes.value }}
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
        @close="closeModal('close')"
    >
        <form class="flex flex-col gap-6" @submit.prevent="formSubmit">
            <div class="flex gap-6">
                <div class="!size-[373px]">
                    <DragDropImage
                        inputName="merchant_image"
                        remarks="Suggested image size: 1920 x 1080 pixel"
                        :errorMessage="form.errors.merchant_image"
                        v-model="form.merchant_image"
                        class="object-contain h-full"
                    />
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
                            :iconPosition="'left'"
                            :errorMessage="form.errors?.merchant_contact || ''"
                            v-model="form.merchant_contact_temp"
                            @keypress="isValidNumberKey($event, false)"
                            @input="formatPhoneInput"
                            class="[&>div:nth-child(2)>input]:text-start"
                        >
                            <template #prefix>
                                <span class="text-grey-700 text-base font-normal">
                                    +60
                                </span>
                            </template>
                        </TextInput>
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
                    @click="closeModal('close')"
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
        :show="isDeleteTaxOpen"
        @close="closeModal('leave')"
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
                        @click="closeModal('leave')"
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
