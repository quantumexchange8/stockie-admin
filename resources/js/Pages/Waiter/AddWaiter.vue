<script setup>
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import InputPrefix from "@/Components/TextInputPrefix.vue";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const show = ref(false);
{
    /**
{
    const openModal = () => {
        show.value = true;
    };
}**/
}
const closeModal = () => {
    resetForm();

    form.errors.phone = null;
    form.errors.email = null;
    form.errors.staffid = null;
    form.errors.salary = null;
    form.errors.stockie_email = null;

    show.value = false;
};

const form = useForm({
    name: "",
    phone: "",
    email: "",
    staffid: "",
    salary: "",
    stockie_email: "",
    stockie_password: "",
});

const submit = () => {
    form.post(route("waiter.add-waiter"), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
        onError: (errors) => {
            // Handle errors here
            console.log(errors);
        },
    });
};

const resetForm = () => {
    form.reset();
};

const isFormIncomplete = () => {
    return (
        !form.name ||
        !form.phone ||
        !form.email ||
        !form.staffid ||
        !form.salary ||
        !form.stockie_email ||
        !form.stockie_password
    );
};
</script>
<template>
    <Modal
        :show="show"
        @close="closeModal"
        :title="'Add New Waiter'"
        :maxWidth="'lg'"
    >
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
                                    <TextInput
                                        label-text="Full name"
                                        :placeholder="'eg: John Doe'"
                                        inputId="name"
                                        type="'name'"
                                        v-model="form.name"
                                    ></TextInput>

                                    <div class="flex md:gap-4">
                                        <div class="w-full flex flex-col">
                                            <InputPrefix
                                                label-text="Phone number"
                                                inputId="phone"
                                                type="'tel'"
                                                v-model="form.phone"
                                                :error-message="
                                                    form.errors.phone
                                                "
                                            >
                                                <template #prefix>
                                                    <span class="text-grey-900"
                                                        >+60</span
                                                    >
                                                </template>
                                            </InputPrefix>
                                            <span
                                                v-if="form.errors.phone"
                                                class="text-primary-700 text-xs"
                                                >{{ form.errors.phone }}</span
                                            >
                                        </div>
                                        <div class="w-full flex flex-col">
                                            <TextInput
                                                label-text="Email address"
                                                :placeholder="'eg: johndoe@gmail.com'"
                                                inputId="email"
                                                type="'email'"
                                                v-model="form.email"
                                            >
                                            </TextInput>
                                            <span
                                                v-if="form.errors.email"
                                                class="text-primary-700 text-xs"
                                                >{{ form.errors.email }}</span
                                            >
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
                                            inputId="staffid"
                                            type="'text'"
                                            v-model="form.staffid"
                                        ></TextInput>
                                        <span
                                            v-if="form.errors.staffid"
                                            class="text-primary-500 text-xs"
                                            >{{ form.errors.staffid }}</span
                                        >
                                    </div>
                                    <div class="w-full flex flex-col">
                                        <InputPrefix
                                            label-text="Basic salary (per month)"
                                            inputId="salary"
                                            type="'text'"
                                            v-model="form.salary"
                                        >
                                            <template #prefix>
                                                <span class="text-grey-900"
                                                    >RM</span
                                                >
                                            </template>
                                        </InputPrefix>
                                        <span
                                            v-if="form.errors.salary"
                                            class="text-primary-500 text-xs"
                                            >{{ form.errors.salary }}</span
                                        >
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
                                            inputId="stockie_email"
                                            type="'email'"
                                            v-model="form.stockie_email"
                                        ></TextInput>
                                        <span
                                            v-if="form.errors.stockie_email"
                                            class="text-primary-700 text-xs"
                                            >{{
                                                form.errors.stockie_email
                                            }}</span
                                        >
                                    </div>
                                    <div class="w-full flex flex-col">
                                        <TextInput
                                            label-text="Password"
                                            :placeholder="'for Stockie account log-in'"
                                            inputId="stockie_password"
                                            :inputType="'password'"
                                            v-model="form.stockie_password"
                                        ></TextInput>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex md:gap-4">
                        <Button
                            variant="tertiary"
                            type="button"
                            @click="resetForm"
                            :size="'lg'"
                            >Cancel</Button
                        >
                        <Button
                            variant="primary"
                            type="submit"
                            :size="'lg'"
                            :disabled="isFormIncomplete()"
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
    </Modal>
</template>
