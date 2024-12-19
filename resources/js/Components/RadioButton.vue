<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import RadioButton from 'primevue/radiobutton';
import InputError from './InputError.vue';

const props = defineProps({
    errorMessage: String,
    dynamic:  {
        type: Boolean,
        default: true
    },
    optionArr: {
        type: Array,
        default: () => [],
    },
    checked:  {
        type: [String, Boolean, Number, Object],
        default: null
    },
    name: {
        type: String,
        default: 'dynamic'
    },
    value: {
        type: [String, Boolean, Number, Object],
        default: null
    },
    disabled:  {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(["update:checked", "onChange"]);

const selected = ref(props.checked);
const optionListArr = ref([]);

// Helper function to compare values (including objects)
const isEqual = (val1, val2) => {
    if (typeof val1 === 'object' && typeof val2 === 'object') {
        // If comparing objects, compare by ID or another unique property
        return val1?.id === val2?.id;
    }
    return val1 === val2;
};

watch(
    () => props.optionArr,
    (newValue) => {
    optionListArr.value = [...newValue];
    },
    { immediate: true }
);

watch(() => props.checked, (newVal) => {
    selected.value = newVal;
});

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
        emit('onChange', val);
    }
});
</script>

<template>
    <div class="w-full flex flex-col">
        <div class="flex flex-row gap-x-10 items-start self-stretch">
            <template v-if="dynamic">
                <div 
                    v-for="(option, index) in optionListArr" 
                    :key="index" 
                    class="flex flex-row align-items-center"
                >
                    <RadioButton 
                        v-model="proxyChecked" 
                        :name="name" 
                        :value="option.value" 
                        :disabled="disabled"
                        @change="$emit('onChange', $event.target.value)"
                        :pt="{
                            root: {
                                class: [
                                    'relative inline-flex align-bottom w-[1.571rem] h-[1.571rem] cursor-pointer select-none'
                                ]
                            },
                            box: ({ props }) => ({
                                class: [
                                    // Flexbox
                                    'flex justify-center items-center w-[1.571rem] h-[1.571rem] border-2 rounded-full transition duration-200 ease-in-out',
    
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
                                    'peer w-full h-full absolute top-0 left-0 z-10 p-0 m-0 opacity-0 rounded-md outline-none border border-grey-300 appearance-none cursor-pointer'
                                ]
                            },
                            icon: ({ props }) => ({
                                class: [
                                    'block rounded-full size-2.5 transition duration-200',
    
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
            </template>
            <template v-else>
                <RadioButton 
                    v-model="proxyChecked" 
                    :name="name" 
                    :value="value"
                    :disabled="disabled"
                    @change="$emit('onChange', $event.target.value)"
                    :pt="{
                        root: {
                            class: [
                                'relative inline-flex align-bottom w-[1.571rem] h-[1.571rem] cursor-pointer select-none'
                            ]
                        },
                        box: ({ props }) => ({
                            class: [
                                // Flexbox
                                'flex justify-center items-center w-[1.571rem] h-[1.571rem] border-2 rounded-full transition duration-200 ease-in-out',

                                // Colors
                                {
                                    'text-grey-700': !isEqual(props.value, props.modelValue) && props.value !== undefined,
                                    'bg-grey-0': !isEqual(props.value, props.modelValue) && props.value !== undefined,
                                    'border-grey-300': !isEqual(props.value, props.modelValue) && props.value !== undefined && !props.invalid,
                                    'border-primary-900': isEqual(props.value, props.modelValue) && props.value !== undefined,
                                    'bg-primary-50': isEqual(props.value, props.modelValue) && props.value !== undefined
                                },
                                // Invalid State
                                { 'border-primary-500': props.invalid },

                                // States
                                {
                                    'peer-hover:border-primary-100': !props.disabled && !props.invalid && !isEqual(props.value, props.modelValue) && props.value !== undefined,
                                    'peer-hover:border-primary-800 peer-hover:[&>div]:bg-primary-800': !props.disabled && isEqual(props.value, props.modelValue) && props.value !== undefined,
                                    'peer-focus-visible:border-primary-500 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-400': !props.disabled,
                                    'border-grey-200 bg-grey-100 cursor-default': props.disabled && !isEqual(props.value, props.modelValue) && props.value !== undefined,
                                    '!border-primary-200 cursor-default': props.disabled && isEqual(props.value, props.modelValue) && props.value !== undefined,
                                }
                            ]
                        }),
                        input: {
                            class: [
                                'peer w-full h-full absolute top-0 left-0 z-10 p-0 m-0 opacity-0 rounded-md outline-none border border-grey-300 appearance-none cursor-pointer'
                            ]
                        },
                        icon: ({ props }) => ({
                            class: [
                                'block rounded-full size-2.5 transition duration-200',

                                // Colors
                                {
                                    'bg-primary-900': !props.disabled,
                                    'bg-primary-200': props.disabled,
                                },

                                // Conditions
                                {
                                    'backface-hidden scale-10 invisible': !isEqual(props.value, props.modelValue),
                                    'transform visible scale-[1.1]': isEqual(props.value, props.modelValue)
                                },
                            ]
                        })
                    }"
                />

                <!-- Example of usage -->
                <!-- 
                    <RadioButton
                        :dynamic="false"
                        :name="'item'"
                        :value="item"
                        :errorMessage="''"
                        v-model:checked="form.item"
                    />
                -->
            </template>
        </div>
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>
