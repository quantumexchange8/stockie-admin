<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import SearchBar from "@/Components/SearchBar.vue";
import { PlusIcon, SearchIcon } from "@/Components/Icons/solid";
import SalesPerformance from "./SalesPerformance.vue";
import WaiterTable from "./WaiterTable.vue";
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
            <div class="w-full flex flex-col gap-5 justify-center items-center">
                <div class="w-full flex md:gap-[21px] justify-center">
                    <div
                        class="w-full p-4 sm:p-8 bg-white sm:rounded-lg"
                        style="border: 1px solid #ffe1e2"
                    >
                        <SalesPerformance class="w-full" />
                    </div>
                    <div
                        class="w-full p-4 sm:p-8 bg-white sm:rounded-lg"
                        style="border: 1px solid #ffe1e2"
                    >
                        <SalesPerformance />
                    </div>
                </div>

                <div
                    class="w-full flex flex-col gap-4 p-4 sm:p-6 bg-white sm:rounded-lg border-[#ffe1e2] border-solid border-[1px]"
                >
                    <div class="flex items-center gap-5">
                        <SearchBar placeholder="Search"></SearchBar>

                        <Button
                            variant="primary"
                            size="lg"
                            class="w-[166px]"
                            @click="openModal"
                        >
                            <PlusIcon aria-hidden="true" /> New Waiter
                        </Button>

                        <Modal
                            :show="isModalOpen"
                            @close="closeModal"
                            :title="'Add New Waiter'"
                            :maxWidth="'lg'"
                        >
                            <div class="w-full flex flex-col md:gap-6">
                                <div class="w-full flex md:gap-6">
                                    <div
                                        class="w-[373px] h-[373px] p-3 border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                                    ></div>
                                    <div
                                        class="w-full flex flex-col md:gap-[48px]"
                                    >
                                        <div class="flex flex-col md:gap-6">
                                            <div
                                                class="md:text-[20px] text-[#48070A]"
                                            >
                                                Personal Detail
                                            </div>
                                            <div class="flex flex-col md:gap-4">
                                                <TextInput
                                                    label-text="Full name"
                                                    :placeholder="'eg: John Doe'"
                                                ></TextInput>
                                                <div class="flex md:gap-4">
                                                    <TextInput
                                                        label-text="Phone number"
                                                        :placeholder="'+60'"
                                                    ></TextInput>
                                                    <TextInput
                                                        label-text="Email address"
                                                        :placeholder="'eg: johndoe@gmail.com'"
                                                    ></TextInput>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col md:gap-6">
                                            <div
                                                class="md:text-[20px] text-[#48070A]"
                                            >
                                                Work Detail
                                            </div>
                                            <div class="flex md:gap-4">
                                                <TextInput
                                                    label-text="Staff ID"
                                                    :placeholder="'eg: J8192'"
                                                ></TextInput>
                                                <TextInput
                                                    label-text="Basic salary (per month)"
                                                    :placeholder="'RM'"
                                                ></TextInput>
                                            </div>
                                        </div>
                                        <div class="flex flex-col md:gap-6">
                                            <div
                                                class="md:text-[20px] text-[#48070A]"
                                            >
                                                Account Detail
                                            </div>
                                            <div class="flex md:gap-4">
                                                <TextInput
                                                    label-text="Email address"
                                                    :placeholder="'for Stockie account log-in'"
                                                ></TextInput>
                                                <TextInput
                                                    label-text="Password"
                                                    :placeholder="'for Stockie account log-in'"
                                                ></TextInput>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex md:gap-4">
                                    <Button variant="tertiary">Cancel</Button>
                                    <Button variant="tertiary" disabled
                                        >Add</Button
                                    >
                                </div>
                            </div>
                        </Modal>
                    </div>

                    <div>
                        <!-- <WaiterTable/> -->
                        <WaiterTable />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
