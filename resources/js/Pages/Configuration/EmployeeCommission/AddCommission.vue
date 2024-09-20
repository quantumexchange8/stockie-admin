<script setup>
import Button from "@/Components/Button.vue";
import Dropdown from "@/Components/Dropdown.vue";
import MultiSelect from "@/Components/MultiSelect.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    productNames: {
        type: Array,
        required: true,
    }
})

const options = props.productNames.map(item => ({
    text: item.product_name, 
    value: item.id
}));
const emit = defineEmits(['closeModal']);
const isRate = ref(true)
const commType = [
  { text: 'Fixed amount per sold product', value: 'Fixed amount per sold product' },
  { text: 'Percentage per sold product', value: 'Percentage per sold product' }
];

const setIsRate = (type) => {
    if(type == 'Fixed amount per sold product'){
        isRate.value = false;
    }else
        isRate.value = true;
}

const form = useForm({
    commType: '',
    commRate: '',
    involvedProducts: '',
});

// Validate input to only allow numeric value to be entered
const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

const submit = () => {
    form.post(route('configurations.addCommission'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            emit('close');
        },
        onError: (errors) => {
            console.error('Form submission error: ', errors);
        }
    })
}

const closeModal = () => {
    form.reset();
    emit('closeModal');
}

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
                    @keypress="isNumber($event)"
                    
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
                    @keypress="isNumber($event)"
                >
                    <template #prefix>RM</template>
                </TextInput>
            </div>

            <div class="flex flex-col items-start gap-1 self-stretch">
                <MultiSelect 
                    :inputArray="options"
                    :labelText="'Product with this commission'"
                    v-model="form.involvedProducts"
                />
            </div>
        </div>

        <div class="flex pt-3 justify-center items-end gap-4 self-stretch w-full">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="closeModal"
            >
                Cancel
            </Button>
            <Button
                :type="'submit'"
                :size="'lg'"
                :disabled="!isFormValid"
            >
                Add
            </Button>
        </div>
    </form>
</template>