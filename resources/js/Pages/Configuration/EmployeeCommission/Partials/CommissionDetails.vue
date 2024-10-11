<script setup>
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { BeerIcon2, CommissionIcon, DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    productDetails: {
        type: Object,
        required: true,
    },
    commissionDetails: {
        type: Object,
        required: true
    }
})

const { showMessage } = useCustomToast();

const isEditCommOpen = ref(false);
const isDeleteCommOpen = ref(false);
const isRate = ref(props.commissionDetails.comm_type === 'Fixed amount per sold product' ? false : true);
const deletingComm = ref(null);

const commType = [
  { text: 'Fixed amount per sold product', value: 'Fixed amount per sold product' },
  { text: 'Percentage per sold product', value: 'Percentage per sold product' }
];
const form = useForm({
    commType: props.commissionDetails.comm_type,
    rate: props.commissionDetails.rate,
    id: props.commissionDetails.id,
});
const requiredFields = ['commType', 'rate'];

const showEditComm = () => {
    isEditCommOpen.value = true;
}

const hideEditComm = () => {
    isEditCommOpen.value = false;
}

const showDeleteComm = (id) => {
    isDeleteCommOpen.value = true;
    deletingComm.value = id;
}

const hideDeleteComm = () => {
    isDeleteCommOpen.value = false;
}

// Validate input to only allow numeric value to be entered
const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

const submit = () => {
    form.post(route('configurations.updateCommission'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            hideEditComm();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Commission has been edited.',
                });
            }, 200)
        },
        onError: (errors) => {
            console.error('Form submission error, ', errors);
        }
    })
}

const setIsRate = (type) => {
    if(type == 'Fixed amount per sold product'){
        isRate.value = false;
    }else
        isRate.value = true;
}

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
})
</script>

<template>
    <div class="flex flex-col items-center gap-6 self-stretch h-full">
        <div class="flex flex-col items-start gap-[10px] self-stretch">
            <div class="flex items-start gap-[10px] self-stretch">
                <div class="flex justify-between items-center flex-[1_0_0] rounded-l-[5px]">
                    <span class="text-primary-900 text-md font-medium">Commission Detail</span>
                </div>
                <div class="flex justify-end items-start gap-2">
                    <EditIcon class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"  @click="showEditComm()"/>
                    <DeleteIcon class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer" @click="showDeleteComm(props.commissionDetails.id)"/>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center gap-4 self-stretch">
            <div class="flex flex-col items-center gap-6 self-stretch">
                <div class="w-full flex p-3 items-center gap-4 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="flex size-6 flex-shrink-0 justify-center items-center gap-[10px] rounded-[2px] bg-primary-50">
                        <CommissionIcon />
                    </div>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium" v-if="props.productDetails.type ==='Fixed amount per sold product'">RM {{ props.productDetails.rate }} / sold product</span>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium" v-if="props.productDetails.type ==='Percentage per sold product'">{{ props.productDetails.rate }}% / sold product</span>
                </div>
            </div>

            <div class="flex flex-col items-center gap-6 self-stretch">
                <div class="flex flex-col items-start gap-4 self-stretch">
                    <div class="flex items-start gap-6 self-stretch">
                        <div class="flex p-3 items-center gap-4 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 bg-white">
                            <div class="flex size-6 flex-shrink-0 justify-center items-center gap-[10px] rounded-[2px] bg-primary-50">
                                <BeerIcon2 />
                            </div>
                            <span class="text-primary-900 text-base font-medium">{{ props.productDetails.commItemsCount }}
                                <span class="text-grey-900 text-base font-medium">products</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal
        :maxWidth="'md'"
        :title="'Edit Commission Type'"
        :closeable="true"
        :show="isEditCommOpen"
        @close="hideEditComm"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
                <div class="flex items-start gap-4 self-stretch">
                    <Dropdown
                        :inputName="'commType'"
                        :labelText="'Commission rate based on'"
                        :inputArray="commType"
                        :errorMessage="''"
                        v-model="form.commType"
                        :dataValue="form.commType"
                        @onChange="setIsRate(form.commType)"
                    >
                    </Dropdown>

                    <TextInput
                        labelText="Rate"
                        :inputName="'rate'"
                        :dataValue="form.rate"
                        type="'number'"
                        iconPosition="right"
                        v-model="form.rate"
                        v-show="isRate"
                        @keypress="isNumber($event)"
                    >
                        <template #prefix>%</template>
                    </TextInput>

                    <TextInput
                        labelText="Amount"
                        :inputName="'rate'"
                        :dataValue="form.rate"
                        type="'number'"
                        iconPosition="left"
                        v-model="form.rate"
                        v-show="!isRate"
                        @keypress="isNumber($event)"
                    >
                        <template #prefix>RM</template>
                    </TextInput>
                </div>

                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="hideEditComm"
                    >
                        Cancel
                    </Button>
                    <Button
                        :type="'submit'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :show="isDeleteCommOpen"
        :confirmationTitle="'Delete this commission type?'"
        :confirmationMessage="'Are you sure you want to delete the selected commission type? This action cannot be undone.'"
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/deleteCommission/${deletingComm}`"
        @close="hideDeleteComm"
        v-if="deletingComm"
    >

    </Modal>
</template>

