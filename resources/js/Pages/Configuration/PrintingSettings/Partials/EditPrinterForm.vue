<script setup>
import Button from '@/Components/Button.vue';
import Date from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { kickCashDrawerOptions } from '@/Composables/constants';
import RadioButton from '@/Components/RadioButton.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    printer: Object,
})

const emit = defineEmits(['isDirty', 'closeModal', 'update:printers']);

const { showMessage } = useCustomToast();

const unsaved = (status) => {
    emit('closeModal', status)
}

const selectedPrinter = ref(props.printer);
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    name: selectedPrinter.value.name,
    ip_address: selectedPrinter.value.ip_address,
    port_number: selectedPrinter.value.port_number,
    temp_port: (selectedPrinter.value.port_number).toString(),
    kick_cash_drawer: !!selectedPrinter.value.kick_cash_drawer,
});

const submit = async () => {
    form.processing = true;
    try {
        form.port_number = Number(form.temp_port);

        const { data } = await axios.post(`/configurations/editPrinter/${selectedPrinter.value.id}`, form);

        emit('update:printers', data);
        unsaved('leave');
        setTimeout(() => {
            form.reset();
            showMessage({ 
                severity: 'success',
                summary: 'Printer details has been edited.',
            });
        }, 200);

    } catch (error) {
        form.errors = error.response.data.errors;

    } finally {
        form.processing = false;
    }
}

const getKickCashDrawerOptions = computed(() => {
    return kickCashDrawerOptions.map((opt) => ({
        ...opt,
        text: wTrans(opt.text).value,
    }));
});

const isFormValid = computed(() => {
    return ['name', 'ip_address', 'port_number'].every(field => form[field]) && !form.processing;
})

watch(() => props.printer, (newValue) => {
    selectedPrinter.value = newValue;
}, { immediate: true });

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="flex flex-col items-start gap-6 max-h-[calc(100dvh-18rem)] pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
            <TextInput
                required
                disabled
                :inputName="'name'"
                :labelText="'Printer Name'"
                :placeholder="'eg: Cashier'"
                :errorMessage="form.errors.name ? form.errors.name[0] : ''"
                v-model="form.name"
            />
            <TextInput
                required
                :inputName="'ip_address'"
                :labelText="'IP'"
                :placeholder="'eg: 128.999.092'"
                :errorMessage="form.errors.ip_address ? form.errors.ip_address[0] : ''"
                v-model="form.ip_address"
            />
            <TextInput
                required
                :inputType="'number'"
                :inputName="'port_number'"
                :labelText="'Port'"
                :placeholder="'eg: 9100'"
                :errorMessage="form.errors.port_number ? form.errors.port_number[0] : ''"
                v-model="form.temp_port"
            />
            <RadioButton
                :optionArr="getKickCashDrawerOptions"
                :checked="form.kick_cash_drawer"
                v-model:checked="form.kick_cash_drawer"
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

