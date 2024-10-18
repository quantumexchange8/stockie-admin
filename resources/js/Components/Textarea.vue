<script setup>
import { onMounted, ref } from "vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    inputName: String,
    modelValue: String,
    labelText: String,
    errorMessage: String,
    hintText: {
        type: String,
        default: "",
    },
    rows: {
        type: Number,
        default: 2,
    },
    placeholder: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    maxCharacters: {
        type: Number,
        default: 524288
    }
});

const {
    inputId,
    inputName,
    labelText,
    errorMessage,
    hintText,
    rows,
    placeholder,
    disabled,
} = props;

const emit = defineEmits(["update:modelValue"]);

const input = ref(null);
const inputCharacters = ref(0);

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

const updateCharacterCount = (event) => {
    inputCharacters.value = event.length;
};

</script>

<template>
    <div class="w-full">
        <template v-if="labelText">
            <div class="flex item-center justify-between">
                <Label 
                    v-if="labelText" 
                    :value="labelText"
                    :for="inputName"
                    :class="[
                        'mb-1 text-xs !font-medium',
                        {
                            'text-grey-900': disabled === false,
                            'text-grey-500': disabled === true,
                        },
                    ]"
                />
                <Label class="text-grey-300 text-xs font-medium" v-if="maxCharacters !== 524288">{{ `${inputCharacters} / ${maxCharacters}` }}</Label>
            </div>
        </template>
        <textarea
            :name="inputName"
            :class="[
                'w-full min-h-[44px] py-3 px-4 mb-1 resize-none',
                'rounded-[5px] text-base text-grey-700 active:ring-0 placeholder:text-grey-200',
                'hover:border-red-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                'active:border-red-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                'focus:border-red-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                {
                    'border-grey-100':
                        disabled === true,
                    'border-grey-300': disabled === false,
                    'border-red-500 focus:border-red-500 hover:border-red-500':
                        errorMessage,
                },
            ]"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value);updateCharacterCount($event.target.value)"
            ref="input"
            :rows="rows"
            :maxlength="maxCharacters"
            :disabled="disabled"
            :placeholder="placeholder"
        />
        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="props.errorMessage" v-if="props.errorMessage" />
    </div>
</template>
