<script setup>
import { onMounted, ref, watch } from "vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import { CircledMinusIcon, CircledPlusIcon } from "./Icons/solid";
const props = defineProps({
    inputName: String,
    modelValue: [Number, String],
    labelText: String,
    errorMessage: String,
    placeholder: {
        type: String,
        default: "",
    },
    minValue: {
        type: Number,
        default: 0,
    },
    maxValue: Number,
    withDecimal: {
        type: Boolean,
        default: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "onChange"]);

const input = ref(null);
const inputValue = ref(props.modelValue);
const initialInputvalue = ref(props.modelValue);
const isTypingState = ref(false);

const focus = () => input.value?.focus();

defineExpose({
    input,
    focus,
});

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

// Watch the prop to update local state when parent changes it
watch(() => props.modelValue, (newVal) => {
    inputValue.value = Number(newVal) || props.minValue; // Fallback to minValue if NaN
});

const emitValue = () => {
    const numValue = Number(inputValue.value);
    emit('update:modelValue', numValue);
    emit("onChange", numValue);
}

const increment = () => {
    if (!props.disabled) {
        if (props.maxValue !== undefined && inputValue.value >= props.maxValue) return;
        
        inputValue.value += 1;
        updateState();
        emitValue();
    }
}

const decrement = () => {
    if (!props.disabled && inputValue.value > props.minValue) {
        inputValue.value -= 1;
        updateState();
        emitValue();
    }
}

const updateValue = (event) => {
    let value = event.target.value;
    
    // Allow empty string temporarily (for better UX when deleting)
    if (value === '') {
        inputValue.value = props.minValue;
        event.target.value = props.minValue;
        emitValue();
        return;
    }

    // First, remove all invalid characters
    const regex = /[^0-9]/g;
    value = value.replace(regex, '');

    // Convert to number and apply constraints
    let numericValue = value === '' ? props.minValue : Number(value);
    
    // Apply min/max constraints
    numericValue = Math.max(numericValue, props.minValue);

    if (props.maxValue !== undefined) {
        numericValue = Math.min(numericValue, props.maxValue);
    }

    // Update the value
    inputValue.value = numericValue < 0 ? 0 : numericValue;
    event.target.value = numericValue < 0 ? 0 : numericValue;
    emitValue();
};

const updateState = () => {
    if (inputValue.value === initialInputvalue.value) {
        isTypingState.value = false;

    } else {
        isTypingState.value = true;
    }
}

</script>

<template>
    <div class="w-full flex flex-col">
        <Label
            :value="labelText"
            :for="inputName"
            :class="[
                'mb-1 text-xs !font-medium',
                {
                    'text-grey-900': disabled === false,
                    'text-grey-500': disabled === true,
                },
            ]"
            v-if="labelText !== ''"
        >
        </Label>
        <div class="flex items-center justify-center flex-row gap-4">
            <CircledMinusIcon
                :class="[
                    'w-6 h-6 flex-shrink-0',
                    {
                        '[&>rect]:fill-primary-900 text-primary-50': (isTypingState || inputValue !== 0) && !disabled,
                        '[&>rect]:fill-grey-100 text-grey-300': !isTypingState || disabled || inputValue === 0 || inputValue <= minValue,
                        '[&>rect]:hover:fill-primary-200 hover:text-primary-900': !disabled && inputValue !== 0 && inputValue > minValue,
                        'pointer-events-none': disabled || inputValue <= minValue,
                    }
                ]"
                @click="decrement"
            />
            <input
                :name="inputName"
                :class="[
                    'w-full min-w-16 min-h-[44px] max-h-[44px] py-3 px-4 mb-1 text-center',
                    'rounded-[5px] text-base text-grey-700 active:ring-0 placeholder:text-grey-200',
                    'hover:border-red-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'active:border-red-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'focus:border-red-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                    '[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none',
                    {
                        'border-grey-100': disabled === true,
                        'border-grey-300': disabled === false,
                        'border-red-500 focus:border-red-500 hover:border-red-500':
                            errorMessage,
                    },
                ]"
                :inputmode="'decimal'"
                :type="'text'"
                :value="inputValue"
                @input="updateValue"
                @change="updateState"
                ref="input"
                :disabled="disabled"
                :placeholder="placeholder"
            />
            <CircledPlusIcon
                :class="[
                    'w-6 h-6 flex-shrink-0',
                    {
                        '[&>rect]:fill-primary-900 text-primary-50 [&>rect]:hover:fill-primary-200 hover:text-primary-900': !disabled || ((maxValue && inputValue < maxValue)),
                        '[&>rect]:fill-grey-100 text-grey-300 pointer-events-none': disabled || (maxValue && inputValue >= maxValue),
                    }
                ]"
                @click="increment"
            />
        </div>
        <InputError :message="props.errorMessage" v-if="props.errorMessage" />
    </div>
</template>
