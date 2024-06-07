<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";

import { ref } from "vue";

const isModalOpen = ref(false);

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<template>
    <!-- <Head title="Waiter"></Head> -->

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Waiter
            </h2>
        </template>

        <div class="w-full py-6">
            <div
                class="w-full mx-auto flex flex-col gap-5 justify-center items-center"
            >
                <div class="flex md:gap-[21px]">
                    <div
                        class="p-4 sm:p-8 bg-white sm:rounded-lg"
                        style="border: 1px solid #ffe1e2"
                    >
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                    <div
                        class="p-4 sm:p-8 bg-white sm:rounded-lg"
                        style="border: 1px solid #ffe1e2"
                    >
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <div
                    class="w-full flex flex-col gap-4 p-4 sm:p-6 bg-white sm:rounded-lg border-[#ffe1e2] border-solid border-[1px]"
                >
                    <div class="flex items-center gap-5">
                        <TextInput class="w-full" />
                        <Button variant="primary" size="lg" @click="openModal">
                            <PlusIcon aria-hidden="true" /> New Waiter
                        </Button>
                        <Modal :show="isModalOpen" @close="closeModal">
                            <div class="max-w-max p-6 flex flex-col gap-6">
                                <div class="flex flex-col justify-between">
                                    <div class="text-[20px] text-[#48070A]">
                                        Add New Waiter
                                    </div>
                                    <div><!-- X Icon --></div>
                                </div>
                                <div class="flex gap-6">
                                    <div class="w-[373px]">
                                        <!--Drag and drop-->
                                    </div>

                                    <div class="flex flex-col gap-[48px]">
                                        <!--- Personal Detail -->
                                        <div class="flex flex-col gap-6">
                                            <div
                                                class="text-[20px] text-[#48070A]"
                                            >
                                                Personal Detail
                                            </div>
                                            <div class="flex flex-col gap-4">
                                                <div
                                                    class="flex flex-col gap-1"
                                                >
                                                    <div class="text-xs">
                                                        Full name
                                                    </div>

                                                    <TextInput></TextInput>
                                                </div>
                                                <div class="flex gap-4">
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <div class="text-xs">
                                                            Phone number
                                                        </div>

                                                        <TextInput></TextInput>
                                                    </div>
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <div class="text-xs">
                                                            Email address
                                                        </div>

                                                        <TextInput></TextInput>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--- Work Detail -->
                                        <div class="flex flex-col gap-6">
                                            <div
                                                class="text-[20px] text-[#48070A]"
                                            >
                                                Work Detail
                                            </div>
                                            <div class="flex flex-col gap-4">
                                                <div class="flex gap-4">
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <div class="text-xs">
                                                            Staff ID
                                                        </div>

                                                        <TextInput></TextInput>
                                                    </div>
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <div class="text-xs">
                                                            Basic salary (per
                                                            month)
                                                        </div>

                                                        <TextInput></TextInput>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--- Account Detail -->
                                        <div class="flex flex-col gap-6">
                                            <div
                                                class="text-[20px] text-[#48070A]"
                                            >
                                                Account Detail
                                            </div>
                                            <div class="flex flex-col gap-4">
                                                <div class="flex gap-4">
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <div class="text-xs">
                                                            Email address
                                                        </div>

                                                        <TextInput></TextInput>
                                                    </div>
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <div class="text-xs">
                                                            Password
                                                        </div>

                                                        <TextInput></TextInput>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <Button variant="tertiary" class="w-full"
                                        >Cancel</Button
                                    >
                                    <Button
                                        variant="primary"
                                        disabled
                                        class="w-full"
                                        >Add</Button
                                    >
                                </div>
                            </div>
                        </Modal>
                    </div>

                    <div>
                        <!-- <WaiterTable/> -->
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
