<script setup>
import SearchBar from "@/Components/SearchBar.vue";
import Button from "@/Components/Button.vue";
import { PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import AddTier from "@/Pages/LoyaltyProgramme/Partial/AddTier.vue";
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
    <div
        class="w-full flex flex-col gap-5 p-6 bg-white rounded-[5px] border-primary-100 border-solid border-[1px]"
    >
        <div class="text-md text-primary-900">Current Tier List</div>
        <div class="flex gap-5">
            <SearchBar></SearchBar>

            <Button
                :type="'button'"
                :size="'lg'"
                :iconPosition="'left'"
                class="md:w-[144px] flex items-center gap-2"
                @click="openModal"
                ><template #icon>
                    <PlusIcon />
                </template>
                New Tier
                <Modal
                    :show="isModalOpen"
                    @close="closeModal"
                    :title="'Add New Tier'"
                    :maxWidth="'md'"
                >
                    <AddTier @close="closeModal" />
                </Modal>
            </Button>
            <!--CurrentTierTable-->
        </div>
    </div>
</template>
