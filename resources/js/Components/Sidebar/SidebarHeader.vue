<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { sidebarState, rightSidebarState, useCustomToast } from '@/Composables'
import Modal from "@/Components/Modal.vue";
import { computed, onMounted, ref } from 'vue';
import DragDropImage from '../DragDropImage.vue';
import { EditIcon } from '../Icons/solid';
import TextInput from '../TextInput.vue';
import Button from '../Button.vue';

const emit = defineEmits(['close']);
const isAccountDetailOpen = ref(false);
const isEditModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const page = usePage();
const { showMessage } = useCustomToast();

const userName = computed(() => page.props.auth.user.data.full_name);
const userId = computed(() => page.props.auth.user.data.id);
const userImage = computed(() => page.props.auth.user.data.image);
const userRoleId = computed(() => page.props.auth.user.data.role_id);

const showAccountDetail = () => {
    isAccountDetailOpen.value = true;
    profileForm.id = userId;
}

const showEditModal = (name, id) => {
    isEditModalOpen.value = true;
    isAccountDetailOpen.value = false;
    form.name = name;
    form.id = id;
}

const closeEditModal = (status) => {
    // isAccountDetailOpen.value = true; //edit profile pic
    // isEditModalOpen.value = false; //edit name 
    
    switch(status) {
        case 'close': {
            if(form.isDirty){
                isUnsavedChangesOpen.value = true;
            } else {
                isEditModalOpen.value = false;
                isAccountDetailOpen.value = true;
            }
            break;
        }
        case 'stay':{
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave':{
            isUnsavedChangesOpen.value = false;
            isEditModalOpen.value = false;
            isAccountDetailOpen.value = true;
            break;
        }
    }
}

const closeModal = () => {
    isAccountDetailOpen.value = false;
}

const form = useForm({
    id: userId.value,
    name: userName.value,
})

const profileForm = useForm({
    image: userImage.value ? userImage.value : '',
    id: userId.value,
})

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
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
                :src="userImage ? userImage : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                alt=""
                class="rounded-[100px] object-contain shadow-[0px_0px_24.2px_0px_rgba(203,60,60,0.30)] 
                        flex w-[60px] h-[60px] justify-center items-center"
            >
            <!-- </div> -->
            <div class="flex flex-col">
                <p class="self-stretch text-primary-900 text-md font-medium  group-hover:text-primary-700">{{ userName }}</p>
                <p class="self-stretch text-primary-950 text-xs font-normal  group-hover:text-primary-800">ID: {{ userRoleId }}</p>
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
                    <span class="text-grey-900 items-center text-md font-medium">{{ userRoleId }}</span>
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
                        <span class="text-grey-900 items-center text-md font-medium">{{ userName }}</span>
                    </div>
                    <EditIcon 
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditModal(userName, userId)"
                    />
                </div>
            </div>
        </form>
    </Modal>

    <Modal
        :title="'Edit display name'"
        :show="isEditModalOpen"
        :maxWidth="'xs'"
        @close="closeEditModal('close')"
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
                    @click="closeEditModal('close')"
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeEditModal('stay')"
            @leave="closeEditModal('leave')"
        >
        </Modal>
    </Modal>
</template>
