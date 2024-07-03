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

// Method to update the selected option
const updateSelectedOption = (inputArray, dataValue) => {
    let selectedText = inputArray.find(option => option.value === dataValue)?.text || props.placeholder;
    selectedValue.value = dataValue;
    selectedOption.value = selectedText;
};

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

// v-directive for when user clicked on the outside of the declared element, it will execute the applied callback function
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
    updateSelectedOption(props.inputArray, props.dataValue);
    document.addEventListener("click", closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener("click", closeDropdown);
});

// Watchers
watch(() => props.inputArray, (newValue) => {
    updateSelectedOption(newValue, props.dataValue);
});

watch(() => props.dataValue, (newValue) => {
    updateSelectedOption(props.inputArray, newValue);
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
                    :disabled="props.disabled"
                    :class="[
                        'w-full max-h-[44px] px-4 py-3 flex justify-between items-center',
                        'rounded-[5px] text-grey-500 border',
                        open ? 'border-red-500' : 'border-grey-300',
                        props.disabled ? 'border-grey-100' : 'hover:border-red-100 active:border-red-300 focus:border-red-300',
                        props.errorMessage && 'border-red-500 focus:border-red-500 hover:border-red-500'
                    ]"
                >
                    <!-- The text to be displayed along with dropdown icons -->
                    <span
                        :class="[
                            'text-base font-normal',
                            selectedOption === props.placeholder ? 'text-grey-200' : 'text-grey-700'
                        ]"
                    >
                        {{ selectedOption || props.placeholder }}
                    </span>
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
                </button>

                <!-- The list of options for the dropdown field -->
                <ul
                    class="z-50 absolute mt-1 p-1 bg-white rounded-[5px] border-2 border-red-50 gap-0.5 items-start flex flex-col shadow-[0px_15px_23.6px_0px_rgba(102,30,30,0.05)]"
                    :class="{ 'w-fit': props.grouped, 'w-full': !props.grouped }"
                    v-show="open"
                >
                    <!-- Grouped option: group by group name and list of its options -->
                    <div v-for="(group, index) in props.inputArray" :key="index" v-if="grouped">
                        <div class="py-2 px-4 text-base text-grey-400 flex items-center bg-grey-25">
                            <div class="flex gap-[10px] items-center justify-center">
                                <slot name="grouped_header">
                                    <span class="w-4 h-4 flex-shrink-0 rounded-full bg-grey-700"></span>
                                    <span class="text-base font-bold">{{ group.group_name }}</span>
                                </slot>
                            </div>
                        </div>
                        <li
                            v-for="(item, optionIndex) in group.items"
                            :key="optionIndex"
                            @click="choose(item)"
                            :class="[
                                'cursor-pointer select-none py-2 px-4 self-stretch items-center flex rounded-[5px] hover:bg-grey-50 active:bg-red-50',
                                selectedOption === item.text && 'bg-red-50'
                            ]"
                        >
                            {{ item.text }}
                            <!-- Optional for including an icon to the right of the text of a specified option -->
                            <span class="absolute right-0 pr-4" v-if="props.iconOptions">
                                <component
                                    :is="props.iconOptions[item.text]"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                />
                            </span>
                        </li>
                    </div>
                    <!-- Default option: directly displays options -->
                    <li
                        v-for="(option, ix) in props.inputArray"
                        :key="ix"
                        @click="choose(option)"
                        v-else
                        :class="[
                            'cursor-pointer select-none py-2 px-4 self-stretch items-center flex rounded-[5px] hover:bg-grey-50 active:bg-red-50',
                            selectedOption === option.text && 'bg-red-50'
                        ]"
                    >
                        {{ option.text }}
                        <!-- Optional for including an icon to the right of the text of a specified option -->
                        <span class="absolute right-0 pr-4" v-if="props.iconOptions">
                            <component
                                :is="props.iconOptions[option.text]"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                            />
                        </span>
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
