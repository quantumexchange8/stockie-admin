<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { sidebarState, rightSidebarState, useCustomToast } from '@/Composables'
import Modal from "@/Components/Modal.vue";
import { onMounted, ref } from 'vue';
import DragDropImage from '../DragDropImage.vue';
import { EditIcon } from '../Icons/solid';
import TextInput from '../TextInput.vue';
import Button from '../Button.vue';
import Toast from '../Toast.vue';


const props = defineProps ({
    user: {
        type: Object,
        required: true,
    },
})
const emit = defineEmits(['close']);
const isAccountDetailOpen = ref(false);
const isEditModalOpen = ref(false);
const { showMessage } = useCustomToast();


const showAccountDetail = () => {
    isAccountDetailOpen.value = true;
    profileForm.id = props.user.id;
}

const showEditModal = (name, id) => {
    isEditModalOpen.value = true;
    isAccountDetailOpen.value = false;
    form.name = name;
    form.id = id;
}

const closeEditModal = () => {
    isAccountDetailOpen.value = true;
    isEditModalOpen.value = false;
}

const closeModal = () => {
    isAccountDetailOpen.value = false;
}

const form = useForm({
    id: props.user.id,
    name: props.user.name,
})

const profileForm = useForm({
    image: props.user.image ? props.user.image : '',
    id: props.user.id,
})

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            emit('close');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Changes saved.',
                });
            }, 200);
        },
        onError: (errors) => {
            console.error('Form submission error. ', errors);
        }
    })
};

const changeProfilePic = () => {
    profileForm.post(route('profile.updateProfile'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            profileForm.reset();
            emit('close');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'You’ve successfully changed your profile picture.',
                });
            }, 200);
        },
        onError: (errors) => {
            console.error('Form submission error. ', errors);
        }
    })
};

</script>

<template>
    <div class="flex items-center justify-between flex-shrink-0 p-4 hover:bg-[#ffe1e261] hover:shadow-[-4px_-9px_36.4px_0px_rgba(199,57,42,0.05)] group cursor-pointer" v-show="sidebarState.isOpen" @click="showAccountDetail()">
        <div class="flex gap-[16px]">
            <!-- <div 
                class="rounded-[100px] bg-primary-900 shadow-[0px_0px_24.2px_0px_rgba(203,60,60,0.30)] 
                        flex w-[46px] pt-[7px] pr-[1.38px] pl-[2px] justify-center items-center"
            > -->
            <img 
                :src="props.user.image ? props.user.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                alt=""
                class="rounded-[100px] object-fit shadow-[0px_0px_24.2px_0px_rgba(203,60,60,0.30)] 
                        flex w-[46px] justify-center items-center"
            >
            <!-- </div> -->
            <div class="flex flex-col" v-if="props.user">
                <p class="self-stretch text-primary-900 text-md font-medium  group-hover:text-primary-700">{{ props.user.full_name }}</p>
                <p class="self-stretch text-primary-950 text-xs font-normal  group-hover:text-primary-800">ID: {{ props.user.role_id }}</p>
            </div>
        </div>
    </div>

    <!-- <Toast /> -->

    <Modal
        :maxWidth="'md'"
        :closeable="true"
        :show="isAccountDetailOpen"
        :title="'Account Detail'"
        @close="closeModal"
    >
        <form class="flex items-start gap-6 self-stretch" @submit.prevent="changeProfilePic">
            <DragDropImage
                :inputName="'image'"
                :errorMessage="profileForm.errors.image"
                v-model="profileForm.image"
                class="!size-[373px]"
                @change="changeProfilePic"
            />
            <div class="flex flex-col items-start flex-[1_0_0] divide-y divide-grey-100">
                <div class="w-full flex flex-col items-start gap-1 flex-[1_0_0] py-4">
                    <span class="text-grey-600 items-center text-sm font-medium">ID</span>
                    <span class="text-grey-900 items-center text-md font-medium">{{ props.user.role_id }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 flex-[1_0_0] py-4">
                    <span class="text-grey-600 items-center text-sm font-medium">Password</span>
                    <div class="flex items-center gap-[5px]">
                        <template v-for="n in 8" :key="n">
                            <div class="size-2 bg-grey-900 rounded-full"></div>
                        </template> 
                    </div>
                </div>
                <div class="flex items-center gap-6 self-stretch">
                    <div class="w-full flex flex-col items-start gap-1 flex-[1_0_0] py-4">
                        <span class="text-grey-600 items-center text-sm font-medium">Display name</span>
                        <span class="text-grey-900 items-center text-md font-medium">{{ props.user.name }}</span>
                    </div>
                    <EditIcon 
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditModal(props.user.name, props.user.id)"
                    />
                </div>
            </div>
        </form>
    </Modal>

    <Modal
        :title="'Edit display name'"
        :show="isEditModalOpen"
        :maxWidth="'xs'"
        @close="closeEditModal"
    >
    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <TextInput
            :inputName="'name'"
            :labelText="'Display name'"
            :errorMessage="form.errors?.name || ''"
            v-model="form.name"
        />
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="closeEditModal"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                :type="'submit'"
            >
                Save
            </Button>
        </div>
    </form>
    </Modal>
</template>
