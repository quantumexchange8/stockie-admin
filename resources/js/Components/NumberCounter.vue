<script setup>
import { onMounted, ref, watch } from "vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import InputError from "@/Components/InputError.vue";
import { EyeIcon, EyeOffIcon } from "./Icons/solid";
const props = defineProps({
    inputName: String,
    modelValue: Number,
    labelText: String,
    errorMessage: String,
    placeholder: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

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
    inputValue.value = newVal;
});

const increment = () => {
    inputValue.value += 1;
    updateState();
    emit('update:modelValue', inputValue.value);
}

const decrement = () => {
    inputValue.value = inputValue.value > 0 ? inputValue.value - 1 : 0;
    updateState();
    emit('update:modelValue', inputValue.value);
}

const updateValue = (event) => {
    const value = parseInt(event.target.value) || 0;
    inputValue.value = value;
    emit('update:modelValue', value);
}

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
            <svg 
                width="20" 
                height="20" 
                viewBox="0 0 20 20" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg" 
                class="w-6 h-6" 
                :class="[
                    isTypingState ? 'text-primary-900' : 'text-grey-100'
                ]"
                @click="decrement"
            >
                <rect 
                    width="20" 
                    height="20" 
                    rx="10" 
                    fill="currentColor"
                />
                <path 
                    d="M7 10.0834H13.1667" 
                    :class="[
                        isTypingState ? 'stroke-primary-50' : 'stroke-grey-300'
                    ]"
                    stroke-width="1.48002" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                />
            </svg>
            <input
                :name="inputName"
                :class="[
                    'w-16 min-h-[44px] max-h-[44px] py-3 px-4 mb-1 text-center',
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
                :type="'number'"
                :value="inputValue"
                @input="updateValue"
                @change="updateState"
                ref="input"
                :disabled="disabled"
                :placeholder="placeholder"
            />
            <svg 
                width="20" 
                height="20" 
                viewBox="0 0 20 20" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg" 
                class="w-6 h-6" 
                @click="increment"
            >
                <rect 
                    width="20" 
                    height="20" 
                    rx="10" 
                    fill="#7E171B"
                />
                <path 
                    d="M10.0834 7V13.1667M7 10.0834H13.1667" 
                    stroke="#FFF1F2" stroke-width="1.48002" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                />
            </svg>

        </div>
        <InputError :message="props.errorMessage" v-if="props.errorMessage" />
    </div>
</template>
