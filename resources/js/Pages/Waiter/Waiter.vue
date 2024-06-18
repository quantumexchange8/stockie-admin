<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Button from "@/Components/Button.vue";
import SearchBar from "@/Components/SearchBar.vue";
import SalesPerformance from "./Partials/SalesPerformance.vue";
import AddWaiter from "./Partials/AddWaiter.vue";
import { PlusIcon } from "@/Components/Icons/solid";
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
const isModalOpen = ref(false);
const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};
const inputValue = ref("");
</script>

<template>
    <!-- <Head title="Waiter"></Head> -->

    <AuthenticatedLayout>
        <template #header> Waiter </template>

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
                    <div class="w-full flex items-center gap-5">
                        <!-- Search Bar -->
                        <SearchBar
                            placeholder="Search"
                            :show-filter="false"
                            v-model="inputValue"
                        >
                        </SearchBar>

                        <Button
                            :type="'button'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            class="md:w-[166px] flex items-center gap-2"
                            @click="openModal"
                            ><template #icon>
                                <PlusIcon />
                            </template>

                            New Waiter
                            <Modal
                                :show="isModalOpen"
                                @close="closeModal"
                                :title="'Add New Waiter'"
                                :maxWidth="'lg'"
                            >
                                <AddWaiter @close="closeModal" />
                            </Modal>
                        </Button>
                    </div>

                    <div>
                        <!-- <WaiterTable/> -->
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
