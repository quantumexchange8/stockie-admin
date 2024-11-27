<script setup>
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import DateInput from '@/Components/Date.vue'
import { useCustomToast } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    reservation: Object,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)
const isUnsavedChangesOpen = ref(false);

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchReservations', 'isDirty']);

const unsaved = (status) => {
    emit('close', status);
}

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
            unsaved('leave');
        },
    })
};

const isFormValid = computed(() => ['new_reservation_date'].every(field => form[field]) && !form.processing);

watch(form, (newValue) => emit('isDirty', newValue.isDirty))

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
                    @click="unsaved('close')"
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
    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :closeable="true"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    >
    </Modal>
</template>
