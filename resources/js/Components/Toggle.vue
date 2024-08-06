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

<template>
    <label class="relative inline-flex cursor-pointer items-center">
        <input
            type="checkbox"
            :name="props.inputName"
            :value="props.value"
            :checked="proxyChecked"
            :disabled="props.disabled"
            @change="proxyChecked = $event.target.checked"
            class="peer sr-only"
        />
        <label :for="props.inputName" class="hidden"></label>
        <div 
            :class="[
                'peer h-6 w-11 rounded-[40px] bg-grey-200 shadow-[0px_1.4px_2.3px_0px_rgba(188,188,188,0.25)_inset]',
                'after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-[100px]',
                'after:transition-all after:content-[\'\']',
                'peer-checked:after:translate-x-full peer-focus:ring-red-300',
                {
                    'hover:bg-grey-100 peer-checked:hover:bg-red-800 after:bg-white after:shadow-[1px_1px_12.1px_0px_rgba(112,112,112,0.15),_0.5px_0px_1px_0px_rgba(0,0,0,0.10),_0px_-1px_4px_0px_rgba(173,173,173,0.10)_inset] peer-checked:bg-red-900 peer-checked:shadow-[0px_1.4px_2.3px_0px_#48070A_inset]'
                    : props.disabled === false,
                    'cursor-not-allowed after:bg-grey-50 after:shadow-[0px_-1px_4px_0px_rgba(173,173,173,0.10)_inset] peer-checked:bg-red-200 peer-checked:shadow-[0px_1.4px_2.3px_0px_rgba(255,161,165,0.23)_inset]': props.disabled === true,
                }
            ]"
        >
        </div>
    </label>
</template>
