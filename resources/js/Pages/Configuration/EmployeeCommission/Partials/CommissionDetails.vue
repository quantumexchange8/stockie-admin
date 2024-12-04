<script setup>
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { DeleteIllus } from '@/Components/Icons/illus';
import { BeerIcon2, CommissionIcon, DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast, useInputValidator } from '@/Composables';
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
const { isValidNumberKey } = useInputValidator();

const isEditCommOpen = ref(false);
const isDeleteCommOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isRate = ref(props.commissionDetails.comm_type === 'Fixed amount per sold product' ? false : true);
const isLoading = ref(false);

const commType = [
  { text: 'Fixed amount per sold product', value: 'Fixed amount per sold product' },
  { text: 'Percentage per sold product', value: 'Percentage per sold product' }
];

const form = useForm({
    commType: props.commissionDetails.comm_type,
    rate: props.commissionDetails.rate,
    id: props.commissionDetails.id,
});

const deleteForm = useForm({
    id: '',
})

const requiredFields = ['commType', 'rate'];

const showEditComm = () => {
    form.reset();
    isEditCommOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(form.isDirty){
                isUnsavedChangesOpen.value = true;
            } else {
                isEditCommOpen.value = false;
            }
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isEditCommOpen.value = false;
            isDeleteCommOpen.value = false;
            break;
        }

    }
}

const showDeleteComm = (id) => {
    isDeleteCommOpen.value = true;
    deleteForm.id = id;
}

const submit = () => {
    form.post(route('configurations.updateCommission'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Commission has been edited.',
                });
            }, 200)
            closeModal('leave');
        },
        onError: (errors) => {
            console.error('Form submission error, ', errors);
        }
    })
}

const deleteComm = async () => {
    isLoading.value = true;
    try {
        deleteForm.delete((`/configurations/deleteCommission/${deleteForm.id}`), {
            onSuccess: () => {
                deleteForm.reset();
                closeModal('leave');
                setTimeout(() => {
                    showMessage({ 
                        severity: 'success',
                        summary: 'Commission deleted.',
                    });
                }, 200);
            },
        });
    } catch(error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
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
        @close="closeModal('close')"
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
                        @keypress="isValidNumberKey($event, true)"
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
                        @keypress="isValidNumberKey($event, true)"
                    >
                        <template #prefix>RM</template>
                    </TextInput>
                </div>

                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="closeModal('close')"
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

            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeModal('stay')"
                @leave="closeModal('leave')"
            />
        </form>
    </Modal>

    <Modal
        :maxWidth="'2xs'"
        :show="isDeleteCommOpen"
        :withHeader="false"
        @close="closeModal('leave')"
    >
        <form @submit.prevent="deleteComm">
            <div class="flex flex-col items-center gap-9 rounded-[5px] border border-solid border-primary-200 bg-white m-[-24px]">
                <div class="w-full flex flex-col items-center gap-[10px] bg-primary-50">
                    <div class="w-full flex pt-2 justify-center items-center">
                        <DeleteIllus />
                    </div>
                </div>
                <div class="flex flex-col px-6 items-center gap-1 self-stretch">
                    <span class="self-stretch text-primary-900 text-center text-lg font-medium ">Delete this commission type?</span>
                    <span class="self-stretch text-grey-900 text-center text-base font-medium">Are you sure you want to delete the selected commission type? This action cannot be undone.</span>
                </div>

                <div class="flex px-6 pb-6 justify-center items-start gap-3 self-stretch">
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
                        :disabled="isLoading"
                        @click="deleteComm()"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>


</template>

