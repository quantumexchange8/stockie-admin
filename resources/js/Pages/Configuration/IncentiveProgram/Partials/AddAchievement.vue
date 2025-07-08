<script setup>
import Button from '@/Components/Button.vue';
import Date from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    waiters: {
        type: Array,
        required: true,
    },
})
const { showMessage } = useCustomToast();

const isUnsavedChangesOpen = ref(false);
const formattedWaiters = computed(() =>
    props.waiters.map(item => ({
        text: item.full_name,
        value: item.id,
        image: item.image,
    }))
);

const emit = defineEmits(['isDirty', 'closeModal', 'getEmployeeIncent']);

const isRate = ref(false);
const comm_type = ref([
    { text: 'Fixed amount', value: 'fixed'},
    { text: 'Percentage of monthly sales', value: 'percentage'}
]);

const recurringDates = ref([...Array(31)].map((_, i) => {
    const day = i + 1;
    const suffix = (day === 11 || day === 12 || day === 13) 
        ? 'th' 
        : ['th', 'st', 'nd', 'rd'][(day % 10) > 3 ? 0 : (day % 10)];
    return {
        text: `${day}${suffix} of every month`,
        value: day
    };
}));


const setIsRate = (type) => {
    isRate.value = type == 'fixed' ? false : true;
}

const unsaved = (status) => {
    emit('closeModal', status);
}

const isFormValid = computed(() => {
    return ['comm_type', 'rate', 'effective_date', 'monthly_sale'].every(field => form[field]);
})

const form = useForm({
    comm_type: comm_type.value[0],
    rate: '',
    effective_date: '',
    monthly_sale: '',
    entitled: '',
})

const submit = () => {
    form.transform((data) => ({
            ...data,
            rate: parseFloat(data.rate),
        }))
        .post(route('configurations.addAchievement'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                form.reset();
                unsaved('leave');
                emit('getEmployeeIncent');
                setTimeout(() => {
                    showMessage({ 
                        severity: 'success',
                        summary: 'New achievement has been successfully added.',
                    });
                }, 200);
            }
        });
}

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex items-start content-start gap-4 self-stretch">
                <Dropdown
                    :inputName="'comm_type'"
                    :labelText="'Commission type'"
                    :inputArray="comm_type"
                    :dataValue="form.comm_type.value"
                    :errorMessage="form.errors?.comm_type"
                    v-model="form.comm_type.value"
                    @onChange="setIsRate(form.comm_type.value)"
                />

                <TextInput
                    labelText="Rate"
                    :inputName="'comm_rate'"
                    :inputType="'number'"
                    withDecimal
                    :iconPosition="isRate ? 'right' : 'left'"
                    :errorMessage="form.errors?.rate"
                    v-model="form.rate"
                >
                    <template #prefix>{{ isRate ? '%' : 'RM' }}</template>
                </TextInput>
            </div>

            <div class="flex items-start content-start gap-4 self-stretch">
                <Date 
                    :inputName="'effective_date'"
                    :labelText="'Effective date'"
                    :placeholder="'DD/MM/YYYY'"
                    :range="false"
                    :errorMessage="form.errors?.effective_date"
                    v-model="form.effective_date"
                />

                <TextInput 
                    :labelText="'Monthly sales hits above'"
                    :inputName="'monthly_sale'"
                    :inputType="'number'"
                    withDecimal
                    :iconPosition="'left'"
                    :errorMessage="form.errors?.monthly_sale"
                    v-model="form.monthly_sale"
                >
                    <template #prefix>RM</template>
                </TextInput>
            </div>


            <MultiSelect
                :inputArray="formattedWaiters"
                :inputName="'entitled'"
                :labelText="'Employee entitled to achieve this commission'"
                :withImages="true"
                :disabled="!formattedWaiters.length"
                :errorMessage="form.errors?.entitled"
                v-model="form.entitled"
            />
        </div>

        <div class="pt-6 flex justify-center items-end gap-4 self-stretch">
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
                :class="{ 'opacity-25': form.processing }"
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

