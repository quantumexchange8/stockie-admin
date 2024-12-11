<script setup>
import { DeleteIcon, EditIcon, PercentageIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed, nextTick, reactive, ref, watch } from "vue";
import Button from '@/Components/Button.vue';
import Toast from "@/Components/Toast.vue";
import Table from "@/Components/Table.vue";
import { useCustomToast, useInputValidator, usePhoneUtils } from "@/Composables";
import { DeleteIllus } from "@/Components/Icons/illus";
import DragDropImage from "@/Components/DragDropImage.vue";
import InputError from "@/Components/InputError.vue";

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
const isDirty = ref(false);
const initialTaxForm = ref(null);
const inputRefs = reactive({});
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();
const { formatPhone, transformPhone, formatPhoneInput } = usePhoneUtils();

const getResults = async () => {
    isLoading.value = true
    try {
        let url = `/configurations/getTax`;

        const response = await axios.get(url);
        taxes.value = response.data;
        editTaxForm.items = taxes.value;
        initialTaxForm.value = JSON.parse(JSON.stringify(taxes.value));
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
    items: [],
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
            isDeleteTaxOpen.value = false;
            break;
        }
    }
}

const isEdited = computed(() => (id) => {
    const targetTax = taxes.value.find((item) => item.id === id); 
    return targetTax?.remarks === 'edited' || targetTax?.remarks === 'added'; 
});

const isNoneEdited = computed(() => {
    return editTaxForm.items.every(item => !item.hasOwnProperty('remarks') || item.remarks === null);
})

const openDeleteTax = (tax) => {
    isDeleteTaxOpen.value = true;
    deleteForm.id = tax;
}

const startEditing = (event, tax, type) => {
    event.stopPropagation();
    actionVal.value = 'edit';
    
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
    if (actionCol && percentageColumn) {
        actionCol.width = '5';
        percentageColumn.width = '25';
    }

    isEditingName.value = true;
    isEditingPercentage.value = true;
    isAddTaxClicked.value = false;

    const taxToEdit = editTaxForm.items.find(item => item.id === tax.id);
    if (taxToEdit && !taxToEdit.remarks) {
        taxToEdit.remarks = 'edited'; 
    }

    const inputRefName = type === 'name' 
        ? `nameInput${tax.id}` 
        : `taxInput${tax.id}`;
    nextTick(() => {
        if (inputRefs[inputRefName]) {
            inputRefs[inputRefName].focus(); 
        }
    });

    if (editTaxForm.id !== tax.id) {
        editTaxForm.id = tax.id;
        editTaxForm.name = tax.name || ''; 
        editTaxForm.value = tax.value || 0; 
    }
};

const addTax = (event) => {

    // column behaviours
    event.stopPropagation();
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
    if (actionCol && percentageColumn) {
        actionCol.width = '5';
        percentageColumn.width = '25'; 
    }

    isAddTaxClicked.value = true;
    actionVal.value = 'add';
    const tempId = Date.now();
    taxes.value.push({ id: tempId, name: '', value: '', remarks: 'added' });

    //newly added tax    
    const newTax = taxes.value[taxes.value.length - 1];
    isEditingPercentage.value = true;
    isEditingName.value = true;
    nextTick(() => {
        const inputRefName = `nameInput${newTax.id}`;
        if (inputRefs[inputRefName]) {
            inputRefs[inputRefName].focus();
        }
    });
    editTaxForm.id = newTax.id;
    editTaxForm.name = newTax.name;
    editTaxForm.value = newTax.value;
}

const quitAddingTax = (event) => {
    event.stopPropagation();
    const actionCol = merchantColumn.value.find(col => col.field === 'action');
    const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
    if (actionCol && percentageColumn) {
        actionCol.width = '13';
        percentageColumn.width = '17'; 
    }

    isAddTaxClicked.value = false;
    actionVal.value = null;
    const lastTax = editTaxForm.items[editTaxForm.items.length - 1];
    if (lastTax) {
        taxes.value.pop();
    }
    isEditingName.value = false;
    isEditingPercentage.value = false;
}

const stopEditing = (event, id) => {
    event.stopPropagation();
    isAddTaxClicked.value = false;
    isEditingName.value = false;
    isEditingPercentage.value = false;

    //clear only this row's error message
    if (editTaxForm.errors) {
        const errors = { ...editTaxForm.errors }; 
        Object.keys(errors).forEach((key) => {
            if (key.startsWith(`taxes.${id}.`)) {
                delete errors[key]; 
            }
        });
        editTaxForm.errors = errors; 
    }

    //restore the value for this row to default value 
    const index = editTaxForm.items.findIndex((item) => item.id === id);
    if (index !== -1 && initialTaxForm.value.find((item) => item.id === id)) {
        editTaxForm.items[index] = JSON.parse(JSON.stringify(initialTaxForm.value.find((item) => item.id === id)));
        if(editTaxForm.items[index].remarks === 'edited') {
            delete editTaxForm.items[index].remarks;
        }
    }

    if(isNoneEdited.value){
        const actionCol = merchantColumn.value.find(col => col.field === 'action');
        const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
        if (actionCol && defaultActionWidth && percentageColumn) {
            actionCol.width = '13'; 
            percentageColumn.width = '17';
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
    isEditingName.value = false;
    isEditingPercentage.value = false;
    if(isDirty.value){
        const currentAction = actionVal.value;
        // const submitItems = editTaxForm.items.filter(tax => tax.remarks === (currentAction === 'add' ? 'added' : 'edited'));
        // editTaxForm.items = submitItems;
        editTaxForm.post(route('configurations.addTax'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const actionCol = merchantColumn.value.find(col => col.field === 'action');
                const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
                if (actionCol && percentageColumn) {
                    percentageColumn.width = '17';
                    actionCol.width = '13'; 
                }
                editTaxForm.reset();
                setTimeout(() => {
                    showMessage({ 
                        severity: 'success',
                        summary: `${currentAction === 'add' ? 'New t' : 'T'}ax type has been ${currentAction}ed successfully.`,
                        closeable: false,
                    });
                }, 200);
                actionVal.value = null;
                isAddTaxClicked.value = false;
                getResults();
            },
            onError: () => {
                isEditingName.value = true;
                isEditingPercentage.value = true;
                // isAddTaxClicked.value = true; 
                const actionCol = merchantColumn.value.find(col => col.field === 'action');
                const percentageColumn = merchantColumn.value.find(col => col.field === 'value');
                if (actionCol && percentageColumn) {
                    percentageColumn.width = '25';
                    actionCol.width = '5'; 
                }
            },
        })
    } else {
        // if isDirty is false (no changes), then simply make everything into default 
        isAddTaxClicked.value = false;
        actionVal.value = null;
        const lastTax = taxes.value[taxes.value.length - 1];
        if (lastTax) {
            if (lastTax.name.trim() === '' && lastTax.value.trim() === ''){
                taxes.value.pop();
            }
        }

        //remove remarks if no changes
        taxes.value.forEach(item => {
            if(item.remarks === 'edited'){
                delete item.remarks;
            }
        })
        isEditingName.value = false;
        isEditingPercentage.value = false;
    }
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

const isFormValid = computed(() => {
    return editTaxForm.items.every(item => item.name && item.value);
});

watch(() => taxes.value, (newValue) => {
    taxes.value = newValue ? newValue : {};
}, { immediate: true });

watch(editTaxForm, () => {
    const restArray = editTaxForm.items.map(({ remarks, ...rest }) => rest);
    isDirty.value = JSON.stringify(restArray) !== JSON.stringify(initialTaxForm.value);
}, { immediate: true });

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
        <form @submit.prevent="taxSubmit">
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
                            <div class="flex size-full !items-center justify-end">
                                <EditIcon
                                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                    v-show="!isEditingName && !isEditingPercentage && !isAddTaxClicked && isNoneEdited"
                                    @click="startEditing($event, taxes, 'name')"
                                />
                            </div>
                        </template>
                        <template #deleteAction="taxes">
                            <div class="flex size-full"
                                :class="isNoneEdited ? '!items-center' : '!items-start'"
                            >
                                <DeleteIcon
                                    class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                    v-show="!isEditingName && !isEditingPercentage && !isAddTaxClicked && isNoneEdited"
                                    @click="openDeleteTax(taxes.id)"
                                />
                                <DeleteIcon
                                    class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                    v-show="editTaxForm.items.length && isEdited(taxes.id)"
                                    @click="isAddTaxClicked ? quitAddingTax($event) : stopEditing($event, taxes.id)"
                                />
                            </div>
                        </template>
                        <template #name="taxes">
                            <div class="flex !items-start size-full" v-if="editTaxForm.items.length && isEdited(taxes.id)" >
                                <TextInput
                                    v-model="editTaxForm.items.find(item => item.id === taxes.id).name"
                                    :inputName="'taxes.'+taxes.id+'.name'"
                                    :ref="el => (inputRefs[`nameInput${taxes.id}`] = el)"
                                    :errorMessage="editTaxForm.errors ? editTaxForm.errors['taxes.' + taxes.id + '.name'] : null"
                                    :disabled="editTaxForm.processing"
                                    @click="isAddTaxClicked ? null : startEditing($event, taxes, 'name')"
                                />
                            </div>
                            <div class="w-full" 
                                v-else
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'name')" 
                            >
                                <span 
                                    class="flex-[1_0_0] text-grey-900 text-sm font-medium !w-full"
                                    :class="editTaxForm.processing ? 'cursor-not-allowed pointer-events-none' : null"
                                    @keydown.enter.prevent="taxSubmit()"
                                >
                                    {{ taxes.name }}
                                </span>
                                <InputError :message="editTaxForm.errors ? editTaxForm.errors['taxes.' + taxes.id + '.name'] : null"></InputError>
                            </div>

                            <!-- keep commented in case click outside behaviour is requested -->
                            <!-- <TextInput
                                :ref="el => (inputRefs[`nameInput${taxes.id}`] = el)"
                                v-model="editTaxForm.name"
                                v-show="isEditingName && editTaxForm.id == taxes.id" 
                                :errorMessage="taxes.id === editTaxForm.id ? editTaxForm.errors?.name : null"
                                :disabled="editTaxForm.processing"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'name')" 
                                @keydown.enter.prevent="taxSubmit()"
                                @blur="isAddTaxClicked && (!editTaxForm.name || !editTaxForm.value) ? null : stopEditing()"
                            /> -->
                        </template>
                        <template #value="taxes">
                            <div class="flex !items-start size-full" v-if="editTaxForm.items.length && isEdited(taxes.id)">
                                <TextInput
                                    v-model="editTaxForm.items.find(item => item.id === taxes.id).value"
                                    iconPosition="'right'"
                                    :inputName="'taxes.' + taxes.id + '.value'"
                                    :ref="el => (inputRefs[`taxInput${taxes.id}`] = el)"
                                    :errorMessage="editTaxForm.errors ? editTaxForm.errors['taxes.' + taxes.id + '.value'] : null"
                                    :disabled="editTaxForm.processing"
                                    @click="isAddTaxClicked ? null : startEditing($event, taxes, 'percentage')"
                                    @keypress="isValidNumberKey($event, true)"
                                >
                                    <template #prefix>
                                        <PercentageIcon />
                                    </template>
                                </TextInput>
                            <!-- keep commented in case click outside behaviour is requested -->
                            <!-- <TextInput
                                :ref="el => (inputRefs[`taxInput${taxes.id}`] = el)"
                                v-model="editTaxForm.value"
                                v-if="isEditingPercentage && editTaxForm.id == taxes.id"
                                :errorMessage="taxes.id === editTaxForm.id ? editTaxForm.errors?.value : null"
                                :disabled="editTaxForm.processing"
                                iconPosition="'right'"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'percentage')"
                                @blur="isAddTaxClicked && (!editTaxForm.name || !editTaxForm.value) ? null : stopEditing()"
                                @keypress="isValidNumberKey($event, false)"
                                @keydown.enter.prevent="stopEditing()"
                            >
                                <template #prefix>
                                    <PercentageIcon />
                                </template>
                            </TextInput> -->
                        </div>
                        <template v-else>
                            <div class="flex flex-col w-full"
                                @click="isAddTaxClicked ? null : startEditing($event, taxes, 'percentage')"
                                @blur="isAddTaxClicked && (!editTaxForm.name || !editTaxForm.value) ? null : stopEditing($event, taxes.id)"
                            >
                                <div class="!p-3 !items-center !text-center content-center !border !rounded-[5px] !border-solid !border-primary-100 !w-full !flex">
                                    <div 
                                        class="flex-[1_0_0] text-grey-900 text-sm font-medium"
                                        :class="editTaxForm.processing ? 'cursor-not-allowed pointer-events-none' : null"
                                    >
                                        {{ taxes.value ? parseInt(taxes.value) : '' }}
                                    </div>
                                    <PercentageIcon />
                                </div>
                                <InputError :message="editTaxForm.errors ? editTaxForm.errors['taxes.' + taxes.id + '.value'] : null"/>
                            </div>
                        </template>
                        </template>

                    </Table>

                    <div>
                        <Button 
                            :variant="'tertiary'" 
                            :size="'lg'" 
                            @click="addTax($event)" 
                            :disabled="editTaxForm.processing"
                            v-if="!isEditingName && !isEditingPercentage && isNoneEdited"
                        >
                            <PlusIcon />
                            Tax Type
                        </Button>
                        <Button 
                            :variant="'primary'" 
                            :size="'lg'" 
                            @click="taxSubmit()"
                            :disabled="editTaxForm.processing || !isFormValid"
                            v-else
                        >
                            <!-- Save Changes -->
                            {{ isDirty ? 'Save Changes' : 'Discard' }}
                        </Button>
                        <!-- <Button 
                            :variant="'primary'" 
                            :size="'lg'" 
                            @click="console.log(editTaxForm.items.every(item => !item.hasOwnProperty('remarks') || item.remarks === null))"
                            :type="'button'"
                        >
                            {{ taxes }}
                        </Button> -->
                    </div>
                </div>
            </div>
        </form>
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
                        :disabled="deleteForm.processing"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>
