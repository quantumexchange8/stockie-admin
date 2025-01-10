<script setup>
import Button from '@/Components/Button.vue';
import DragDropImage from '@/Components/DragDropImage.vue';
import { ArrowRightIcon } from '@/Components/Icons/solid';
import TextInput from '@/Components/TextInput.vue';
import Toggle from '@/Components/Toggle.vue';
import { useCustomToast } from '@/Composables';
import { permissionList } from '@/Composables/constants';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    image: '',
    role_id: '',
    full_name: '',
    position: '',
    password: '',
    permission: [],
    email: '',
})
const emit = defineEmits(['close', 'isDirty']);
const { showMessage } = useCustomToast();
const isAddAdminClicked = ref(false);
const isProcessing = ref(false);

const addAdmin = () => {
    isProcessing.value = true;
    try {
        form.post('admin-user/add-sub-admin', {
            preserveScroll: true,
            preserveState: 'errors',
            onSuccess: () => {
                showMessage({
                    severity: 'success',
                    summary: 'Successfully added.'
                });
                emit('close', 'leave');
                setTimeout(() => {
                    isProcessing.value = false; 
                }, 2000);
            }
        })
    } catch (error) {
        console.error(error);
        isAddAdminClicked.value = false;
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
                        :errorMessage="form.errors?.image"
                        :inputName="'image'"
                        v-model="form.image"
                        class="!max-w-[320px] !h-[320px]"
                    />

                    <div class="flex flex-col items-start gap-4 self-stretch w-full">
                        <TextInput 
                        :labelText="'ID'"
                        :required="true"
                        :inputName="'role_id'"
                        :errorMessage="form.errors?.role_id"
                        v-model="form.role_id"
                    />

                    <TextInput
                        :labelText="'Name'"
                        :required="true"
                        :inputName="'full_name'"
                        :errorMessage="form.errors?.full_name"
                        v-model="form.full_name"
                    />

                    <TextInput 
                        :labelText="'Title'"
                        :required="true"
                        :inputName="'position'"
                        :errorMessage="form.errors?.position"
                        v-model="form.position"
                    />

                    <TextInput 
                        :labelText="'Password'"
                        :inputName="'password'"
                        :inputType="'password'"
                        :errorMessage="form.errors?.password"
                        v-model="form.password"
                    />
                    </div>
                </div>
                <Button
                    :variant="'primary'"
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'right'"
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
                            :disabled="form.processing || isProcessing"
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
                    >
                        Add
                    </Button>
                </div>
            </div>
        </transition>
    </form>
</template>

