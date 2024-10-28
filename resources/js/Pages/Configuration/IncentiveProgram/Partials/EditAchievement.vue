<script setup>
import Button from '@/Components/Button.vue';
import Date from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast, useInputValidator } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed, ref } from 'vue';

const props = defineProps({
    selectedIncent: {
        type: Object,
        required: true,
    },
})
const emit = defineEmits(['closeModal', 'getEmployeeIncent']);
const closeModal = () => {
    emit('closeModal');
    setTimeout(() => {
        emit('getEmployeeIncent');
    }, 200)
};

const comm_type = ref([
    { text: 'Fixed amount', value: 'fixed'},
    { text: 'Percentage of monthly sales', value: 'percentage'}
]);
const isRate = ref(props.selectedIncent.isRate);
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator()

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

const isFormValid = computed(() => {
    return ['comm_type', 'rate', 'effective_date', 'recurring_on', 'monthly_sale'].every(field => form[field]);
})

const defaultCommType = comm_type.value.find(item => item.value === props.selectedIncent.type) || comm_type.value[0];
const defaultRecurringOn = recurringDates.value.find(item => item.value === props.selectedIncent.recrurring_on) || recurringDates.value[0];
const defaultEffective = dayjs(props.selectedIncent.effective_date).add(1, 'month').format('DD/MM/YYYY')

const form = useForm({
    id: props.selectedIncent.id,
    comm_type: defaultCommType,
    rate: props.selectedIncent.type === 'fixed'
        ? props.selectedIncent.rate.toString()
        : (props.selectedIncent.rate * 100).toString(),
    effective_date: defaultEffective,
    recurring_on: defaultRecurringOn,
    monthly_sale: props.selectedIncent.monthly_sale.slice(0,-3),
})

const submit = () => {
    form.post(route('configurations.editAchievement'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeModal();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Achievement detail edited.',
                    detail: `Changes will take effect from ${form.effective_date}`,
                });
            }, 200);
            form.reset();
        }
    })
}

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
                    :hintText="'Changes will take effect only starting from next month.'"
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
                Save Changes
            </Button>
        </div>
    </form>
</template>

