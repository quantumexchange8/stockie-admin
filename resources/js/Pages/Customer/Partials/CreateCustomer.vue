<script setup>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import { useCustomToast, usePhoneUtils } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';
import Toast from '@/Components/Toast.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    // errors: Object,
    // itemCategoryArr: {
    //     type: Array,
    //     default: () => [],
    // },
    // categoryArr: {
    //     type: Array,
    //     default: () => [],
    // },
});

const emit = defineEmits(['close', 'closeAll', 'isDirty', 'update:customerListing']);

const { showMessage } = useCustomToast();
const { transformPhone, formatPhoneInput } = usePhoneUtils();

const isUnsavedChangesOpen = ref(false);

const form = useForm({
    full_name: '',
    phone: '',
    phone_temp: "",
    email: '',
    password: '',
});

const unsaved = (status) => {
    emit('close', status);
    emit('closeAll');
}

const submit = async () => { 
    form.processing = true;
    form.phone = form.phone_temp ? transformPhone(form.phone_temp) : '';
    try {
        const response = await axios.post('/customer/', form);

        showMessage({
            severity: 'success',
            summary: wTrans('public.toast.customer_added'),
        });

        emit('update:customerListing', response.data);
        form.reset();
        unsaved('leave');
    } catch (error) {
        if (error.response) {
            form.setError(error.response.data.errors);
            console.error('An unexpected error occurred:', error);
        }
    } finally {
        form.processing = false;
    }
};

const requiredFields = ['full_name', 'password'];

const isFormValid = computed(() => requiredFields.every(field => form[field]) && !form.processing);

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="submit">
        <div class="flex flex-col items-start gap-8 pl-1 pr-2 py-1 self-stretch max-h-[calc(100dvh-14rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <TextInput
                required
                :inputName="'full_name'"
                :labelText="$t('public.field.name')"
                :placeholder="'e.g. Tan Mei Wah'"
                :errorMessage="form.errors && form.errors.full_name ? form.errors.full_name[0] : ''"
                v-model="form.full_name"
            />
            
            <TextInput
                inputName="phone"
                :inputType="'number'"
                :labelText="$t('public.field.phone_no')"
                placeholder="12 345 1234"
                :iconPosition="'left'"
                :errorMessage="form.errors && form.errors.phone ? form.errors.phone[0] : ''"
                class="col-span-full sm:col-span-6 [&>div:nth-child(2)>input]:text-left"
                v-model="form.phone_temp"
                @input="formatPhoneInput"
            >
                <template #prefix> +60 </template>
            </TextInput>

            <TextInput
                :inputName="'email'"
                :labelText="$t('public.field.email')"
                :placeholder="'e.g. meiwah@gmail.com'"
                :errorMessage="form.errors && form.errors.email ? form.errors.email[0] : ''"
                v-model="form.email"
            />

            <TextInput
                required
                :inputName="'password'"
                :labelText="$t('public.field.password')"
                :placeholder="$t('public.field.password')"
                :inputType="'password'"
                :errorMessage="form.errors && form.errors.password ? form.errors.password[0] : ''"
                v-model="form.password"
            />
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                :disabled="form.processing"
                @click="unsaved('close')"
            >
                {{ $t('public.action.cancel') }}
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
            >
                {{ $t('public.action.add') }}
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
