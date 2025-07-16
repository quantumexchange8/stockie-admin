<script setup>
import Main from "../../../assets/images/login/Main.svg";
import StockieLogo from "../../../assets/images/login/stockie-logo.svg";
import Button from "@/Components/Button.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import Toast from "@/Components/Toast.vue";
import Modal from "@/Components/Modal.vue";
import { useCustomToast } from "@/Composables";
import { LogoutIllust } from '@/Components/Icons/illus';
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const { flashMessage } = useCustomToast();
const page = usePage();

const user = computed(() => page.props.auth.user);
const isConfirmLoginModalOpen = ref(false);

const form = useForm({
    role_id: "",
    password: "",
    confirm_login: false,
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onError: (error) => {
            form.confirm_login = false;
            if (error['user_has_session']) openModal();
        },
        onFinish: () => {
            if (form.confirm_login) form.reset("password");
        },
    });
};

const openModal = () => (isConfirmLoginModalOpen.value = true);

const closeModal = () => (isConfirmLoginModalOpen.value = false);

const confirmLogin = () => {
    form.confirm_login = true;
    submit();
    closeModal();
}

const isFormValid = computed(() => ['role_id', 'password'].every(field => form[field]) && !form.processing && user.value == null);

onMounted(() => {
    flashMessage();
    // console.log(user.value);

    // const redirectTo = user.value && user.value != null 
    //     ? '/dashboard'
    //     : '/dashboard';
    if (user.value != null) {
        window.location.href = '/dashboard';
    }
});
</script>

<template>
    <Head title="Login"></Head>

    <div class="flex flex-col md:flex-row w-full min-h-screen max-h-screen overflow-y-auto scrollbar-thin scrollbar-webkit md:overflow-hidden gap-y-10 md:gap-0 items-center">
        <div class="w-full md:w-1/2 lg:w-7/12 md:bg-primary-900 flex flex-col pt-12 md:pt-48 lg:pt-96 xl:pt-[550px] 2xl:pt-[650px] pr-6 md:pr-12 xl:pr-12 gap-12">
            <div class="flex flex-col pl-6 md:pl-11">
                <div class="w-full flex flex-col gap-2 items-center md:items-start">
                    <div class="text-primary-900 md:text-white font-black text-[40px]">
                        Welcome to
                        <span class="text-white bg-primary-800 pl-2 pr-3">Stockie!</span>
                    </div>
                    <div class="text-base md:text-md text-primary-900 md:text-white">
                        Experience the ease of a hassle-free inventory
                        management system today.
                    </div>
                </div>
            </div>
            <img :src="Main" alt="Stockie" width="925" height="740" class="hidden md:block"/>
            <!-- <div class="">
            </div> -->
        </div>

        <Toast />
        
        <div class="w-full md:w-1/2 lg:w-5/12 bg-white flex flex-col justify-center items-center">
            <form @submit.prevent="submit" class="max-w-[421px] w-full px-6">
                <div class="flex flex-col gap-12 w-full">
                    <div class="flex flex-col gap-2">
                        <img
                            :src="StockieLogo"
                            alt="Stockie"
                            class="size-20"
                        />
                        <div class="flex flex-col gap-1">
                            <div class="font-black text-xl text-primary-900">
                                stockie
                            </div>
                            <div class="font-medium text-base text-grey-900">
                                Your Inventory, Perfectly Managed
                            </div>
                        </div>
                    </div>

                    <div class="w-full flex flex-col gap-6">
                        <div class="flex flex-col">
                            <TextInput
                                label-text="ID"
                                :inputType="'text'"
                                :placeholder="'Enter your ID here'"
                                v-model="form.role_id"
                                required
                                autofocus
                                autocomplete="role_id"
                            />
                            <InputError :message="form.errors.role_id"/>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div>
                                <TextInput
                                    label-text="Password"
                                    :placeholder="'Enter your password here'"
                                    id="password"
                                    :inputType="'password'"
                                    v-model="form.password"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="flex items-center gap-2">
                                <Checkbox
                                    name="remember"
                                    id="remember"
                                    v-model:checked="form.remember"
                                />
                                <span class="text-xs text-gray-900">Remember me</span>
                            </div>
                        </div>
                    </div>

                    <Button
                        variant="primary"
                        :size="'lg'"
                        :disabled="!isFormValid"
                    >
                        Log in
                    </Button>
                </div>
            </form>
        </div>
    </div>

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :show="isConfirmLoginModalOpen"
        @close="closeModal"
        :withHeader="false"
    >
        <div class="inline-flex flex-col items-center gap-9" >
            <div class="bg-primary-50 flex flex-col items-center gap-[10px] rounded-t-[5px] m-[-24px] pt-6 px-3">
                <div class="w-full shrink-0">
                    <LogoutIllust />
                </div>
            </div>
            <div class="flex flex-col gap-5 pt-6">
                <div class="flex flex-col gap-1 text-center self-stretch">
                    <span class="text-primary-900 text-lg font-medium self-stretch">Active Session Detected</span>
                    <span class="text-grey-900 text-base font-medium self-stretch">Your account is currently in use on another device. Continuing will log out the other session. Are you sure you want to proceed?</span>
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
                    type="button"
                    :disabled="form.processing"
                    @click="confirmLogin"
                >
                    Confirm
                </Button>
            </div>
        </div>
    </Modal>
</template>
