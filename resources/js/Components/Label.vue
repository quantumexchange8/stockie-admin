<script setup>
defineProps({
    value: String,
})
</script>

<template>
    <label 
        class="block text-base font-normal text-gray-700"
    >
        <span v-if="value">{{ value }}</span>

        <span v-else><slot /></span>
    </label>
</template>
