<script setup>
import Main from "../../../assets/images/login/Main.svg";
import StockieLogo from "../../../assets/images/login/stockie-logo.svg";
import Button from "@/Components/Button.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables";
import { Head, useForm } from "@inertiajs/vue3";
import { onMounted } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const { flashMessage } = useCustomToast();

const form = useForm({
    role_id: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

onMounted(() => {
    flashMessage();
});
</script>

<template>
    <Head title="Login"></Head>

    <div class="flex w-full min-h-screen max-h-screen overflow-hidden">
        <div class="w-1/2 md:bg-primary-900 flex flex-col justify-between pt-[99px] pr-12 xl:pr-12 gap-20">
            <div class="flex flex-col pl-11">
                <div class="w-full flex flex-col gap-2 max-w-[450px]">
                    <div class="text-primary-900 md:text-white font-black text-[40px]">
                        Welcome to
                        <span class="text-white bg-primary-800 pl-2 pr-3">Stockie!</span>
                    </div>
                    <div class="text-md text-primary-900 md:text-white">
                        Experience the ease of a hassle-free inventory
                        management system today.
                    </div>
                </div>
            </div>
            <img :src="Main" alt="Stockie" width="1050" height="920" class="hidden md:block flex-shrink w-full"/>
            <!-- <div class="">
            </div> -->
        </div>

        <Toast />
        
        <div class="w-1/2 bg-white flex flex-col justify-center items-center">
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
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Log in
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
