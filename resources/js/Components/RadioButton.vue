<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import RadioButton from 'primevue/radiobutton';
import InputError from './InputError.vue';

const props = defineProps({
    errorMessage: String,
    optionArr: {
        type: Array,
        default: () => [],
    },
    checked:  {
        type: String,
        default: ''
    },
    disabled:  {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(["update:checked", "onChange"]);

const selected = ref(props.checked);
const optionListArr = ref([]);

watch(
    () => props.optionArr,
    (newValue) => {
    optionListArr.value = [...newValue];
    },
    { immediate: true }
);

onMounted(() => {
    optionListArr.value = [...props.optionArr];
})

const proxyChecked = computed({
    get() {
        return selected.value;
    },
    set(val) {
        selected.value = val;
        emit('update:checked', val);
    }
});
</script>

<template>
    <div class="w-full flex flex-col">
        <div class="flex flex-row gap-x-10 items-start self-stretch">
            <div 
                v-for="(option, index) in optionListArr" 
                :key="index" 
                class="flex flex-row align-items-center"
            >
                <RadioButton 
                    v-model="proxyChecked" 
                    :inputId="option.text" 
                    name="dynamic" 
                    :value="option.value" 
                    :disabled="disabled"
                    @change="$emit('onChange', $event.target.value)"
                    :pt="{
                        root: {
                            class: [
                                'relative',

                                // Flexbox & Alignment
                                'inline-flex',
                                'align-bottom',

                                // Size
                                'w-[1.571rem] h-[1.571rem]',

                                // Misc
                                'cursor-pointer',
                                'select-none'
                            ]
                        },
                        box: ({ props }) => ({
                            class: [
                                // Flexbox
                                'flex justify-center items-center',

                                // Size
                                'w-[1.571rem] h-[1.571rem]',

                                // Shape
                                'border-2',
                                'rounded-full',

                                // Transition
                                'transition duration-200 ease-in-out',

                                // Colors
                                {
                                    'text-grey-700': props.value !== props.modelValue && props.value !== undefined,
                                    'bg-grey-0': props.value !== props.modelValue && props.value !== undefined,
                                    'border-grey-300': props.value !== props.modelValue && props.value !== undefined && !props.invalid,
                                    'border-primary-900': props.value == props.modelValue && props.value !== undefined,
                                    'bg-primary-50': props.value == props.modelValue && props.value !== undefined
                                },
                                // Invalid State
                                { 'border-primary-500': props.invalid },

                                // States
                                {
                                    'peer-hover:border-primary-100': !props.disabled && !props.invalid && props.value !== props.modelValue && props.value !== undefined,
                                    'peer-hover:border-primary-800 peer-hover:[&>div]:bg-primary-800': !props.disabled && props.value == props.modelValue && props.value !== undefined,
                                    'peer-focus-visible:border-primary-500 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-400': !props.disabled,
                                    'border-grey-200 bg-grey-100 cursor-default': props.disabled && props.value !== props.modelValue && props.value !== undefined,
                                    '!border-primary-200 cursor-default': props.disabled && props.value == props.modelValue && props.value !== undefined,
                                }
                            ]
                        }),
                        input: {
                            class: [
                                'peer',

                                // Size
                                'w-full ',
                                'h-full',

                                // Position
                                'absolute',
                                'top-0 left-0',
                                'z-10',

                                // Spacing
                                'p-0',
                                'm-0',

                                // Shape
                                'opacity-0',
                                'rounded-md',
                                'outline-none',
                                'border border-grey-300',

                                // Misc
                                'appearance-none',
                                'cursor-pointer'
                            ]
                        },
                        icon: ({ props }) => ({
                            class: [
                                'block',

                                // Shape
                                'rounded-full',

                                // Size
                                'size-2.5',

                                // Colors
                                {
                                    'bg-primary-900': !props.disabled,
                                    'bg-primary-200': props.disabled,
                                },

                                // Conditions
                                {
                                    'backface-hidden scale-10 invisible': props.value !== props.modelValue,
                                    'transform visible scale-[1.1]': props.value == props.modelValue
                                },

                                // Transition
                                'transition duration-200',
                            ]
                        })
                    }"
                />
                <label 
                    :for="option.text" 
                    class="ml-2"
                >
                    {{ option.text }}
                </label>
            </div>
        </div>
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>
