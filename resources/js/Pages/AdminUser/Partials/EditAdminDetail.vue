<script setup>
import Button from '@/Components/Button.vue';
import DragDropImage from '@/Components/DragDropImage.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
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
    position: capitilize(props.user.position),
    password: '',
    image: props.user.image ? props.user.image : '',
})

const editDetails = async () => {
    try {
        editForm.put('admin-user/edit-admin-details', {
            preserveScroll: true,
            preserveState: 'errors',
            onSuccess: () => {
                showMessage({
                    severity: 'success',
                    summary: 'Successfully edited.',
                });
            }
        })
        // const response = await axios.put('/admin-user/edit-admin-details', editForm);
        // emit('update:users', response.data);
        // emit('close', 'leave');
        // showMessage({
        //     severity: 'success',
        //     summary: 'Successfully edited.',
        // });
        // editForm.reset();
    } catch (error) {
        if (error.response && error.response.data.errors) {
            deleteForm.errors = error.response.data.errors; 
        }
        console.error(error);
    }
}

const isFormValid = computed(() => {
    return ['role_id', 'full_name', 'position', 'password'].every(field => editForm[field]);
})

watch((editForm), (newValue) => {
    isDirty.value = newValue.role_id !== initialForm.value.role_id ||
                    newValue.full_name !== initialForm.value.full_name ||
                    newValue.position !== initialForm.value.position;

    emit('isDirty', isDirty.value)
})

</script>

<template>
    <form @submit.prevent="editDetails">
        <div class="flex items-start gap-6 self-stretch">
            <DragDropImage 
                :errorMessage="editForm.errors?.image"
                :inputName="'image'"
                v-model="editForm.image"
                class="!max-w-[320px] !h-[320px]"
            />

            <div class="flex flex-col items-start gap-4 self-stretch w-full">
                <TextInput 
                    :labelText="'ID'"
                    :required="true"
                    :inputName="'role_id'"
                    :errorMessage="editForm.errors?.role_id"
                    v-model="editForm.role_id"
                />

                <TextInput 
                    :labelText="'Name'"
                    :required="true"
                    :inputName="'full_name'"
                    :errorMessage="editForm.errors?.full_name"
                    v-model="editForm.full_name"
                />

                <TextInput 
                    :labelText="'Title'"
                    :required="true"
                    :inputName="'position'"
                    :errorMessage="editForm.errors?.position"
                    v-model="editForm.position"
                />

                <TextInput 
                    :labelText="'Password'"
                    :required="true"
                    :inputName="'password'"
                    :inputType="'password'"
                    :errorMessage="editForm.errors?.password"
                    v-model="editForm.password"
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
                Cancel
            </Button>
            <Button
                :variant="'primary'"
                :type="'submit'"
                :size="'lg'"
                :disabled="editForm.processing || !isFormValid"
            >
                Save
            </Button>
        </div>
    </form>      
</template>

