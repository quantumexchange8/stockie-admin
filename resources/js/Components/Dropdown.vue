<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import InputError from "./InputError.vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";

const props = defineProps({
    labelText: String,
    errorMessage: String,
    inputName: String,
    inputArray: {
        type: [Array, Object],
        default: () => [],
    },
    dataValue: {
        type: [String, Number],
        default: "",
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
    // New prop for grouped options
    grouped: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits(["update:modelValue"]);

const open = ref(false);
const selectedOption = ref("");
const selectedValue = ref("");

const choose = (option) => {
    selectedOption.value = option.text;
    selectedValue.value = option.value;
    open.value = false;
    emits("update:modelValue", option.value);
};

const closeDropdown = (event) => {
    if (!event.target.closest("#dropdown")) {
        open.value = false;
    }
};

const vClickOutside = {
    mounted(el, binding) {
        el.__clickOutsideHandler__ = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        document.addEventListener("click", el.__clickOutsideHandler__);
    },
    unmounted(el) {
        document.removeEventListener("click", el.__clickOutsideHandler__);
    },
};

onMounted(() => {
    selectedValue.value = props.dataValue;
    selectedOption.value = props.inputArray[props.dataValue]
        ? props.inputArray[props.dataValue]
        : props.placeholder;
    document.addEventListener("click", closeDropdown);
});
onUnmounted(() => {
    document.removeEventListener("click", closeDropdown);
});
</script>

<template>
    <div class="w-full">
        <Label
            :value="props.labelText"
            :for="props.inputName"
            :class="[
                'mb-1 text-xs font-medium',
                {
                    'text-grey-900': props.disabled === false,
                    'text-grey-500': props.disabled === true,
                },
            ]"
            v-if="labelText !== ''"
        >
        </Label>
        <div
            class="relative"
            id="dropdown"
            v-click-outside="closeDropdown"
        >
            <span class="w-full rounded-md shadow-sm mb-1">
                <!-- The dropdown input field as a button -->
                <button
                    type="button"
                    @click="open = !open"
                    aria-haspopup="listbox"
                    :aria-expanded="open"
                    aria-labelledby="assigned-to-label"
                    :disabled="props.disabled"
                    :class="[
                        'w-full max-h-[44px] px-4 py-3 flex justify-between items-center hover:text-red-300 active:text-red-300 focus:text-red-300',
                        'rounded-[5px] text-grey-500 active:ring-0 border border-grey-300',
                        'hover:border-red-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                        'active:border-red-300 active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
                        'focus:border-red-300 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
                        {
                            'border-grey-100': props.disabled === true,
                            'border-grey-300': props.disabled === false,
                            'border-red-500 focus:border-red-500 hover:border-red-500':
                                errorMessage,
                        },
                    ]"
                >
                    <!-- The text to be displayed along with dropdown icons -->
                    <span
                        :class="[
                            'text-base font-normal',
                            {
                                'text-grey-200':
                                    selectedOption === props.placeholder,
                                'text-grey-700':
                                    selectedOption !== props.placeholder,
                            },
                        ]"
                    >
                        {{ selectedOption === "" ? "Select" : selectedOption }}
                    </span>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 16 16"
                        fill="none"
                        :class="[
                            open
                                ? 'transition-all duration-200 rotate-270 transform self-center'
                                : '',
                            !open
                                ? 'transition-all duration-200 rotate-180 transform self-center'
                                : '',
                        ]"
                    >
                        <path
                            d="M12 10L8 6L4 10"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </button>

                <!-- The list of options for the dropdown field -->
                <ul
                    class="z-50 absolute mt-1 p-1 bg-white rounded-[5px] border-2 border-red-50 gap-0.5 items-start flex flex-col shadow-[0px_15px_23.6px_0px_rgba(102,30,30,0.05)]"
                    :class="[
                        {
                            'w-fit': grouped,
                            'w-full': !grouped,
                        }
                    ]"
                    v-show="open"
                >
                    <!-- Grouped option: group by group name and list of its options -->
                    <div v-for="(group, index) in props.inputArray" v-if="grouped">
                        <div
                            :class="[
                                'py-2 px-4 text-base text-grey-400 flex items-center bg-grey-25',
                            ]"
                        >
                            <div
                                class="flex gap-[10px] items-center justify-center"
                            >
                                <slot name="grouped_header">
                                    <span class="w-4 h-4 flex-shrink-0 rounded-full bg-grey-700"></span>
                                    <span class="text-base font-bold">{{ group.group_name }}</span>
                                </slot>
                            </div>
                        </div>

                        <li
                            :class="[
                                'cursor-pointer select-none py-2 px-4 self-stretch items-center flex rounded-[5px] hover:bg-grey-50 active:bg-red-50',
                                {
                                    'bg-red-50': selectedOption === item.text,
                                },
                            ]"
                            v-for="(item, optionIndex) in group.items"
                            :key="optionIndex"
                            @click="choose(item)"
                        >
                            {{ item.text }}

                            <!-- Optional for including an icon to the right of the text of a specified option -->
                            <template v-if="iconOptions">
                                <span class="absolute right-0 pr-4">
                                    <component
                                        :is="iconOptions[item.text]"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                /></span>
                            </template>
                        </li>
                    </div>
                    <!-- Default option: directly displays options -->
                    <li
                        :class="[
                            'cursor-pointer select-none py-2 px-4 self-stretch items-center flex rounded-[5px] hover:bg-grey-50 active:bg-red-50',
                            {
                                'bg-red-50': selectedOption === option.text,
                            },
                        ]"
                        v-else
                        v-for="(option, ix) in props.inputArray"
                        :key="ix"
                        @click="choose(option)"
                    >
                        {{ option.text }}

                        <!-- Optional for including an icon to the right of the text of a specified option -->
                        <template v-if="iconOptions">
                            <span class="absolute right-0 pr-4">
                                <component
                                    :is="iconOptions[option.text]"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                            /></span>
                        </template>
                    </li>
                </ul>
            </span>
        </div>
        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>

<!-- group option should pass this format of json data -->
<!-- 
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
]);
 -->
