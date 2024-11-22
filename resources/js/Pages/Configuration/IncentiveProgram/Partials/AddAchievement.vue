<script setup>
import Button from '@/Components/Button.vue';
import Date from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast, useInputValidator } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    waiters: {
        type: Array,
        required: true,
    }
})
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();
const isUnsavedChangesOpen = ref(false);
const waiters = computed(() =>
    props.waiters.map(item => ({
        text: item.full_name,
        value: item.id,
        image: item.image,
    }))
);

const emit = defineEmits(['stay', 'leave', 'isDirty', 'closeModal', 'getEmployeeIncent']);

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
    if(type == 'fixed'){
        isRate.value = false;
    } else {
        isRate.value = true;
    }
}

const closeModal = () => {
    emit('closeModal');
}

const stayModal = () => {
    emit('stay');
}

const leaveModal = () => {
    emit('leave');
}

const isFormValid = computed(() => {
    return ['comm_type', 'rate', 'effective_date', 'recurring_on', 'monthly_sale', 'entitled'].every(field => form[field]);
})

const form = useForm({
    comm_type: comm_type.value[0],
    rate: '',
    effective_date: '',
    recurring_on: recurringDates.value[0],
    monthly_sale: '',
    entitled: '',
})

const submit = () => {
    form.post(route('configurations.addAchievement'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            emit('closeModal');
            emit('getEmployeeIncent');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'New achievement has been successfully added.',
                });
            }, 200);
        }
    })
}

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="grid items-start gap-4 self-stretch grid-cols-12">
                <Dropdown
                    :inputName="'comm_type'"
                    :labelText="'Commission type'"
                    :inputArray="comm_type"
                    :dataValue="form.comm_type.value"
                    v-model="form.comm_type.value"
                    @onChange="setIsRate(form.comm_type.value)"
                    class="col-span-8"
                >
                </Dropdown>

                <TextInput
                    labelText="Rate"
                    :inputName="'comm_rate'"
                    iconPosition='right'
                    v-model="form.rate"
                    v-show="isRate"
                    @keypress="isValidNumberKey($event, true)"
                    class="col-span-4"
                >
                    <template #prefix>%</template>
                </TextInput>

                <TextInput
                    labelText="Amount"
                    :inputName="'comm_rate'"
                    iconPosition="left"
                    v-model="form.rate"
                    v-show="!isRate"
                    @keypress="isValidNumberKey($event, true)"
                    class="col-span-4"
                >
                    <template #prefix>RM</template>
                </TextInput>
            </div>

            <div class="flex items-start content-start gap-4 self-stretch">
                <Date 
                    :inputName="'effective_date'"
                    :labelText="'Effective date'"
                    :placeholder="'DD/MM/YYYY'"
                    :range="false"
                    v-model="form.effective_date"
                    
                />

                <Dropdown
                    :inputName="'recurring_on'"
                    :labelText="'Recurring on'"
                    :inputArray="recurringDates"
                    :dataValue="form.recurring_on.value"
                    v-model="form.recurring_on.value"
                >
                </Dropdown>
            </div>

            <TextInput 
                :labelText="'Monthly sales hits above'"
                :inputName="'monthly_sale'"
                :iconPosition="'left'"
                v-model="form.monthly_sale"
                @keypress="isValidNumberKey($event, true)"
            >
                <template #prefix>RM</template>
            </TextInput>

            <MultiSelect
                :inputArray="waiters"
                :inputName="'entitled'"
                :labelText="'Employee entitled to achieve this commission'"
                :withImages="true"
                :disabled="!waiters.length"
                v-model="form.entitled"
            >   
            </MultiSelect>

        </div>

        <div class="pt-6 flex justify-center items-end gap-4 self-stretch">
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
        @close="stayModal"
        @leave="leaveModal"
    />
</template>

