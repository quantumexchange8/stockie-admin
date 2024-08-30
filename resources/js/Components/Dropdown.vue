<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import InputError from "./InputError.vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import Dropdown from 'primevue/dropdown';

const props = defineProps({
    dataValue: {
        type: [String, Number],
        default: ''
    },
    labelText: String,
    errorMessage: String,
    inputName: String,
    inputArray: {
        type: [Array, Object],
        default: () => [],
    },
    hintText: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Select",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    iconOptions: {
        type: Object,
        default: () => ({}),
    },
    grouped: {
        type: Boolean,
        default: false,
    },
    imageOption: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits(["update:modelValue", "onChange"]);

const open = ref(false);
const localValue = ref(props.dataValue);

// Reactive options based on props
const options = ref(props.inputArray);

watch(
  () => props.inputArray,
  (newValue) => {
    options.value = newValue;
  },
  { immediate: true }
);

watch(
  () => props.dataValue,
  (newValue) => {
    localValue.value = newValue;
  },
  { immediate: true }
);

const updateSelectedOption = (option) => {
    emits('update:modelValue', option.value);
    emits("onChange", option.value);
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.p-dropdown')) {
    open.value = false;
  }
};

onMounted(() => {
    // localValue.value = props.modelValue;
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="w-full">
        <Label
            :value="props.labelText"
            :for="props.inputName"
            :class="[
                'mb-1 text-xs !font-medium',
                {
                    'text-grey-900': !props.disabled,
                    'text-grey-500': props.disabled,
                },
            ]"
            v-if="labelText"
        >
        </Label>
        <Dropdown 
            v-model="localValue" 
            :options="options" 
            :placeholder="placeholder" 
            :ariaLabelledby="inputName"
            :disabled="disabled"
            :optionGroupLabel="grouped ? 'group_name' : null"
            :optionGroupChildren="grouped ? 'items' : null"
            optionLabel="text"
            optionValue='value'
            @change="updateSelectedOption"
            @click="open = !open"
            :pt="{
                root: ({ props, state, parent }) => {
                    open = state.overlayVisible ? true : false;
                    state.overlayVisible = !open || !state.focused || !state.clicked ? false : true;
                    return {
                        class: [
                            'inline-flex relative w-full max-h-[44px]',
                            'bg-white border border-grey-300',
                            'focus:border-primary-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                            'active:border-primary-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                            'cursor-pointer select-none',
                            { 
                                'rounded-md': parent.instance.$name !== 'InputGroup',
                                'first:rounded-l-md rounded-none last:rounded-r-md': parent.instance.$name == 'InputGroup',
                                'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup',
                                'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons,
                                'border-grey-300': !props.invalid,
                                'border-primary-500': props.invalid,
                                'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !state.focused,
                                'border-primary-500 focus:border-primary-500 hover:border-primary-500': props.errorMessage,
                                'outline-none border-primary-300 shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': state.focused,
                                'border-grey-100 opacity-60 pointer-events-none cursor-default': props.disabled 
                            }
                            // Transitions
                            // 'transition-all',
                            // 'duration-200',
                        ]
                    }
                },
                input: ({ props, parent }) => {
                    var _a;
                    return {
                        class: [
                            'block relative flex items-center w-full px-4 py-3 rounded-none',
                            'text-grey-200 text-base font-normal bg-transparent border-0',
                            'cursor-pointer overflow-hidden overflow-ellipsis whitespace-nowrap appearance-none',
                            'transition duration-200',
                            'focus:outline-none focus:shadow-none',
                            { 
                                'text-grey-700 ': props.modelValue != null && props.modelValue !== '', 
                                'text-grey-200': props.modelValue == null || props.modelValue !== '',
                                'pr-7': props.showClear,
                                // Filled State *for FloatLabel
                                filled: ((_a = parent.instance) == null ? void 0 : _a.$name) == 'FloatLabel' && props.modelValue !== null 
                            },
                        ]
                    };
                },
                item: ({ context }) => {
                    return {
                        class: [
                            'relative rounded-none m-0 py-3 px-5',
                            'font-normal leading-none border-0',
                            'overflow-hidden whitespace-nowrap',
                            'transition-shadow duration-200',
                            'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring focus-visible:ring-inset focus-visible:ring-primary-400/50',
                            {
                                'text-grey-700': !context.focused && !context.selected,
                                'bg-grey-50': context.focused && !context.selected,
                                'text-grey-800': context.focused && !context.selected,
                                'text-primary-900': context.selected,
                                'bg-primary-50': context.selected ,
                                'hover:bg-grey-100': !context.focused && !context.selected,
                                'hover:bg-primary-highlight-hover': context.selected,
                                'pointer-events-none cursor-default': context.disabled,
                                'cursor-pointer': !context.disabled 
                            },
                        ]
                    }
                },
            }"
        >
            <template #dropdownicon>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                    :class="open ? 'rotate-270' : 'rotate-180'"
                    class="transition-all duration-200 transform self-center"
                >
                    <path
                        d="M12 10L8 6L4 10"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #optiongroup="slotProps" v-if="grouped">
                <slot name="optionGroup" :="slotProps.option">
                </slot>
            </template>
            <template #option="slotProps">
                <div class="flex flex-nowrap items-center gap-2">
                    <div class="size-5 bg-primary-100 rounded-full" v-if="imageOption"></div>
                    <span>{{ slotProps.option.text }}</span>
                    <span
                        class="absolute right-0 pr-4"
                        v-if="props.iconOptions"
                    >
                        <component
                            :is="props.iconOptions[slotProps.option.text]"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                        />
                    </span>
                </div>
                <!-- <slot name="option" :="slotProps.option">
                </slot> -->
            </template>
        </Dropdown>
        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>

<!-- group option should pass this format of json data
const unitArrs = ref([
    {
        'group_name': 'Heineken',
        'items': [
            {
                'text': 'Bottle',
                'value': 'Bottle'
            },
            {
                'text': 'Can',
                'value': 'Can'
            }
        ],
    }, 
]); -->
