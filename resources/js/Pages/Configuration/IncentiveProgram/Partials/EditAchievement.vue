<script setup>
import Button from '@/Components/Button.vue';
import Date from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast, useInputValidator } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    selectedIncent: {
        type: Object,
        required: true,
    },
})
const emit = defineEmits(['isDirty', 'closeModal', 'getEmployeeIncent']);
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator()
const closeModal = () => {
    emit('closeModal');
}

const stayModal = () => {
    emit('stay');
}

const leaveModal = () => {
    emit('leave');
}

const unsaved = (status) => {
    emit('closeModal', status)
}

const isRate = ref(props.selectedIncent.isRate);
const selectedIncent = ref(props.selectedIncent);
const isUnsavedChangesOpen = ref(false);
const comm_type = ref([
    { text: 'Fixed amount', value: 'fixed'},
    { text: 'Percentage of monthly sales', value: 'percentage'}
]);

const recurringDates = ref([...Array(31)].map((_, i) => {
    const day = i + 1;
    let suffix;

    if (day % 10 === 1 && day % 100 !== 11) {
        suffix = 'st';
    } else if (day % 10 === 2 && day % 100 !== 12) {
        suffix = 'nd';
    } else if (day % 10 === 3 && day % 100 !== 13) {
        suffix = 'rd';
    } else {
        suffix = 'th';
    }

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
    return ['comm_type', 'rate', 'effective_date', 'monthly_sale'].every(field => form[field]);
})

const form = useForm({
    id: selectedIncent.value.id,
    comm_type: comm_type.value.find(item => item.value === selectedIncent.value.type) || comm_type.value[0],
    rate: selectedIncent.value.type === 'fixed'
        ? selectedIncent.value.rate.toString()
        : parseFloat((selectedIncent.value.rate * 100).toFixed(2)).toString(),
    effective_date: dayjs(selectedIncent.value.effective_date).add(1, 'month').date(props.selectedIncent.recurring_on).toDate(),
    // recurring_on: recurringDates.value.find(item => item.value === selectedIncent.value.recurring_on) || recurringDates.value[0],
    monthly_sale: parseFloat(selectedIncent.value.monthly_sale).toFixed(2),
});


const submit = () => {
    form.effective_date = form.effective_date ? dayjs(form.effective_date).format('YYYY-MM-DD HH:mm:ss') : '';
    let toastMessage = dayjs(form.effective_date).format('DD/MM/YYYY');
    form.post(route('configurations.editAchievement'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Achievement detail edited.',
                    detail: `Changes will take effect from '${toastMessage}'.`,
                });
            }, 200);
            unsaved('leave');
            form.reset();
            emit('getEmployeeIncent', selectedIncent.value.id);
        }
    })
}

watch(() => props.selectedIncent, (newValue) => {
    selectedIncent.value = newValue;
}, { immediate: true });

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
                    v-model="form.comm_type.value"
                    @onChange="setIsRate(form.comm_type.value)"
                >
                </Dropdown>

                <TextInput
                    labelText="Rate"
                    :inputName="'comm_rate'"
                    :iconPosition="isRate ? 'right' : 'left'"
                    v-model="form.rate"
                    @keypress="isValidNumberKey($event, true)"
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
                    :hintText="'Changes will take effect only starting from next month.'"
                    v-model="form.effective_date"
                />

                <TextInput 
                    :labelText="'Monthly sales hits above'"
                    :inputName="'monthly_sale'"
                    :iconPosition="'left'"
                    v-model="form.monthly_sale"
                    @keypress="isValidNumberKey($event, true)"
                >
                    <template #prefix>RM</template>
                </TextInput>
                <!-- <Dropdown
                    :inputName="'recurring_on'"
                    :labelText="'Recurring on'"
                    :inputArray="recurringDates"
                    :dataValue="form.recurring_on.value"
                    v-model="form.recurring_on.value"
                /> -->
            </div>
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
                Save Changes
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
    >
    </Modal>
</template>

