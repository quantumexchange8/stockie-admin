<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue'
import { useCustomToast } from '@/Composables/index.js';
import Textarea from '@/Components/Textarea.vue';
import Modal from '@/Components/Modal.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    reservation: Object,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id);
const isUnsavedChangesOpen = ref(false);

const cancelTypes = computed(() => [
    { text: wTrans('public.reservation.change_plan'), value: 'Change of plan' },
    { text: wTrans('public.reservation.feeling_unwell'), value: 'Feeling unwell' },
    { text: wTrans('public.reservation.bad_weather'), value: 'Bad weather' },
    { text: wTrans('public.reservation.work_conflicts'), value: 'Work conflicts' },
    { text: wTrans('public.reservation.family_emergency'), value: 'Family emergency' },
    { text: wTrans('public.reservation.forgotten_reservation'), value: 'Forgotten reservation' },
    { text: wTrans('public.reservation.other_reason'), value: 'Others (specify under Remarks)' },
]);

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchReservations', 'isDirty']);

const unsaved = (status) => {
    emit('close', status)
};

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
                    summary: wTrans('public.toast.cancelled_rsvp_success'),
                });
                form.reset();
            }, 200);
            emit('fetchReservations');
            unsaved('leave');
        },
    })
};

const isFormValid = computed(() => {
    return ['cancel_type'].every(field => form[field]) && !form.processing;
});

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-y-4 items-start self-stretch">
                <Dropdown
                    inputName="cancel_type"
                    :labelText="$t('public.field.cancel_rsvp_reason')"
                    :inputArray="cancelTypes"
                    :errorMessage="form.errors?.cancel_type || ''"
                    v-model="form.cancel_type"
                />
                <Textarea
                    :inputName="'remark'"
                    :labelText="$t('public.remark')"
                    :placeholder="$t('public.enter_here')"
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
                    @click="unsaved('close')"
                >
                    {{ $t('public.action.maybe_later') }}
                </Button>
                <Button
                    size="lg"
                    :disabled="!isFormValid"
                >
                    {{ $t('public.action.cancel_reservation') }}
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
