<script setup>
import Main from "../../../assets/images/login/Main.svg";
import StockieLogo from "../../../assets/images/login/stockie-logo.svg";
import Button from "@/Components/Button.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

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
</script>

<template>
    <div class="flex min-h-screen">
        <div
            class="w-full bg-primary-900 flex flex-col pt-[99px] pr-[56px] gap-[94px]"
        >
            <div class="flex flex-col pl-[45px]">
                <div class="w-full flex flex-col gap-[7px]">
                    <div class="text-white font-black text-[40px]">
                        Welcome to
                        <span class="bg-primary-800 pl-2 pr-3">Stockie!</span>
                    </div>
                    <div class="w-[434px] text-md text-white">
                        Experience the ease of a hassle-free inventory
                        management system today.
                    </div>
                </div>
            </div>
            <div><img :src="Main" alt="Stockie" /></div>
        </div>

        <div
            class="w-full bg-white flex flex-col justify-center items-center pl-[113px] pr-[106px]"
        >
            <form @submit.prevent="submit">
                <div class="w-[421px] flex flex-col gap-[73px]">
                    <div class="flex flex-col gap-[10px]">
                        <img
                            :src="StockieLogo"
                            alt="Stockie"
                            class="w-[80px] h-[80px]"
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
                            >
                            </TextInput>
                            <InputError
                                :message="form.errors.role_id"
                            ></InputError>
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

                            <div class="flex items-center gap-[9px]">
                                <Checkbox
                                    name="remember"
                                    id="remember"
                                    v-model:checked="form.remember"
                                />
                                <span class="text-xs text-gray-900"
                                    >Remember me</span
                                >
                            </div>
                        </div>
                    </div>

                    <Button
                        variant="primary"
                        :size="'lg'"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        >Log in</Button
                    >
                </div>
            </form>
        </div>
    </div>
</template>
