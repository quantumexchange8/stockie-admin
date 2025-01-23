<script setup>
import Button from '@/Components/Button.vue';
import { DefaultIcon, PlusIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Tag from '@/Components/Tag.vue';
import Toggle from '@/Components/Toggle.vue';
import { useCustomToast } from '@/Composables';
import { permissionList } from '@/Composables/constants';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EditAdminDetail from './EditAdminDetail.vue';
import AddSubAdmin from './AddSubAdmin.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    }
})
const emit = defineEmits(['update:users']);

const users = ref(props.users);
const isDetailShow = ref(false);
const isDeleteShow = ref(false);
const isEditShow = ref(false);
const selectedUser = ref('');
const initialEditForm = ref({});
const isDirty = ref(false);
const isAddAdminDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const isAddAdminOpen = ref(false);
const isProcessing = ref(false);
const searchQuery = ref('');
const { showMessage } = useCustomToast();

const form = useForm({
    id: '',
    permission: '',
})

const editForm = useForm({
    id: '',
    role_id: '',
    full_name: '',
    position: '',
    password: '',
    image: '',
})

const capitilize = (string) => {
    return (string).charAt(0).toUpperCase() + (string).slice(1);
}

// const refetchAdminUsers = async () => {
//     try {
//         const response = axios.get(route('refetch-admin-users'));
//         // users.value = response.data;

//         emit('update:users', response.data);
//     } catch (error) {
//         console.error(error);
//     }
// }

const openDetail = (user) => {
    selectedUser.value = user;
    form.id = user.id;
    isDetailShow.value = true;

}

const closeDetail = () => {
    if(isDeleteShow.value === false && isEditShow.value === false){
        isDetailShow.value = false;
    } else if (isDeleteShow.value === true){
        isDeleteShow.value = false;
    } else if (isEditShow.value === true){
        isEditShow.value === false;
    }

}

const openDelete = () => {
    isDeleteShow.value = true;
}

const closeDelete = () => {
    isDeleteShow.value = false;
}

const openEdit = () => {
    isEditShow.value = true;
    editForm.id = selectedUser.value.id;
    editForm.role_id = selectedUser.value.role_id.toString();
    editForm.full_name = selectedUser.value.full_name;
    editForm.position = capitilize(selectedUser.value.position);
    editForm.password = '';
    editForm.image = selectedUser.value.image ? selectedUser.value.image : '';

    initialEditForm.value = {...editForm};
}

const closeEdit = (status) => {
    switch(status) {
        case 'close': {
            if(isDirty.value) isUnsavedChangesOpen.value = true;
            else isEditShow.value = false;
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isEditShow.value = false;
            isDirty.value = false;
            break;
        }
    }
}

const openAddAdmin = () => {
    isAddAdminOpen.value = true;
}

const closeAddAdmin = (status) => {
    switch(status){
        case 'close': {
            if(isAddAdminDirty.value) isUnsavedChangesOpen.value = true;
            else isAddAdminOpen.value = false;
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isAddAdminOpen.value = false;
            isAddAdminDirty.value = false;
            break;
        }
    }
}

const editPermission = async (permission) => {
    isProcessing.value = true;
    form.permission = permission;
    let hasPermission = !users.value
        .find(user => user.id === form.id)?.permissions
        .find(exist_permission => exist_permission.name === permission.value);
    try {
        // form.post('admin-user/edit-permission', {
        //     preserveScroll: true,
        //     preserveState: true,
        //     onSuccess: () => {
        //         showMessage({
        //             severity: 'success',
        //             summary: hasPermission ? `This admin now has access to ‘${permission.text}’.` : `Access to ‘${permission.text}’ has been restricted for this admin.`,
        //         })
        //         setTimeout(() => {
        //             isProcessing.value = false; 
        //         }, 2000);
        //         refetchAdminUsers();
        //     }
        // })
        const response = await axios.post(`/admin-user/edit-permission`, form);
        users.value = response.data;
        showMessage({
            severity: 'success',
            summary: hasPermission ? `This admin now has access to ‘${permission.text}’.` : `Access to ‘${permission.text}’ has been restricted for this admin.`,
        })
        setTimeout(() => {
            isProcessing.value = false; 
        }, 2000);
        form.reset('permission');
    } catch (error) {
        console.error(error);
    }
}

watch((editForm), (newValue) => {
    isDirty.value = newValue.full_name !== initialEditForm.value.full_name ||
                    newValue.role_id !== initialEditForm.value.role_id ||
                    newValue.position !== initialEditForm.value.position;
})

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        users.value = props.users;
        return;
    }

    const query = newValue.toLowerCase();
    
    users.value = props.users.filter(user => {
        const full_name = user.full_name.toLowerCase();
        const position = user.position.toLowerCase();
        const role_id = user.role_id.toLowerCase();
        const permission = user.permissions.map(permission => permission.name).join('.');

        return full_name.includes(query) ||
                position.includes(query) ||
                role_id.includes(query) ||
                permission.includes(query);
    })
}, { immediate: true })
</script>

<template>
    <div class="flex flex-col items-start gap-5 flex-[1_0_0] self-stretch">
        <div class="flex items-start gap-5 self-stretch">
            <SearchBar 
                :showFilter="false"
                :placeholder="'Search'"
                v-model="searchQuery"
            />
            <Button
                :type="'button'"
                :variant="'primary'"
                :size="'lg'"
                :iconPosition="'left'"
                class="!w-fit"
                @click="openAddAdmin"
            >
                <template #icon>
                    <PlusIcon class="size-6" />
                </template>
                Add sub-admin
            </Button>
        </div>

        <div class="grid grid-cols-2 xl:grid-cols-3 items-start content-start gap-5 self-stretch flex-wrap" v-if="users && users.length">
            <template v-for="user in users">
                <div class="flex p-4 items-center gap-4 flex-[1_0_0] rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)] cursor-pointer"
                    @click="openDetail(user)"    
                >
                    <img
                        :src="user.image"
                        alt="UserIcon"
                        class="rounded-full size-[72px]"
                        v-if="user.image"
                    >
                    <DefaultIcon class="rounded-full size-[72px]" v-else />
                    
                    <div class="flex flex-col items-start gap-2 flex-[1_0_0] self-stretch">
                        <div class="flex items-start self-stretch">
                            <Tag
                                :variant="'default'"
                                :value="capitilize(user.position)"
                            />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <span class="line-clamp-1 self-stretch text-ellipsis text-grey-950 text-base font-bold">{{ user.full_name }}</span>
                            <span class="line-clamp-1 self-stretch text-ellipsis text-grey-500 text-xs font-normal">ID: {{ user.role_id }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex w-full flex-col items-center justify-center gap-5" v-else>
            <UndetectableIllus />
            <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
        </div>
    </div>

    <Modal
        :title="'Sub-admin detail'"
        :show="isDetailShow"
        :maxWidth="'md'"
        @close="closeDetail('close')"
    >
        <div class="flex items-start gap-8 self-stretch">
            <div class="flex flex-col p-3 items-start gap-8 rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)]">
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <img
                        :src="selectedUser.image"
                        alt="UserImage"
                        class="size-[208px]"
                        v-if="selectedUser && selectedUser.image"
                    >
                    <DefaultIcon class="size-[208px]" v-else />
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <span class="self-stretch text-grey-950 text-md font-bold">{{ selectedUser.full_name }}</span>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <div class="grid grid-cols-3 items-start gap-3 self-stretch">
                                <span class="text-grey-500 text-xs font-normal">Admin ID</span>
                                <span class="text-grey-950 text-xs font-semibold grid-cols-2">{{ selectedUser.role_id }}</span>
                            </div>
                            <div class="grid grid-cols-3 items-start gap-3 self-stretch">
                                <span class="text-grey-500 text-xs font-normal">Title</span>
                                <span class="text-grey-950 text-xs font-semibold grid-cols-2">{{ capitilize(selectedUser.position) }}</span>
                            </div>
                            <div class="grid grid-cols-3 items-center gap-3 self-stretch">
                                <span class="text-grey-500 text-xs font-normal">Password</span>
                                <div class="flex items-center gap-1">
                                    <template v-for="n in 8" :key="n">
                                        <!-- <div class="text-[10px]">&#x2022;</div> -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="6" height="7" viewBox="0 0 6 7" fill="none">
                                            <circle cx="3" cy="3.5" r="3" fill="#23292E"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-2 self-stretch">
                    <Button
                        :variant="'red-tertiary'"
                        :type="'button'"
                        @click="openDelete"
                    >
                        Delete
                    </Button>
                    <Button
                        :variant="'primary'"
                        :type="'button'"
                        @click="openEdit()"
                    >
                        Edit
                    </Button>
                </div>
            </div>

            <div class="flex flex-col items-start flex-[1_0_0] self-stretch divide-y divide-grey-100 max-h-[417px] overflow-y-auto scrollbar-webkit scrollbar-thin">
                <div class="flex py-4 justify-between items-center self-stretch" v-for="permission in permissionList">
                    <span class="text-grey-950 text-base font-medium">Allow '{{ permission.text }}' access</span>
                    <Toggle
                        :checked="!!selectedUser.permissions.find(exist_permission => exist_permission.name === permission.value)"
                        :disabled="form.processing || isProcessing"
                        @change="editPermission(permission)"
                        class="pr-2"
                    >
                    </Toggle>
                </div>
            </div>
        </div>

            <!-- Edit sub-admin details -->
            <Modal
                :show="isEditShow"
                :maxWidth="'md'"
                :title="'Edit Sub-admin'"
                @close="closeEdit('close')"
            >
                <EditAdminDetail 
                    :user="selectedUser"
                    @close="closeEdit()"
                    @isDirty="isDirty=$event"
                    @update:users="users.value=$event"
                />

                <Modal 
                    :unsaved="true"
                    :maxWidth="'2xs'"
                    :withHeader="false"
                    :show="isUnsavedChangesOpen"
                    @close="closeEdit('stay')"
                    @leave="closeEdit('leave')"
                >
                </Modal>
            </Modal>
    </Modal>

    <!-- Delete sub-admin -->
    <Modal
        :show="isDeleteShow"
        :maxWidth="'2xs'"
        :deleteConfirmation="true"
        :confirmationTitle="'Delete this admin?'"
        :confirmationMessage="'Are you sure you want to delete the selected admin? This action cannot be undone.'"
        :deleteUrl="`admin-user/delete-admin-user/${selectedUser.id}`"
        :toastMessage="'Admin deleted successfully.'"
        @close="closeDelete"
    >
    </Modal>

    <!-- Add sub-admin -->
    <Modal
        :show="isAddAdminOpen"
        :maxWidth="'md'"
        :title="'Add Sub-admin'"
        @close="closeAddAdmin('close')"
    >
        <AddSubAdmin 
            @isDirty="isAddAdminDirty=$event"
            @close="closeAddAdmin"
        />

        <Modal 
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeAddAdmin('stay')"
            @leave="closeAddAdmin('leave')"
        />
    </Modal>
</template>

