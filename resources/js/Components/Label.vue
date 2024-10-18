<script setup>
const props = defineProps({
    value: String,
    for: String
})
</script>

<template>
    <label 
        :id="props.for"
        :name="props.for"
        class="block text-base font-normal text-gray-700"
    >
        <span v-if="value">{{ value }}</span>

        <span v-else><slot /></span>
    </label>
</template>
