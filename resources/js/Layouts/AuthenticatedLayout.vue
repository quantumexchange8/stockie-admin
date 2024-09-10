<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import { sidebarState, rightSidebarState } from '@/Composables'
import { NumberedNotificationIcon, LanguageIcon, LogOutIcon } from '@/Components/Icons/solid';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import { LogoutIllust } from '@/Components/Icons/illus';

defineProps({
    title: String
})

const form = useForm({

});

const submit = () => {
    form.post(route('logout'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            closeModal();
        },
        onError: (error) => {
            console.error(error);
        }
    })
}

const isLogoutModal = ref(false);

const logout = () => {
    isLogoutModal.value = true;
};

const closeModal = () => {
    isLogoutModal.value = false;
}

onMounted(() => {
    rightSidebarState.isOpen = false
})
</script>

<template>
    <Head :title="title"></Head>
    
    
    <div class="min-h-screen">
        <!-- Sidebar Menu -->
        <Sidebar />

        <div
            style="transition-property: margin; transition-duration: 150ms"
            :class="[
                'flex flex-col',
                'flex-shrink-0',
                'min-h-screen py-8 px-6',
                {
                    'lg:ml-[283px]': sidebarState.isOpen,
                    'md:ml-0': !sidebarState.isOpen,
                },
            ]"
        >
            <!-- Page Heading -->
            <div class="flex flex-col shadow-[-4px_-9px_36.4px_0px_rgba(199,57,42,0.05)] rounded-[8px] bg-white">
                <header class="pl-6 flex flex-col items-center gap-[10px]" v-if="$slots.header">
                    <div class="w-full flex flex-col items-end gap-[19px] pt-8">
                        <div class="flex items-center justify-between self-stretch pr-6">
                            <div class="flex items-center gap-[20px]">
                                <button
                                    @click="sidebarState.isOpen = !sidebarState.isOpen"
                                    :srText="sidebarState.isOpen ? 'Close sidebar' : 'Open sidebar'"
                                    class="inline-flex items-center justify-center rounded-md text-gray-400 
                                        hover:text-gray-500 transition duration-150 ease-in-out"
                                >
                                    <svg width="24" height="24" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path
                                            :class="{
                                                hidden: !sidebarState.isOpen,
                                                'inline-flex': sidebarState.isOpen,
                                            }"
                                            d="M3 12H21M3 6H21M3 18H15" 
                                            stroke="#7E171B" 
                                            stroke-width="3.5" 
                                            stroke-linecap="round" 
                                            stroke-linejoin="round"
                                        />
                                        <path 
                                            :class="{
                                                hidden: sidebarState.isOpen,
                                                'inline-flex': !sidebarState.isOpen,
                                            }"
                                            d="M4 6h16M4 12h16M4 18h16"
                                            stroke="#7E171B" 
                                            stroke-width="3.5" 
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </button>
                                <slot name="header"/>
                            </div>
                            <div class="flex items-start gap-3">
                                <NumberedNotificationIcon 
                                    class="text-primary-900 hover:text-primary-800"
                                    :notificationValue="10"
                                    aria-hidden="true" 
                                />
                                <LanguageIcon 
                                    class="text-primary-900 hover:text-primary-800"
                                    aria-hidden="true" 
                                />
                                <LogOutIcon 
                                    class="text-primary-900 cursor-pointer hover:text-primary-800"
                                    aria-hidden="true" 
                                    @click="logout()"
                                />
                            </div>
                        </div>
                        <div class="bg-primary-900 w-full h-[0.5px]"></div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="w-full flex justify-center shadow-[-4px_-9px_36.4px_0px_rgba(199,57,42,0.05)] p-4">
                    <div class="xl:max-w-[1440px] max-h-[80vh] overflow-y-auto scrollbar-thin scrollbar-webkit flex flex-col w-full self-center gap-[10px] flex-shrink-1 p-1">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :show="isLogoutModal"
        @close="closeModal"
        v-if="isLogoutModal"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="inline-flex flex-col items-center gap-9" >
                <div class="bg-primary-50 flex flex-col items-center gap-[10px] rounded-t-[5px] m-[-24px] pt-6 px-3">
                    <div class="w-full shrink-0">
                        <LogoutIllust />
                    </div>
                </div>
                <div class="flex flex-col gap-5 pt-6">
                    <div class="flex flex-col gap-1 text-center self-stretch">
                        <span class="text-primary-900 text-lg font-medium self-stretch">You’re leaving...</span>
                        <span class="text-grey-900 text-base font-medium self-stretch">Are you sure you want to log out this account?</span>
                    </div>
                </div>

                <div class="flex justify-center items-start self-stretch gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        size="lg"
                        type="submit"
                    >
                        Log out
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
    
</template>
