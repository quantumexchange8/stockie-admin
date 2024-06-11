<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
	inputName: {
		type: String,
	},
    disabled: {
        type: Boolean,
        default: false,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<style scoped>
    .radio {
        background-color: #FFFFFF;
    }
    .radio:checked {
        background-color: rgb(127 29 29 / 1);
        background-size: auto;
        background-repeat: no-repeat;
    }
    .radio:checked:hover {
        background-color: rgb(153 27 27 / 1);
    }
    .radio:disabled {
        border-width: 1px;
        border-color: rgb(214 220 225 / 1);
        background-color: rgb(236 239 242 / 1);
        opacity: unset;
    }
    .radio:checked:disabled {
        border-width: 1px;
        border-color: transparent;
        background-color: #FFC7C9;
        opacity: unset;
    }
</style>

<template>
    <input
        type="radio"
        :name="props.inputName"
        :value="props.value"
        :checked="proxyChecked"
        :disabled="props.disabled"
        :class="[
            'radio rounded-[100px] border flex items-center justify-center hover:border-red-100',
            '',
            {
                'bg-grey-100 border-grey-200': props.disabled === true,
                'bg-white border-grey-300': props.disabled === false,
            }
        ]"
    />
</template>
