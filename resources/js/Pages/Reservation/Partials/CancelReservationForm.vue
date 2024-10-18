<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue'
import { useCustomToast } from '@/Composables/index.js';
import { cancelTypes } from '@/Composables/constants.js';
import Textarea from '@/Components/Textarea.vue';

const props = defineProps({
    reservation: Object,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchReservations']);

const form = useForm({
    handled_by: userId.value,  
    cancel_type: '',
    remark: '',
});

const submit = () => { 
    form.put(route('reservations.cancelReservation', props.reservation.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: "Selected reservation has been cancelled.",
                });
                form.reset();
            }, 200);
            emit('fetchReservations');
            emit('close');
        },
    })
};

const isFormValid = computed(() => {
    return ['cancel_type'].every(field => form[field]) && !form.processing;
});

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-y-4 items-start self-stretch">
                <Dropdown
                    inputName="cancel_type"
                    labelText="Select Cancellation Reason"
                    :inputArray="cancelTypes"
                    :errorMessage="form.errors?.cancel_type || ''"
                    v-model="form.cancel_type"
                />
                <Textarea
                    :inputName="'remark'"
                    :labelText="'Remarks'"
                    :placeholder="'Enter here'"
                    :rows="5"
                    class="col-span-full xl:col-span-4"
                    :errorMessage="form.errors?.remark || ''" 
                    v-model="form.remark"
                />
            </div>

            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    type="button"
                    variant="tertiary"
                    size="lg"
                    @click="$emit('close')"
                >
                    Maybe Later
                </Button>
                <Button
                    size="lg"
                    :disabled="!isFormValid"
                >
                    Cancel Reservation
                </Button>
            </div>
        </div>
    </form>
</template>
