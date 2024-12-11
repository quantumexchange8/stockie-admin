<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import RadioButton from '@/Components/RadioButton.vue';
import { tableType } from '@/Composables/constants';
import { useCustomToast, useInputValidator } from '@/Composables';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    errors: Object,
    zonesArr: {
        type: Array,
        default: () => [],
    }
});
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(['close', 'isDirty']);
const zones = ref();
const isUnsavedChangesOpen = ref(false);

watch(() => props.zonesArr, (newValue) => {
    zones.value = newValue ? newValue : {};
}, { immediate: true });

const form = useForm({
    // type: '',
    table_no: '',
    seat: '',
    zone_id: '',
});

const formSubmit = () => { 
    form.post(route('tableroom.add-table'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            emit('close', 'leave');
            form.reset();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'New table has been successfully added.',
                });
            }, 200)
        },
        onError: (errors) => {
            console.error('Form submission error, ', errors);
        }
    })
};

const unsaved = (status) => {
    emit('close', status)
}

const requiredFields = ['table_no', 'seat', 'zone_id'];

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
});

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="gap-6 pl-1 pr-2 py-1 max-h-[700px] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <!-- <div class="flex items-start gap-10">
                    <RadioButton
                        :optionArr="tableType"
                        :checked="form.type"
                        v-model:checked="form.type"
                    />
                </div> -->
                <TextInput
                    :inputName="'table_no'"
                    :labelText="'Table / Room No.'"
                    :placeholder="'eg: 1'"
                    :errorMessage="form.errors?.table_no || ''"
                    :maxlength="4"
                    v-model="form.table_no"
                    class="col-span-full md:col-span-8"
                />
                <div class="grid grid-cols-2 md:grid-cols-12 gap-3 self-stretch">
                    <TextInput
                        :inputName="'seat'"
                        :labelText="'No. of Seats Available'"
                        :placeholder="'number only (eg: 6)'"
                        :errorMessage="form.errors?.seat || ''"
                        @keypress="isValidNumberKey($event, false)"
                        v-model="form.seat"
                        class="col-span-full md:col-span-6"
                    />
                    <Dropdown
                        :inputName="'zone_id'"
                        :labelText="'Select Zone'"
                        :inputArray="zones"
                        :errorMessage="form.errors?.zone_id || ''"
                        v-model="form.zone_id"
                        class="col-span-full md:col-span-6"
                    />
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="unsaved('close')"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid || form.processing"
                :type="'submit'"
                :class="{ 'opacity-25': form.processing }"
            >
                Add
            </Button>
        </div>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="unsaved('stay')"
            @leave="unsaved('leave')"
        />
    </form>
</template>

