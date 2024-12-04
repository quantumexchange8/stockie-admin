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
    productToAdd: {
        type: Array,
        default: () => {},
    }
})

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const options = computed(() =>
    props.productToAdd.map(item => ({
        text: item.product_name,
        value: item.id,
        image: item.image,
    }))
);
const emit = defineEmits(['closeModal', 'viewEmployeeComm', 'isDirty']);
const isRate = ref(true)
const isUnsavedChangesOpen = ref(false);
const commType = ref([
    { text: 'Fixed amount per sold product', value: 'Fixed amount per sold product' },
    { text: 'Percentage per sold product', value: 'Percentage per sold product' }
]);

const setIsRate = (type) => {
    if(type == 'Fixed amount per sold product'){
        isRate.value = false;
    }else
        isRate.value = true;
}

const form = useForm({
    commType: commType.value[1].value,
    commRate: '',
    involvedProducts: '',
});

const submit = () => {
    form.post(route('configurations.addCommission'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            unsaved('leave');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'New commission type has been successfully added.',
                });
                emit('viewEmployeeComm');
            }, 200)
            form.reset();
        },
        onError: (errors) => {
            console.error('Form submission error: ', errors);
        }
    })
}

const unsaved = (status) => {
    emit('closeModal', status);
}

const isFormValid = computed(() => {
    return ['commType', 'commRate', 'involvedProducts'].every(field => form[field]);
});

watch(form, (newValue) => emit('isDirty', newValue.isDirty));


</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex items-start gap-4 self-stretch">
                <Dropdown
                    :inputName="'commType'"
                    :labelText="'Commission rate based on'"
                    :inputArray="commType"
                    :dataValue="form.commType"
                    :errorMessage="form.errors?.commType"
                    v-model="form.commType"
                    @onChange="setIsRate(form.commType)"
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
                    :disabled="!options.length"
                    :labelText="'Product with this commission'"
                    v-model="form.involvedProducts"
                    :withImages="true"
                >
                </MultiSelect>
            </div>
        </div>

        <div class="flex pt-3 justify-center items-end gap-4 self-stretch w-full">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="unsaved('close')"
            >
                Cancel
            </Button>
            <Button
                :type="'submit'"
                :size="'lg'"
                :disabled="!isFormValid || form.processing"
            >
                Add
            </Button>
        </div>
    </form>

    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    />
</template>