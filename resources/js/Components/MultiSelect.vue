<script setup>
import MultiSelect from "primevue/multiselect";
import { onMounted, onBeforeUnmount, onUnmounted, ref, watch } from "vue";
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
    },
    withImages: {
        type: Boolean,
        default: false,
    },
    filter: {
        type: Boolean,
        default: true
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emits = defineEmits(['update:modelValue', 'onChange']);
const open = ref(false);
const isOverlayOpen = ref(false);
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
    // const multiSelectWrapper = document.querySelector(".p-multiselect");
    // const multiSelectOverlay = document.querySelector(".p-multiselect-panel");
    // Close only if click happens outside the dropdown and overlay
    // open.value = true;
    // if (event.target.closest('.p-multiselect')) {
    // }
};

const closeOverlay = () => {
    open.value = false;
};

// Local click outside directive
// const vClickOutside = {
//     beforeMount(el, binding) {
//         el.clickOutsideEvent = (event) => {
//             // Check if the click is outside the multiselect component but inside the modal
//             const modal = el.closest('.modal-panel'); // Replace '.modal-class' with the actual modal's class

//             // Close only if the click is outside the multiselect and still inside the modal
//             if (!(el.contains(event.target)) && modal && modal.contains(event.target)) {
//                 binding.value(event);
//             }
//         };
//         // Attach the event listener to the document
//         document.addEventListener('click', el.clickOutsideEvent);
//     },
//     unmounted(el) {
//         // Remove the event listener when the element is unmounted
//         document.removeEventListener('click', el.clickOutsideEvent);
//     },
// };

// const vClickOutside = {
//     mounted(element, binding) {
//         const clickEventHandler = (event) => {
//             // Check if the clicked element is outside the directive's element
//             if (!element.contains(event.target) && typeof binding.value === 'function') {
//                 binding.value(event); // Execute the provided callback
//             }
//         };
//         element.__clickedOutsideHandler__ = clickEventHandler;
//         document.addEventListener("click", clickEventHandler);
//     },
//     unmounted(element) {
//         document.removeEventListener("click", element.__clickedOutsideHandler__);
//         delete element.__clickedOutsideHandler__; // Cleanup
//     },
// };

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
            optionDisabled="disabled"
            :disabled="disabled"
            :placeholder="placeholder"
            :maxSelectedLabels="7" 
            :loading="loading"
            display="chip"
            :filter="props.filter" 
            @change="updateSelectedOption"
            :pt="{
                root: ({ props, state, parent }) => {
                    open = state.overlayVisible;
                    isOverlayOpen = state.overlayVisible;
                    // state.overlayVisible = isOpened;
                    return {
                        class: [
                            'p-multiselect inline-flex flex-row relative min-h-[44px] mb-1 w-full justify-between py-2 pl-4 pr-4 rounded-md',
                            { 
                                'bg-white border': !props.disabled,
                                'bg-grey-50 border': props.disabled 
                            },
                            'focus:border-primary-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                            'active:border-primary-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                            'cursor-pointer select-none overflow-hidden',
                            { 
                                'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons,
                                'border-grey-300': !props.invalid && !props.disabled,
                                'border-primary-500': props.invalid,
                                'border-grey-100': props.disabled,
                                'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !state.focused,
                                'border-primary-500 focus:border-primary-500 hover:border-primary-500': props.errorMessage,
                                'outline-none border-primary-300 shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': state.focused,
                                'border-grey-100 opacity-60 pointer-events-none cursor-default': props.disabled 
                            }
                        ]
                    }
                },
                label: ({ props, parent }) => {
                    var _a;
                    return {
                        class: [
                            'flex flex-wrap max-w-full items-center gap-2 text-grey-200 text-base font-normal',
                            { 
                                'pr-7': props.showClear,
                                // Filled State *for FloatLabel
                                filled: ((_a = parent.instance) == null ? void 0 : _a.$name) == 'FloatLabel' && props.modelValue !== null 
                            },
                        ]
                    };
                },
                token: {
                    class: 'flex p-1 items-center gap-x-1 rounded-[3px] bg-primary-50'
                },
                tokenLabel: {
                    class: 'text-grey-700 text-xs font-normal whitespace-nowrap overflow-hidden'
                },
                trigger: {
                    class: ['flex items-center', {
                        'text-primary-300': !props.disabled,
                        'text-grey-200': props.disabled
                    }]
                },
                input: ({ props, parent }) => {
                    var _a;
                    return {
                        class: [
                            'block relative flex items-center w-full px-4 py-3 rounded-none',
                            '!text-grey-200 text-base font-normal bg-transparent border-0',
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
                                '!cursor-not-allowed': context.disabled,
                                'cursor-pointer': !context.disabled 
                            },
                        ],

                    }
                },
                panel:  ({ props, state, global, context, parent, instance }) => {
                    // state.focused = open;
                    // state.overlayVisible = open;
                    // state.overlayVisible = !open || !state.focused || !state.clicked ? false : true;
                    return {
                        class: [
                            'p-multiselect-panel self-stretch absolute top-0 left-0 !z-[1110]', 
                            '!mt-1 p-1', 
                            'border-2 border-red-50', 
                            'rounded-[5px]', 
                            'shadow-[0px_15px_23.6px_0px_rgba(102,30,30,0.05)]', 
                            'bg-white', 
                            'text-grey-800',
                            'overflow-auto scrollbar-thin scrollbar-webkit'
                        ]
                    }
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
                    class: [
                        'flex px-4 py-3 items-center gap-[10px] self-stretch w-full',
                        {
                            'hidden': !props.filter
                        }
                    ]
                },
                headerCheckbox: ({ props, state, global, context, parent, instance }) => {
                    return {
                        root: [
                            'flex size-5 justify-center items-center',
                            {
                                'pointer-events-none cursor-not-allowed': context.disabled,
                                'cursor-pointer': !context.disabled 
                            },
                        ],
                        input: `rounded-full focus:ring-white
                                checked:bg-primary-900 
                                active:bg-primary-900
                                hover:checked:bg-primary-900
                                focus:checked:bg-primary-900
                                checked:focus:border-none`,
                        box: '!hidden',
                    }
                },
                closeButton: {
                    class: 'flex items-end'
                },
                // wrapper: {
                //     class: 'flex max-h-[44px] px-4 py-2 items-center gap-[10px] self-stretch rounded-t-0.5'
                // },
                itemCheckbox: ({ props, state, global, context, parent, instance }) => {
                    return {
                        root: 'flex size-5 justify-center items-center',
                        input: [
                            `rounded-full focus:ring-white
                            checked:bg-primary-900 
                            active:bg-primary-900
                            hover:checked:bg-primary-900
                            focus:checked:bg-primary-900
                            checked:focus:border-none`,
                            {
                                'pointer-events-none !cursor-not-allowed bg-grey-100 border-grey-200': context.disabled,
                                'cursor-pointer': !context.disabled 
                            },
                        ],
                        box: '!hidden',
                    }
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

            <template #removetokenicon="{ removeCallback }">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" @click="removeCallback">
                    <path d="M11.3337 4.66602L4.66699 11.3327M4.66699 4.66602L11.3337 11.3327" stroke="#7E171B" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </template>

            <template #closeicon>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 7L7 17M7 7L17 17" stroke="#7E171B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </template>

            <template #option="slotProps" v-if="props.withImages">
                <div class="flex items-center gap-[10px]">
                    <slot name="optionLabel"></slot>
                    <img 
                        :src="slotProps.option.image ? slotProps.option.image 
                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt=""
                        class="size-7 rounded-full object-contain"
                    >
                    <div>{{ slotProps.option.text }}</div>
                </div>
            </template>
        </MultiSelect>

    </div>
</template>


