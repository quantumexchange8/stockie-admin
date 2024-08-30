<script setup>
import { onMounted, ref } from "vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import InputError from "@/Components/InputError.vue";
import Filter from "@/Components/Filter.vue";

const props = defineProps({
    showFilter: Boolean, 
    inputName: String,
    modelValue: String,
    labelText: String,
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
    prefix: {
        type: String,
        default: "",
    },
});

const {
    inputName,
    labelText,
    errorMessage,
    inputType,
    hintText,
    placeholder,
    disabled,
    showFilter,

} = props;



const input = ref(null);

const focus = () => input.value?.focus();

const emit = defineEmits(["update:modelValue"]);

defineExpose({
    input,
    focus,
});

const clearInput = () => {
    emit('update:modelValue', '');
    input.value.focus(); // Optional: Keep focus on the input after clearing
};

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

</script>

<template>
    <div class="w-full">
        <Label
            :value="labelText"
            :for="inputName"
            :class="[
                'mb-1 text-xs font-medium',
                {
                    'text-grey-900': disabled === false,
                    'text-grey-500': disabled === true,
                },
            ]"
            v-if="labelText"
        >
        </Label>
        <div class="relative">
            <div
                :class="[
                    'w-full max-h-[44px] pl-4 py-3 flex justify-between items-center hover:text-primary-300 active:text-primary-200 focus:text-primary-300',
                    'rounded-[5px] text-primary-900 active:ring-0 border-y border-l border-primary-900 bg-transparent',
                    'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'active:border-primary-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'focus:border-primary-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                    '[&>button]:hover:border-primary-100',
                    {
                        'border-r': !showFilter,
                        'border-grey-100': props.disabled === true,
                        'border-grey-300': props.disabled === false,
                        'border-primary-500 focus:border-primary-500 hover:border-primary-500':
                            errorMessage,
                    },
                ]"
            >
                <slot name="prefix">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 20 20"
                        fill="none"
                        class="flex-shrink-0"
                    >
                        <path
                            d="M17.5 17.5L13.875 13.875M15.8333 9.16667C15.8333 12.8486 12.8486 15.8333 9.16667 15.8333C5.48477 15.8333 2.5 12.8486 2.5 9.16667C2.5 5.48477 5.48477 2.5 9.16667 2.5C12.8486 2.5 15.8333 5.48477 15.8333 9.16667Z"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </slot>
                <input
                    :name="inputName"
                    :class="[
                        'w-full border-none text-base font-normal bg-transparent',
                        'text-base text-grey-700 active:ring-0 focus:ring-0 placeholder:text-grey-200',
                    ]"
                    :type="inputType"
                    :value="modelValue"
                    @input="$emit('update:modelValue', $event.target.value)"
                    ref="input"
                    :disabled="disabled"
                    :placeholder="placeholder"
                >
                    <button
                        type="button"
                        v-if="modelValue && !disabled"
                        class="flex items-center justify-center w-8 h-8 text-gray-400 hover:text-gray-600 focus:outline-none"
                        @click="clearInput"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" class="flex-shrink-0">
                            <rect width="16" height="16" rx="8" fill="#ECEFF2"/>
                            <path d="M10 6L6 10M6 6L10 10" stroke="#546775" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </input>
             
                <Filter v-if="showFilter">
                    <template #default="{ hideOverlay }">
                        <slot :hideOverlay="hideOverlay"></slot>
                    </template>
                </Filter>
            </div>
        

        </div>

        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>
