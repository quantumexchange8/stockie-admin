<script setup>
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import DragDropImage from "@/Components/DragDropImage.vue";
import { computed, ref, watch } from "vue";
import { useCustomToast, useInputValidator, usePhoneUtils } from "@/Composables";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    waiters: {
        type: Array,
        required: true
    },
})
const { showMessage } = useCustomToast();
const { transformPhone, formatPhoneInput } = usePhoneUtils();
const { isValidNumberKey } = useInputValidator();
const isUnsavedChangesOpen = ref(false);

const emit = defineEmits(["close", "isDirty"]);

const unsaved = (status) => {
    emit('close', status)
};

const form = useForm({
    username: "",
    name: "",
    phone: "",
    phone_temp: "",
    email: "",
    role_id: "",
    salary: "",
    stockie_email: "",
    password: "",
    image: "",
});

const submit = () => {
    form.phone = form.phone_temp ? transformPhone(form.phone_temp) : '';
    form.post(route("waiter.add-waiter"), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            unsaved('leave');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'New waiter has been successfully added.',
                });
            }, 200)
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const requiredFields = ['username', 'name', 'phone_temp', 'email', 'role_id', 'salary', 'stockie_email', 'password'];

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
})

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>
<template>
    <div class="w-full flex flex-col overflow-y-scroll scrollbar-thin scrollbar-webkit pl-1 pt-1">
        <form @submit.prevent="submit" autocomplete="off">
            <div class="w-full flex flex-col max-h-[500px] md:gap-9">
                <div class="w-full flex md:gap-6">
                    <DragDropImage
                        :inputName="'image'"
                        :errorMessage="form.errors.image"
                        v-model="form.image"
                        class="!h-[373px] !w-[373px] "
                    />
                    <div class="flex flex-grow flex-col md:gap-[48px]">
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Personal Detail
                            </div>

                            <div class="flex flex-col md:gap-4">
                                <div class="flex md:gap-4">
                                    <TextInput
                                        :labelText="'User name'"
                                        :placeholder="'eg: johndoe'"
                                        inputId="username"
                                        v-model="form.username"
                                        :errorMessage="form.errors.username"
                                    >
                                    </TextInput>
                                </div>

                                <div class="flex md:gap-4">
                                    <TextInput
                                        label-text="Full name"
                                        :placeholder="'eg: John Doe'"
                                        inputId="name"
                                        type="'text'"
                                        v-model="form.name"
                                        :errorMessage="form.errors.name"
                                    >
                                    </TextInput>
                                </div>

                                <div class="flex md:gap-4">
                                    <div class="w-full flex flex-col">
                                        <TextInput
                                            label-text="Phone number"
                                            inputId="phone"
                                            type="'tel'"
                                            v-model="form.phone_temp"
                                            :errorMessage="form.errors?.phone || ''"
                                            :iconPosition="'left'"
                                            @keypress="isValidNumberKey($event, false)"
                                            @input="formatPhoneInput"
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
                                            :errorMessage="form.errors?.email || ''"
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
                                        @keypress="isValidNumberKey($event, true)"
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
                                        labelText="Email address"
                                        :placeholder="'for Stockie account log-in'"
                                        inputId="stockie_email"
                                        type="'email'"
                                        v-model="form.stockie_email"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.stockie_email"
                                    />
                                </div>
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        labelText="Password"
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
                        @click="unsaved('close')"
                        :size="'lg'"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        type="submit"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        Add
                    </Button>
                </div>
            </div>
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="unsaved('stay')"
                @leave="unsaved('leave')"
            />
        </form>
    </div>
</template>
