<script setup>
const props = defineProps({
    value: String,
    for: String,
    required: Boolean 
})
</script>

<template>
    <label 
        :id="props.for"
        :name="props.for"
        class="block text-base font-normal text-gray-700"
    >
        <span v-if="value">
            {{ value }}
            <span class="text-primary-600 text-xs font-medium" v-if="props.required">*</span>
        </span>

        <span v-else><slot />
            <span class="text-primary-600 text-xs font-medium" v-if="props.required"> *</span>
        </span>
    </label>
</template>
