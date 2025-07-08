<script setup>
import { onMounted, ref, watch } from "vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import InputError from "@/Components/InputError.vue";
import { EyeIcon, EyeOffIcon } from "./Icons/solid";
import { useInputValidator } from '@/Composables';

const props = defineProps({
    inputName: String,
    modelValue: String,
    labelText: {
        type: String,
        default: "",
    },
    errorMessage: String,
    inputType: {
        type: String,
        default: "text",
    },
    hintText: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    iconPosition: {
        type: String,
        default: "",
    },
    required: {
        type: Boolean,
        default: false,
    },
    withDecimal: {
        type: Boolean,
        default: false,
    },
    maxlength: Number,
});

const {
    inputId,
    inputName,
    labelText,
    errorMessage,
    inputType,
    hintText,
    placeholder,
    disabled,
    required
} = props;

const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(["update:modelValue", "blur"]);

const input = ref(null);
const inputLabel = ref('');

const focus = () => input.value?.focus();

defineExpose({
    input,
    focus,
});

const showPassword = ref(false);

const toggleShow = () => {
    showPassword.value = !showPassword.value;
};

// // Check and allows only the following keypressed
// const isNumber = (e, withDot = true) => {
//     if (props.inputType === 'number') {
//         const keysAllowed = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
//         if (withDot) {
//             keysAllowed.push('.');
//         }
//         const keyPressed = e.key;
        
//         if (!keysAllowed.includes(keyPressed)) {
//             e.preventDefault();
//         }
//     }
// };

const handleInput = (e) => {
    let value = e.target.value;
    
    if (props.inputType === 'number') {
        // First, remove all invalid characters
        const regex = props.withDecimal ? /[^0-9.]/g : /[^0-9]/g;
        value = value.replace(regex, '');
        
        // Then handle decimal formatting
        if (props.withDecimal) {
            const parts = value.split('.');
            
            // Ensure only one decimal point
            if (parts.length > 1) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Limit to 2 decimal places
            if (parts.length > 1 && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            
            // Handle cases like ".5" -> "0.5"
            if (value.startsWith('.')) {
                value = '0' + value;
            }
        }
        
        // Update the input's value directly (important for mobile)
        e.target.value = value;
        emit('update:modelValue', value);
    } else {
        emit('update:modelValue', value);
    }
};

watch(
    () => props.labelText,
    (newValue) => {
        inputLabel.value = newValue;
    },
    { immediate: true }
);

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});
</script>

<template>
    <div class="w-full">
        <Label
            :for="inputName"
            :required="required"
            :class="[
                'mb-1 text-xs !font-medium',
                {
                    'text-grey-900': disabled === false,
                    'text-grey-500': disabled === true,
                },
            ]"
            v-if="labelText !== ''"
        >
            {{ inputLabel }}
        </Label>
        <div class="relative">
            <div
                v-if="$slots.prefix"
                :class="[
                    'absolute min-h-[44px] max-h-[44px] flex py-3 text-base mb-1 items-center text-grey-700',
                    iconPosition === 'left' ? 'left-0  pl-3' : 'right-0 pr-3',
                ]"
            >
                <slot name="prefix"></slot>
            </div>

            <input
                :name="inputName"
                :class="[
                    'w-full min-h-[44px] max-h-[44px] py-3 px-4 mb-1',
                    'rounded-[5px] text-base text-grey-700 active:ring-0 placeholder:text-grey-200',
                    'hover:border-red-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'active:border-red-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'focus:border-red-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                    {
                        'border-grey-100': disabled === true,
                        'border-grey-300': disabled === false,
                        'border-red-500 focus:border-red-500 hover:border-red-500':
                            errorMessage,
                        'pl-11 pr-11 text-center': $slots.prefix,
                        'pl-4 pr-4': !$slots.prefix,
                        'text-center': iconPosition === 'right',
                        '[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none':
                            inputType === 'number',
                    },
                ]"
                :type="
                    inputType !== 'password'
                        ? 'text'
                        : showPassword
                        ? 'text'
                        : 'password'
                "
                :inputmode="inputType === 'number' ? 'decimal' : 'text'"
                :value="modelValue"
                @input="handleInput($event)"
                @keydown="inputType === 'number' ? isValidNumberKey($event, withDecimal) : undefined"
                @blur="$emit('blur', $event)"
                ref="input"
                :autocomplete="inputType === 'password' ? 'current-password' : ''"
                :disabled="disabled"
                :maxlength="maxlength"
                :placeholder="placeholder"
            />
            <span
                v-if="inputType === 'password'"
                class="absolute inset-y-0 right-0 flex items-center px-4"
            >
                <button
                    type="button"
                    class="justify-center max-h-max"
                    @click="toggleShow"
                >
                    <component
                        :is="showPassword ? EyeOffIcon : EyeIcon"
                        class="flex-shrink-0 w-5 h-5 cursor-pointer text-cyan-500"
                        aria-hidden="true"
                    />
                </button>
            </span>
        </div>
        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="props.errorMessage" v-if="props.errorMessage" />
    </div>
</template>
