<script setup>
import { onMounted, ref } from 'vue'
import Label from '@/Components/Label.vue'
// import Tooltip from '@/Components/Tooltip.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
	inputName: String,
	modelValue: String,
    labelValue: String,
    errorMessage: String,
	inputType: {
		type: String,
		default: 'text'
	},
    withTooltip: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const { inputId, inputName, labelValue, errorMessage, inputType, disabled } = props

defineEmits(['update:modelValue'])

const input = ref(null)

const focus = () => input.value?.focus()

defineExpose({
    input,
    focus
})

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus()
    }
})

</script>

<template>
	<div class="input-wrapper">
        <span class="flex flex-row gap-2" v-if="labelValue !== ''">
            <Label
                :value="labelValue"
                :for="inputName"
                class="mb-2"
            >
            </Label>
            <!-- <Tooltip v-if="withTooltip">
                <slot></slot>
            </Tooltip> -->
        </span>
        <input
            :name="inputName"
            :class="[
                'min-w-[268px] max-w-[268px] min-h-[44px] max-h-[44px] py-3 px-4 rounded-[5px]',
                'placeholder-grey-200 text-base text-grey-700 border-grey-300 active:ring-0',
                'hover:border-red-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                'active:border-red-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
            ]"
            :type="inputType"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            ref="input"
            :disabled="disabled"
            :placeholder="'Placeholder Text'"
        />
        <InputError
            :message="errorMessage"
            v-if="errorMessage"
        />
    </div>
</template>
