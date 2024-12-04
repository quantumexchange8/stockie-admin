<script setup>
import Button from "@/Components/Button.vue";
import Dropdown from "@/Components/Dropdown.vue";
import Modal from "@/Components/Modal.vue";
import MultiSelect from "@/Components/MultiSelect.vue";
import TextInput from "@/Components/TextInput.vue";
import { useCustomToast, useInputValidator } from "@/Composables";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    productNames: {
        type: Array,
        required: true,
    },
    commisionDetails: {
        type: Object,
        required: true,
    },
    productToAdd: {
        type: Array,
        default: () => {},
    }
})
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();
const options = props.productToAdd.map(item => ({
    text: item.product_name, 
    value: item.id,
    image: item.image
}));
if (Array.isArray(props.commisionDetails.product) && Array.isArray(props.commisionDetails.productIds) && props.commisionDetails.product.length === props.commisionDetails.productIds.length) {
    props.commisionDetails.product.forEach((productName, index) => {
        options.push({
            text: productName,
            value: props.commisionDetails.productIds[index],
            image: props.commisionDetails.image[index]
        });
    });
}

const emit = defineEmits(['close', 'viewEmployeeComm', 'isDirty']);
const isRate = computed(() => 
    form.commType === 'Fixed amount per sold product' ? false : true
);
const isUnsavedChangesOpen = ref(false);
const commType = [
  { text: 'Fixed amount per sold product', value: 'Fixed amount per sold product' },
  { text: 'Percentage per sold product', value: 'Percentage per sold product' }
];

// const setIsRate = (type) => {
//     if(type == 'Fixed amount per sold product'){
//         isRate.value = false;
//     }else
//         isRate.value = true;
// }

const form = useForm({
    id: props.commisionDetails.id,
    commType: props.commisionDetails.comm_type,
    commRate: props.commisionDetails.rate,
    involvedProducts: props.commisionDetails.productIds,
});

const submit = () => {
    form.post(route('configurations.editCommission'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeModal('leave');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Commission type has been successfully updated.',
                });
            }, 200)
            emit('viewEmployeeComm');
        },
        onError: (errors) => {
            console.error('Form submission error: ', errors);
        }
    })
}

const closeModal = (status) => {
    emit('close', status);
}

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

const isFormValid = computed(() => {
    return ['commType', 'commRate', 'involvedProducts'].every(field => form[field]);
});


</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex items-start gap-4 self-stretch">
                <Dropdown
                    :inputName="'commType'"
                    :labelText="'Commission rate based on'"
                    :inputArray="commType"
                    :errorMessage="''"
                    :dataValue="form.commType"
                    v-model="form.commType"
                >
                </Dropdown>

                <TextInput
                    labelText="Rate"
                    :inputName="'commRate'"
                    type="'number'"
                    iconPosition="right"
                    v-model="form.commRate"
                    v-show="isRate"
                    @keypress="isValidNumberKey($event, true)"
                    
                >
                    <template #prefix>%</template>
                </TextInput>

                <TextInput
                    labelText="Amount"
                    :inputName="'commRate'"
                    type="'number'"
                    iconPosition="left"
                    v-model="form.commRate"
                    v-show="!isRate"
                    @keypress="isValidNumberKey($event, true)"
                >
                    <template #prefix>RM</template>
                </TextInput>
            </div>

            <div class="flex flex-col items-start gap-1 self-stretch">
                <MultiSelect 
                    :inputArray="options"
                    :labelText="'Product with this commission'"
                    :dataValue="form.involvedProducts"
                    withImages
                    v-model="form.involvedProducts"
                >
                </MultiSelect>
            </div>
        </div>

        <div class="flex pt-3 justify-center items-end gap-4 self-stretch w-full">
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
                :size="'lg'"
                :disabled="!isFormValid || form.processing"
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
</template>