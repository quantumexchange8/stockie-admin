<script setup>
import MultiSelect from "primevue/multiselect";
import { onMounted, onUnmounted, ref, watch } from "vue";
import Label from "./Label.vue";

const props = defineProps({
    labelText: {
        type: String,
        default: 'Label Text',
    },
    dataValue: {
        type: [String, Array],
        default: '',
    },
    inputName: String,
    errorMessage: String,
    placeholder: {
        type: String,
        default: 'Select',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    inputArray: {
        type: [Array, Object],
        default: () => [],
    }
})

const emits = defineEmits(['update:modelValue', 'onChange']);
const open = ref(false);
const localValue = ref(props.dataValue);
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
    emits('onChange', option.value);
};

const handleClickOutside = (event) => {
    if(!event.target.closest('.p-multiselect')) {
        open.value = false;
  }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
})
</script>

<template>
    <div class="w-full">
        <Label
            :value="props.labelText"
            :for="props.inputName"
            :class="[
                'mb-1 text-xs !font-medium w-full',
                {
                    'text-grey-900': !props.disabled,
                    'text-grey-500': props.disabled,
                },
            ]"
            v-if="labelText"
        >
        </Label>
        <!-- options use props to pass in the selections -->
        <MultiSelect 
            v-model="localValue" 
            :options="options"
            optionLabel="text"
            optionValue="value"
            :placeholder="placeholder"
            :maxSelectedLabels="7" 
            display="chip"
            filter 
            @change="updateSelectedOption"
            @click="open = !open"
            :pt="{
                root: ({ props, state, parent }) => {
                    open = state.overlayVisible ? true : false;
                    // state.overlayVisible = !open || !state.focused && !state.clicked ? false : true;
                    return {
                        class: [
                            'inline-flex flex-row relative min-h-[44px] w-full justify-between py-2 pl-2 pr-4 ',
                            'bg-white border border-grey-300',
                            'focus:border-primary-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                            'active:border-primary-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                            'cursor-pointer select-none overflow-hidden',
                            { 
                                'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons,
                                'border-grey-300': !props.invalid,
                                'border-primary-500': props.invalid,
                                'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !state.focused,
                                'border-primary-500 focus:border-primary-500 hover:border-primary-500': props.errorMessage,
                                'outline-none border-primary-300 shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': state.focused,
                                'border-grey-100 opacity-60 pointer-events-none cursor-default': props.disabled 
                            }
                        ]
                    }
                },
                label: {
                    class: 'flex max-w-full items-center gap-3'
                },
                token: {
                    class: 'flex p-1 items-center gap-[10px] rounded-[3px] bg-primary-25'
                },
                tokenLabel: {
                    class: 'text-grey-700 text-base font-normal whitespace-nowrap overflow-hidden'
                },
                trigger: {
                    class: 'flex items-center text-primary-300'
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
                            'flex max-h-[44px] px-4 py-2 items-center gap-[10px] self-stretch',
                            'font-normal leading-none border-0',
                            'overflow-hidden whitespace-nowrap',
                            'transition-shadow duration-200',
                            'text-grey-700 text-base font-normal',
                            'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring focus-visible:ring-inset focus-visible:ring-primary-400/50',
                            {
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
                        'overflow-auto scrollbar-thin scrollbar-webkit'
                    ]
                },
                filterInput: {
                    placeholder: 'Search',
                    class: [
                        '!w-full max-h-[44px] flex relative justify-between items-center hover:text-primary-300 active:text-primary-200 focus:text-primary-300',
                        'rounded-[5px] text-primary-900 active:ring-0 border-y border-l border-primary-900 bg-transparent',
                        'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                        'active:border-primary-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                        'focus:border-primary-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                        '[&>button]:hover:border-primary-100',
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
                headerCheckbox: {
                    class: 'flex size-5 justify-center items-center',
                    input: `rounded-full focus:ring-white
                            checked:bg-primary-900 
                            active:bg-primary-900
                            hover:checked:bg-primary-900
                            focus:checked:bg-primary-900
                            checked:focus:border-none`,
                    box: '!hidden',
                },
                closeButton: {
                    class: 'flex items-end'
                },
                // wrapper: {
                //     class: 'flex max-h-[44px] px-4 py-2 items-center gap-[10px] self-stretch rounded-t-0.5'
                // },
                itemCheckbox: {
                    class: 'flex size-5 justify-center items-center',
                    input: `rounded-full focus:ring-white
                            checked:bg-primary-900 
                            active:bg-primary-900
                            hover:checked:bg-primary-900
                            focus:checked:bg-primary-900
                            checked:focus:border-none`,
                    box: '!hidden',
                },
                input: {
                    class: 'bg-primary-900'
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
                    class="transition-all duration-200 transform self-center items-center"
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

            <template #removetokenicon>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.3337 4.66602L4.66699 11.3327M4.66699 4.66602L11.3337 11.3327" stroke="#7E171B" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </template>

            <template #closeicon>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 7L7 17M7 7L17 17" stroke="#7E171B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </template>

            <template #option="slotProps">
                <div class="flex items-center gap-[10px]">
                    <slot name="optionLabel"></slot>
                    <div>{{ slotProps.option.text }}</div>
                </div>
            </template>
        </MultiSelect>

    </div>
</template>


