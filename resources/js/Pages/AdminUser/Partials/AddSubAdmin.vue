<script setup>
import Button from '@/Components/Button.vue';
import DragDropImage from '@/Components/DragDropImage.vue';
import { ArrowRightIcon } from '@/Components/Icons/solid';
import TextInput from '@/Components/TextInput.vue';
import Toggle from '@/Components/Toggle.vue';
import { useCustomToast } from '@/Composables';
import { permissionList } from '@/Composables/constants';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const form = useForm({
    image: '',
    role_id: '',
    full_name: '',
    position: '',
    password: '',
    permission: [],
    email: '',
})
const emit = defineEmits(['close', 'isDirty', 'update:users']);
const { showMessage } = useCustomToast();
const isAddAdminClicked = ref(false);

const addAdmin = async () => {
    form.processing = true;
    form.clearErrors();
    
    try {
        // form.post('admin-user/add-sub-admin', {
        //     preserveScroll: true,
        //     preserveState: 'errors',
        //     onSuccess: () => {
        //         showMessage({
        //             severity: 'success',
        //             summary: 'Successfully added.'
        //         });
        //         emit('close', 'leave');
        //         setTimeout(() => {
        //             form.processing = false; 
        //         }, 2000);
        //     }
        // })

        const response = await axios.post('admin-user/add-sub-admin', form, { headers: { 'Content-Type': 'multipart/form-data' } });

        showMessage({
            severity: 'success',
            summary: 'Successfully added.'
        });

        emit('update:users', response.data);
        form.reset();
        form.clearErrors();
        emit('close', 'leave');

    } catch (error) {
        if (error.response && error.response.data.errors) {
            form.setError(error.response.data.errors); 
            console.error('An unexpected error occurred:', error);
        }
        isAddAdminClicked.value = false;
    } finally {
        form.processing = false; 
    }
};

const addAdminClicked = () => {
    isAddAdminClicked.value = !isAddAdminClicked.value;
}

const updatePermission = (permission) => {
    const index = form.permission.indexOf(permission);

    if (index === -1) {
        form.permission.push(permission);
    } else {
        form.permission.splice(index, 1);
    }
}

const hasPermissionSelected = computed(() => {
    const permLength = form.permission.length;
    const hasPerm = permLength > 0;
    
    if (!hasPerm) return false;

    if (permLength === 1) {
        const hasFreeUpTablePerm = form.permission[0] === 'free-up-table';

        if (hasFreeUpTablePerm) return false;
    }

    return true;
})

const isFormValid = computed(() => {
    return ['role_id', 'password', 'full_name', 'email', 'position', 'image'].every(field => form[field]);
})

</script>

<template>
    <form autocomplete="off" @submit.prevent="addAdmin">
        <transition
            enter-active-class="transition duration-500 ease"
            leave-active-class="transition duration-500 ease"
            enter-from-class="opacity-0 translate-x-8"
            enter-to-class="opacity-100 translate-x-0"
            leave-from-class="opacity-100 duration-500 translate-x-0"
            leave-to-class="opacity-0 translate-x-8"
            mode="out-in"
        >
        <!-- Step 1: Set up sub-admin detail -->
            <div class="flex flex-col gap-6 items-start w-full" v-if="!isAddAdminClicked">
                <!-- header -->
                <div class="flex items-start gap-6">
                    <div class="flex items-center gap-3">
                        <div class="flex rounded-full justify-center items-center bg-primary-900 size-5">
                            <span class="text-white text-xs font-normal">1</span>
                        </div>
                        <span class="text-grey-950 text-base font-normal">Set up sub-admin detail</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex rounded-full justify-center items-center bg-grey-200 size-5">
                            <span class="text-white text-xs font-normal text-center shrink-0">2</span>
                        </div>
                        <span class="text-grey-300 text-base font-normal">Control access</span>
                    </div>
                </div>
            
                <!-- form -->
                <div class="flex items-start gap-6 self-stretch w-full pb-3">
                    <DragDropImage 
                        :errorMessage="form.errors?.image ? form.errors.image[0] : ''"
                        :inputName="'image'"
                        v-model="form.image"
                        class="!max-w-[320px] !h-[320px]"
                    />

                    <div class="flex flex-col items-start gap-4 self-stretch w-full pr-1 max-h-[calc(100dvh-22rem)] overflow-y-auto scrollbar-webkit scrollbar-thin">
                        <TextInput 
                            :labelText="'ID'"
                            :placeholder="'e.g. 000001'"
                            :required="true"
                            :inputName="'role_id'"
                            :errorMessage="form.errors?.role_id ? form.errors.role_id[0] : ''"
                            v-model="form.role_id"
                        />

                        <TextInput 
                            :labelText="'Password'"
                            :placeholder="'Enter your password here'"
                            :inputName="'password'"
                            :inputType="'password'"
                            :errorMessage="form.errors?.password ? form.errors.password[0] : ''"
                            v-model="form.password"
                        />

                        <TextInput
                            :labelText="'Name'"
                            :placeholder="'e.g. John Doe'"
                            :required="true"
                            :inputName="'full_name'"
                            :errorMessage="form.errors?.full_name ? form.errors.full_name[0] : ''"
                            v-model="form.full_name"
                        />
                        
                        <TextInput
                            labelText="Email"
                            :placeholder="'e.g. johndoe@gmail.com'"
                            inputId="email"
                            type="'email'"
                            required
                            :errorMessage="form.errors?.email ? form.errors.email[0] : ''"
                            v-model="form.email"
                        />

                        <TextInput 
                            :labelText="'Title'"
                            :placeholder="'e.g. Accountant'"
                            :required="true"
                            :inputName="'position'"
                            :errorMessage="form.errors?.position ? form.errors.position[0] : ''"
                            v-model="form.position"
                        />
                    </div>
                </div>
                <Button
                    :variant="'primary'"
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'right'"
                    :disabled="form.processing || !isFormValid"
                    @click="addAdminClicked"
                >
                    <template #icon>
                        <ArrowRightIcon class="size-5"/>
                    </template>
                    Next
                </Button>
            </div>

            <!-- Step 2: Control access -->
            <div class="flex flex-col gap-6 items-start w-full" v-else>
                <!-- header -->
                <div class="flex items-start gap-6">
                    <div class="flex items-center gap-3">
                        <div class="flex rounded-full justify-center items-center bg-primary-900 size-5">
                            <span class="text-white text-xs font-normal">1</span>
                        </div>
                        <span class="text-grey-950 text-base font-normal">Set up sub-admin detail</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex rounded-full justify-center items-center bg-primary-900 size-5">
                            <span class="text-white text-xs font-normal">2</span>
                        </div>
                        <span class="text-grey-950 text-base font-normal">Control access</span>
                    </div>
                </div>
            
                <!-- form -->
                <div class="flex flex-col max-h-[320px] items-start self-stretch divide-y-[1px] divide-grey-100 overflow-y-auto scrollbar-webkit scrollbar-thin">
                    <div class="flex py-4 pr-5 justify-between items-center self-stretch" v-for="permission in permissionList">
                        <span class="text-grey-950 text-base font-medium">Allow '{{ permission.text }}' access</span>
                        <Toggle
                            :checked="!!form.permission.find(exist_permission => exist_permission === permission.value)"
                            :disabled="form.processing"
                            @change="updatePermission(permission.value)"
                        >
                        </Toggle>
                    </div>
                </div>

                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :variant="'tertiary'"
                        :size="'lg'"
                        :type="'button'"
                        @click="addAdminClicked"
                    >
                        Go back
                    </Button>
                    <Button
                        :variant="'primary'"
                        :size="'lg'"
                        :type="'submit'"
                        :disabled="form.processing || !isFormValid || !hasPermissionSelected"
                    >
                        Add
                    </Button>
                </div>
            </div>
        </transition>
    </form>
</template>

