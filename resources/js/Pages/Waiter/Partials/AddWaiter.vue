<script setup>
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";

const emit = defineEmits(["close"]);
const closeModal = () => {
    form.reset(); //Reset form field
    form.errors = {}; // Clear all errors
    emit("close");
};

const form = useForm({
    name: "",
    full_name: "",
    phone: "",
    email: "",
    role_id: "",
    salary: "",
    worker_email: "",
    password: "",
});

const submit = () => {
    form.post(route("waiter.add-waiter"), {
        onSuccess: () => {
            console.log("Form submitted successfully!");
            closeModal();
        },
        onError: (error) => {
            console.error(error); // Log the error details
        },
    });
};

const isFormIncomplete = () => {
    return (
        !form.name ||
        !form.full_name ||
        !form.phone ||
        !form.email ||
        !form.role_id ||
        !form.salary ||
        !form.worker_email ||
        !form.password
    );
};
</script>
<template>
    <div class="w-full flex flex-col">
        <form @submit.prevent="submit">
            <div class="w-full flex flex-col md:gap-9">
                <div class="w-full flex md:gap-6">
                    <div
                        class="w-[373px] h-[373px] p-3 border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                    ></div>
                    <div class="flex flex-grow flex-col md:gap-[48px]">
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Personal Detail
                            </div>

                            <div class="flex flex-col md:gap-4">
                                <div class="flex md:gap-4">
                                    <TextInput
                                        label-text="Full name"
                                        :placeholder="'eg: John Doe'"
                                        inputId="full_name"
                                        type="'text'"
                                        v-model="form.full_name"
                                        :errorMessage="form.errors.full_name"
                                    ></TextInput>
                                    <TextInput
                                        label-text="Username"
                                        :placeholder="'eg: johndoe'"
                                        inputId="name"
                                        type="'text'"
                                        v-model="form.name"
                                        :errorMessage="form.errors.name"
                                    ></TextInput>
                                </div>

                                <div class="flex md:gap-4">
                                    <div class="w-full flex flex-col">
                                        <TextInput
                                            label-text="Phone number"
                                            inputId="phone"
                                            type="'tel'"
                                            v-model="form.phone"
                                            :errorMessage="form.errors.phone"
                                            :iconPosition="'left'"
                                        >
                                            <template #prefix>
                                                <span class="text-grey-900"
                                                    >+60</span
                                                >
                                            </template>
                                        </TextInput>
                                    </div>

                                    <div class="w-full flex flex-col">
                                        <TextInput
                                            label-text="Email address"
                                            :placeholder="'eg: johndoe@gmail.com'"
                                            inputId="email"
                                            type="'email'"
                                            v-model="form.email"
                                            :errorMessage="form.errors.email"
                                        >
                                        </TextInput>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Work Detail
                            </div>
                            <div class="flex md:gap-4">
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Staff ID"
                                        :placeholder="'eg: J8192'"
                                        inputId="role_id"
                                        type="'text'"
                                        v-model="form.role_id"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.role_id"
                                    />
                                </div>
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Basic salary (per month)"
                                        inputId="salary"
                                        type="'text'"
                                        v-model="form.salary"
                                        :iconPosition="'left'"
                                    >
                                        <template #prefix>
                                            <span class="text-grey-900"
                                                >RM</span
                                            >
                                        </template>
                                    </TextInput>
                                    <InputError :message="form.errors.salary" />
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Account Detail
                            </div>
                            <div class="flex md:gap-4">
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Email address"
                                        :placeholder="'for Stockie account log-in'"
                                        inputId="worker_email"
                                        type="'email'"
                                        v-model="form.worker_email"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.worker_email"
                                    />
                                </div>
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Password"
                                        :placeholder="'for Stockie account log-in'"
                                        inputId="password"
                                        :inputType="'password'"
                                        v-model="form.password"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.password"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex md:gap-4">
                    <Button
                        variant="tertiary"
                        type="button"
                        @click="form.reset()"
                        :size="'lg'"
                        >Cancel</Button
                    >
                    <Button
                        variant="primary"
                        type="submit"
                        :size="'lg'"
                        :disabled="isFormIncomplete() && form.processing"
                        :class="{ 'opacity-25': form.processing }"
                        v-bind:class="[
                            isFormIncomplete()
                                ? 'disabled-class'
                                : 'enabled-class',
                        ]"
                        >Add</Button
                    >
                </div>
            </div>
        </form>
    </div>
</template>
