<script setup>
import Button from "@/Components/Button.vue";
</script>

<template>
    <div class="relative flex gap-5">
        <input
            type="text"
            class="border-[#7E171B] rounded-md shadow-sm focus:border-[#7E171B] focus:ring focus:ring-[#7E171B] focus:ring-opacity-50"
            placeholder="Search"
        />
        <Button variant="primary" class="px-6">+ New Waiter</Button>
    </div>
</template>
