<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import { sidebarState, rightSidebarState } from '@/Composables'
import { NumberedNotificationIcon, LanguageIcon, LogOutIcon, CheckIcon } from '@/Components/Icons/solid';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import { LogoutIllust } from '@/Components/Icons/illus';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import NotificationsOverlay from './NotificationsOverlay.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import axios from 'axios';

defineProps({
    title: String
})

const form = useForm({

});

const order_notifications = ref([]);
const inventory_notifications = ref([]);
const waiter_notifications = ref([]);
const notificationLength = ref(0);
const all_notifications = ref(0);

const getNotifications = async () => {
    try {
        const response = await axios.get('/notifications/latestNotification');
        order_notifications.value = response.data.order_notifications;
        inventory_notifications.value = response.data.inventory_notifications;
        waiter_notifications.value = response.data.waiter_notifications;
        all_notifications.value = response.data.all_notifications;

        notificationLength.value =  Object.keys(inventory_notifications.value).length + 
                                    Object.keys(order_notifications.value).length +
                                    Object.keys(waiter_notifications.value).length;

    } catch (error) {
        console.error(error);
    }
}

const markAllAsRead = async () => {
    try {
        const notificationsToMarkAsRead = [
            ... (Array.isArray(order_notifications.value) ? order_notifications.value : []),
            ... (Array.isArray(inventory_notifications.value) ? inventory_notifications.value : []),
            ... (Array.isArray(waiter_notifications.value) ? waiter_notifications.value : [])
        ];

        await axios.post('/notifications/markAsRead', { notifications: notificationsToMarkAsRead });
        getNotifications();
    } catch (error) {
        console.error(error);
    }
};

const languages = [ 
    { name: 'Bahasa Melayu', language: 'ms' },
    { name: 'English', language: 'en' },
    { name: '中文', language: 'zh' },
];

const selected = ref(languages.find(language => language.language === localStorage.getItem('selectedLanguage')) || languages[1]);
const op = ref(null);

const changeLanguage = (lang) => {
    selected.value = languages.find(language => language.language === lang);
    localStorage.setItem('selectedLanguage', lang);
    // console.log(lang);
};

const isSelected = (language) => {
    return selected.value && language.language === selected.value.language;
};


const openOverlay = (event) => {
    op.value.show(event);
}

const closeOverlay = () => {
    notificationLength.value = 0;
    markAllAsRead();
    op.value.hide();
}

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

watch(() => notificationLength.value, (newValue) => {
    notificationLength.value = newValue;
}, { immediate: true });

onMounted(() => {
    rightSidebarState.isOpen = false;
    getNotifications();
    const savedLanguage = localStorage.getItem('selectedLanguage');
    if(savedLanguage){
        selected.value = languages.find(language => language.language === savedLanguage);
    }
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
                            <div class="flex items-start gap-8">
                                <button
                                    :type="'button'"
                                    @click="openOverlay($event)"    
                                >
                                    <NumberedNotificationIcon 
                                        class="text-primary-900 hover:text-primary-800"
                                        :notificationValue="all_notifications > 0 ? all_notifications : 0"
                                        aria-hidden="true" 
                                    />
                                </button>
                                <Menu as="div" class="relative inline-block text-left">
                                    <div>
                                        <MenuButton>
                                            <LanguageIcon class="text-primary-900 hover:text-primary-800"/>
                                        </MenuButton>
                                    </div>

                                    <transition 
                                        enter-active-class="transition duration-100 ease-out"
                                        enter-from-class="transform scale-95 opacity-0" 
                                        enter-to-class="transform scale-100 opacity-100"
                                        leave-active-class="transition duration-75 ease-in"
                                        leave-from-class="transform scale-100 opacity-100" 
                                        leave-to-class="transform scale-95 opacity-0"
                                    >
                                        <MenuItems
                                            class="absolute z-20 right-0 min-w-[370px] p-6 flex flex-col origin-top-right whitespace-nowrap rounded-md bg-white shadow-lg"
                                        >
                                            <span class="text-primary-950 text-start text-md font-medium pb-6">Change Language</span>
                                            <MenuItem
                                                v-slot="{ active }"
                                                v-for="language in languages"
                                                :key="language.language"
                                            >
                                                <button
                                                    type="button"
                                                    :class="[
                                                        { 'bg-primary-25 flex justify-between': isSelected(language) },
                                                        { 'bg-white hover:bg-[#fff9f980]': !isSelected(language) },
                                                        'group flex w-full items-center rounded-md px-6 py-3 text-base text-gray-900',
                                                    ]"
                                                    @click="changeLanguage(language.language)"
                                                >
                                                    <span
                                                        :class="[
                                                            { 'text-primary-900 text-center text-base font-medium': isSelected(language) },
                                                            { 'text-grey-900 text-center text-base font-medium group-hover:text-primary-800': !isSelected(language) },
                                                        ]"
                                                    >
                                                        {{ language.name }}
                                                    </span>
                                                    <div v-show="isSelected(language)" class="shrink-0 text-white">
                                                        <CheckIcon />
                                                    </div>
                                                </button>
                                            </MenuItem>
                                        </MenuItems>
                                    </transition>
                                </Menu>
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

    <OverlayPanel 
        ref="op"
        @close="closeOverlay()"
    >
        <NotificationsOverlay 
            :order_notifications="order_notifications"
            :inventory_notifications="inventory_notifications"
            :waiter_notifications="waiter_notifications"
            :all_notifications="all_notifications"
            :notificationLength="notificationLength"
        />
    </OverlayPanel>
    
</template>
