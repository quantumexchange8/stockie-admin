<script setup>
import { computed } from "vue";

const emit = defineEmits(["update:checked"]);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    defaultChecked: {
        type: Boolean,
        default: false,
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
        return props.defaultChecked ? true : props.checked;
    },

    set(val) {
        emit("update:checked", val);
    },
});
</script>

<style scoped>
.checkbox {
    background-color: #ffffff;
}
.checkbox:checked {
    padding: 6px 5px 4px 5px;
    border: none;
    background-color: rgb(127 29 29 / 1);
    background-size: auto;
    background-repeat: no-repeat;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M11.6663 3.5L5.24967 9.91667L2.33301 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
}
.checkbox:checked:hover {
    background-color: rgb(153 27 27 / 1);
}
.checkbox:disabled {
    border-width: 1px;
    border-color: rgb(214 220 225 / 1);
    background-color: rgb(236 239 242 / 1);
    opacity: unset;
}
.checkbox:checked:disabled {
    border-width: 0;
    border-color: transparent;
    background-color: #ffc7c9;
    opacity: unset;
}
</style>

<template>
    <input
        type="checkbox"
        :name="props.inputName"
        :value="props.value"
        :checked="proxyChecked"
        @change="proxyChecked = $event.target.checked"
        :class="[
            'checkbox rounded-[100px] border flex items-center justify-center hover:border-red-100',
            '',
            {
                'bg-grey-100 border-grey-200': props.disabled === true,
                'bg-white border-grey-300': props.disabled === false,
            },
        ]"
        :disabled="props.disabled"
    />
</template>
