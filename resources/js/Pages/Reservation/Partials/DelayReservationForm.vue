<script setup>
import dayjs from 'dayjs';
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import DateInput from '@/Components/Date.vue'
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    reservation: Object,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchReservations']);

const form = useForm({
    handled_by: userId.value,  
    new_reservation_date: '',
});

const submit = () => { 
    form.new_reservation_date = form.new_reservation_date ? dayjs(form.new_reservation_date).format('YYYY-MM-DD HH:mm:ss') : '';

    form.put(route('reservations.delayReservation', props.reservation.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: `Selected reservation has been delayed to ${dayjs(form.new_reservation_date).format('DD/MM/YYYY HH:mm')}.`,
                });
                form.reset();
            }, 200);
            emit('fetchReservations');
            emit('close');
        },
    })
};

const isFormValid = computed(() => ['new_reservation_date'].every(field => form[field]) && !form.processing);

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6">
            <DateInput 
                inputName="new_reservation_date"
                labelText="Delay to"
                placeholder="Select Date and Time"
                withTime
                :minDate="new Date()"
                :errorMessage="form.errors?.new_reservation_date || ''"
                v-model="form.new_reservation_date"
            />

            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    type="button"
                    variant="tertiary"
                    size="lg"
                    @click="$emit('close')"
                >
                    Cancel
                </Button>
                <Button
                    size="lg"
                    :disabled="!isFormValid"
                >
                    Save
                </Button>
            </div>
        </div>
    </form>
</template>
