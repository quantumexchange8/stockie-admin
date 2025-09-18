<script setup>
import Button from '@/Components/Button.vue';
import DragDropImage from '@/Components/DragDropImage.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { wTrans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    }
})
const emit = defineEmits(['close', 'isDirty', 'update:users']);
const capitilize = (string) => {
    return (string).charAt(0).toUpperCase() + (string).slice(1);
}

const initialForm = ref({
    role_id: props.user.role_id,
    full_name: props.user.full_name,
    email: props.user.email,
    position: capitilize(props.user.position),
});
const isDirty = ref(false);
const { showMessage } = useCustomToast();


const close = (status) => {
    emit('close', status);
}

const editForm = useForm({
    id: props.user.id,
    role_id: props.user.role_id,
    full_name: props.user.full_name,
    email: props.user.email,
    position: capitilize(props.user.position),
    password: '',
    image: props.user.image ? props.user.image : '',
})

const editDetails = async () => {
    editForm.processing = true;
    editForm.clearErrors();

    try {
        // editForm.put('admin-user/edit-admin-details', {
        //     preserveScroll: true,
        //     preserveState: 'errors',
        //     onSuccess: () => {
        //         showMessage({
        //             severity: 'success',
        //             summary: 'Successfully edited.',
        //         });
        //     }
        // })

        const response = await axios.post('admin-user/edit-admin-details', editForm, { headers: { 'Content-Type': 'multipart/form-data' } });

        showMessage({
            severity: 'success',
            summary: wTrans('public.toast.edit_success'),
        });

        emit('update:users', response.data);
        emit('close', 'leave');
        editForm.reset();
        editForm.clearErrors();

    } catch (error) {
        if (error.response && error.response.data.errors) {
            editForm.setError(error.response.data.errors); 
            console.error('An unexpected error occurred:', error);
        }
    } finally {
        editForm.processing = false; 
    }
}

const isFormValid = computed(() => {
    return ['role_id', 'full_name', 'position', 'email'].every(field => editForm[field]);
})

watch((editForm), (newValue) => {
    isDirty.value = newValue.role_id !== initialForm.value.role_id ||
                    newValue.full_name !== initialForm.value.full_name ||
                    newValue.email !== initialForm.value.email ||
                    newValue.position !== initialForm.value.position;

    emit('isDirty', isDirty.value)
})

</script>

<template>
    <form @submit.prevent="editDetails">
        <div class="flex items-start gap-6 self-stretch">
            <DragDropImage 
                :errorMessage="editForm.errors?.image ? editForm.errors.image[0] :''"
                :inputName="'image'"
                v-model="editForm.image"
                class="!max-w-[320px] !h-[320px]"
            />

            <div class="flex flex-col items-start gap-4 self-stretch w-full pr-1 max-h-[calc(100dvh-22rem)] overflow-y-auto scrollbar-webkit scrollbar-thin">
                <TextInput 
                    :labelText="$t('public.field.id')"
                    :placeholder="'e.g. 00000'"
                    :required="true"
                    :inputName="'role_id'"
                    :errorMessage="editForm.errors?.role_id ? editForm.errors.role_id[0] : ''"
                    v-model="editForm.role_id"
                />

                <TextInput 
                    :labelText="$t('public.field.password')"
                    :placeholder="$t('public.login.password_placeholder')"
                    :required="true"
                    :inputName="'password'"
                    :inputType="'password'"
                    :errorMessage="editForm.errors?.password ? editForm.errors.password[0] : ''"
                    v-model="editForm.password"
                />

                <TextInput 
                    :labelText="$t('public.field.name')"
                    :placeholder="'e.g. John Doe'"
                    :required="true"
                    :inputName="'full_name'"
                    :errorMessage="editForm.errors?.full_name ? editForm.errors.full_name[0] : ''"
                    v-model="editForm.full_name"
                />
                        
                <TextInput
                    :labelText="$t('public.field.email')"
                    :placeholder="'e.g. johndoe@gmail.com'"
                    inputId="email"
                    type="'email'"
                    required
                    :errorMessage="editForm.errors?.email ? editForm.errors.email[0] : ''"
                    v-model="editForm.email"
                />

                <TextInput 
                    :labelText="$t('public.field.title')"
                    :placeholder="'e.g. Accountant'"
                    :required="true"
                    :inputName="'position'"
                    :errorMessage="editForm.errors?.position ? editForm.errors.position[0] : ''"
                    v-model="editForm.position"
                />
            </div>
        </div>

        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
            <Button
                :variant="'tertiary'"
                :type="'button'"
                :size="'lg'"
                @click="close('close')"
            >
                {{ $t('public.action.cancel') }}
            </Button>
            <Button
                :variant="'primary'"
                :type="'submit'"
                :size="'lg'"
                :disabled="editForm.processing || !isFormValid"
            >
                {{ $t('public.action.save') }}
            </Button>
        </div>
    </form>      
</template>

