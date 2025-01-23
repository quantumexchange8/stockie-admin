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
    editable: {
        type: Boolean,
        default: false,
    },
    plainStyle: {
        type: Boolean,
        default: false,
    },
    withDescription: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false,
    },
    filter: {
        type: Boolean,
        default: false,
    }
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

// Only if the option array thats been passed has image provided
const getOptionData = (value) => {
    return props.inputArray.find((item) => item.value === value);
}

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
            :required="props.required"
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
            :filter="props.filter"
            :placeholder="placeholder" 
            :ariaLabelledby="inputName"
            :disabled="disabled"
            :optionGroupLabel="grouped ? 'group_name' : null"
            :optionGroupChildren="grouped ? 'items' : null"
            :editable="editable"
            :loading="loading"
            optionLabel="text"
            optionValue='value'
            @change="updateSelectedOption"
            :pt="{
                root: ({ props, state, parent }) => {
                    open = state.overlayVisible ? true : false;
                    if (!filter) state.overlayVisible = !open || !state.focused || !state.clicked ? false : true;
                    else state.overlayVisible = !open ? false : true;
                    return {
                        class: [
                            'inline-flex relative w-full mb-1 max-h-[44px] cursor-pointer select-none',
                            { 
                                'bg-white border border-grey-300': !plainStyle && !props.disabled,
                                'focus:border-primary-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !plainStyle,
                                'active:border-primary-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !plainStyle,
                                'rounded-md': parent.instance.$name !== 'InputGroup',
                                'first:rounded-l-md rounded-none last:rounded-r-md': parent.instance.$name == 'InputGroup',
                                'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup',
                                'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons,
                                'border-grey-300': !props.invalid,
                                'border-primary-500': props.invalid,
                                'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !state.focused && !plainStyle,
                                'outline-none border-primary-300 shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': state.focused && !plainStyle,
                                'border-primary-500 focus:border-primary-500 hover:border-primary-500': props.errorMessage,
                                'border-grey-100 opacity-60 pointer-events-none cursor-default': props.disabled 
                            }
                            // Transitions
                            // 'transition-all',
                            // 'duration-200',
                        ]
                    }
                },
                input: ({ props, parent, state }) => {
                    var _a;
                    return {
                        class: [
                            'block relative flex items-center w-full rounded-none',
                            'text-base',
                            {
                                'bg-transparent border-0': !props.disabled,
                                'bg-grey-50 border border-solid border-grey-100 border-r-0': props.disabled
                            },
                            'cursor-pointer overflow-hidden overflow-ellipsis whitespace-nowrap appearance-none',
                            'transition duration-200 focus:ring-0',
                            'focus:outline-none focus:shadow-none',
                            { 
                                // '!min-h-[44px]': props.modelValue === '',
                                'placeholder-grey-300 text-grey-300 font-medium !min-h-[24px]': plainStyle,
                                'px-4 py-3 placeholder-grey-200 text-grey-200 font-normal !min-h-[44px]': !plainStyle,
                                'hover:text-grey-900': !state.focused && plainStyle,
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
                                'text-primary-900 [&>div>div>div>span]:text-primary-900': context.selected,
                                'bg-primary-50': context.selected ,
                                'hover:bg-grey-100': !context.focused && !context.selected,
                                'hover:bg-primary-highlight-hover': context.selected,
                                'pointer-events-none cursor-default': context.disabled,
                                'cursor-pointer': !context.disabled 
                            },
                        ]
                    }
                },
                panel: {
                    class: [
                        'absolute top-0 left-0 !z-[1110]', 
                        '!mt-1 p-1', 
                        'border-2 border-red-50', 
                        'rounded-[5px]', 
                        'shadow-[0px_15px_23.6px_0px_rgba(102,30,30,0.05)]', 
                        'bg-white', 
                        'text-grey-800',
                        'max-w-min overflow-y-auto scrollbar-webkit scrollbar-thin'
                    ]
                },
                wrapper: {
                    class: [
                        'max-h-[calc(100dvh-20.5rem)]',
                        'overflow-y-auto',
                        'scrollbar-webkit',
                        'scrollbar-thin'
                    ]
                },
                filterInput: {
                    placeholder: 'Search',
                    class: [
                        '!w-full max-h-[44px] flex relative justify-between items-center hover:text-primary-900 active:text-primary-900 focus:text-primary-900',
                        'rounded-[5px] text-primary-900 active:ring-0 border-y border-l border-primary-900 bg-transparent',
                        'hover:border-primary-900 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                        'active:border-primary-900 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                        'focus:border-primary-900 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                        '[&>button]:hover:border-primary-100 placeholder-grey-200',
                        {
                            'border-grey-100': props.disabled === true,
                            'border-grey-300': props.disabled === false,
                            'border-primary-500 focus:border-primary-500 hover:border-primary-500': errorMessage,
                        },
                    ],
                },
                filterContainer: {
                    class: 'flex items-center gap-[10px] flex-[1_0_0] ',
                },
                filterIcon: {
                    class: 'hidden'
                },
                header: {
                    class: 'flex px-4 py-3 items-center gap-[10px] self-stretch w-full'
                },
                trigger: {
                    class: [
                        'flex items-center justify-center shrink-0 text-grey-500 w-10 rounded-tr-md rounded-br-md',
                        { 'border border-l-0 border-solid border-grey-100 bg-grey-50': props.disabled === true },
                    ]
                }
            }"
        >
            <template #value="slotProps">
                <slot name="value" :="slotProps">
                    <!-- If imageOption is true then it will display the option's image when selected an option -->
                    <div class="flex items-center gap-x-2" v-if="slotProps.value && imageOption">
                        <img 
                            :src="getOptionData(slotProps.value).image ? getOptionData(slotProps.value).image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                            alt="WaiterProfileImage"
                            class="size-5 rounded-full"
                        >
                        <p class="text-grey-700 text-base font-normal">{{ getOptionData(slotProps.value).text }}</p>
                    </div>
                </slot>
            </template>
            <template #dropdownicon>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                    :class="open ? 'rotate-270' : 'rotate-180'"
                    class="transition-all duration-200 transform self-center"
                    v-if="!plainStyle"
                >
                    <path
                        d="M12 10L8 6L4 10"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg" v-else>
                    <g clip-path="url(#clip0_4705_26673)">
                        <path d="M4.26826 7.61216H11.7331C12.0352 7.61216 12.3123 7.44478 12.4533 7.17785C12.5941 6.91038 12.5755 6.58772 12.4048 6.33789L8.62642 0.81698C8.49115 0.619372 8.2672 0.501073 8.02765 0.500002C7.78836 0.499418 7.5637 0.616258 7.42734 0.813282L3.59911 6.33429C3.42657 6.58302 3.40649 6.90726 3.54698 7.17575C3.68747 7.44378 3.96533 7.61216 4.26826 7.61216Z" fill="#7E171B"/>
                        <path d="M11.7331 9.38867H4.26825C3.96532 9.38867 3.68746 9.55712 3.54697 9.82512C3.40648 10.0936 3.42659 10.4178 3.5991 10.6665L7.42733 16.1875C7.56369 16.3845 7.78835 16.5014 8.02764 16.5009C8.26719 16.4999 8.49114 16.3814 8.62641 16.1839L12.4048 10.6629C12.5755 10.4131 12.594 10.0904 12.4533 9.82291C12.3123 9.55605 12.0352 9.38867 11.7331 9.38867Z" fill="#7E171B"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_4705_26673">
                            <rect width="16" height="16" fill="white" transform="translate(0 0.5)"/>
                        </clipPath>
                    </defs>
                </svg>
            </template>
            <template #optiongroup="slotProps" v-if="grouped">
                <slot name="optionGroup" :="slotProps.option">
                </slot>
            </template>
            <template #option="slotProps">
                <div class="flex flex-nowrap items-center gap-2 w-fit">
                    <!-- <div class="size-5 bg-primary-100 rounded-full" v-if="imageOption"></div> -->
                    <img 
                        :src="slotProps.option.image ? slotProps.option.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                        alt=''
                        class="size-5 rounded-full"
                        v-if="imageOption"
                    />
                    <span v-if="!withDescription">{{ slotProps.option.text }}</span>

                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]" v-if="withDescription">
                        <span>{{ slotProps.option.text }}</span>
                        <slot name="description" :="slotProps"></slot>
                    </div>
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
