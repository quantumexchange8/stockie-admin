<script setup>
import { onMounted, ref } from "vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import InputError from "@/Components/InputError.vue";
import { SearchIcon } from "@/Components/Icons/solid";

const typing = ref(false);

const props = defineProps({
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
} = props;

defineEmits(["update:modelValue"]);

const input = ref(null);

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

const handleInput = (event) => {
    typing.value = true;
    $emit("update:modelValue", event.target.value);
};
</script>

<template>
    <div class="relative w-full">
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
            v-if="labelText !== ''"
        />
        <div class="relative">
            <input
                :name="inputName"
                :class="[
                    'w-full min-h-[44px] max-h-[44px] py-3 px-12 mb-1',
                    'rounded-[5px] text-base active:ring-0 placeholder:text-grey-200',
                    'hover:border-red-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'active:border-red-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                    'focus:border-red-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                    'focus:outline-none',
                    {
                        'text-[#45535F]': typing,
                        'text-grey-700': !typing,
                        'border-grey-100': disabled === true,
                        'border-[#7E171B]': !disabled,
                        'border-red-500 focus:border-red-500': errorMessage,
                    },
                ]"
                :type="inputType"
                :value="modelValue"
                @input="handleInput"
                ref="input"
                :disabled="disabled"
                :placeholder="placeholder"
            />
            <SearchIcon
                class="absolute top-1/2 transform -translate-y-1/2 left-4 w-5 h-5 text-[#7E171B] pointer-events-none"
                :style="{ visibility: modelValue ? 'hidden' : 'visible' }"
            />
        </div>
        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>
