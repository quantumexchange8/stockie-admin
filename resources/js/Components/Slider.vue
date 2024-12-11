<script setup>
import { ref, watch } from "vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Slider from 'primevue/slider';

const props = defineProps({
    modelValue: Array,
    labelText: String,
    errorMessage: String,
    minValue: {
        type: Number,
        default: 0,
    },
    maxValue:  {
        type: Number,
        default: 100,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

const value = ref(props.modelValue);

const updateRangeValues = (values) => {
    emit('update:modelValue', values);
};

</script>

<template>
    <div class="w-full flex flex-col">
        <Label
            :value="labelText"
            :class="[
                'mb-1 text-xs !font-medium',
                {
                    'text-grey-900': disabled === false,
                    'text-grey-500': disabled === true,
                },
            ]"
            v-if="labelText !== ''"
        >
        </Label>
        <!-- v-tooltip.top="{ value: `${value}` }" -->
        <div class="flex items-center justify-center relative w-full">
            <div class="relative w-full">
                <Slider 
                    v-model="value" 
                    range 
                    :min="minValue"
                    :max="maxValue"
                    :disabled="disabled"
                    class="w-14" 
                    @change="updateRangeValues"
                    :pt="{
                        root: ({ props }) => ({
                            class: [
                                'relative self-stretch',

                                // Size
                                { 'h-1.5 w-full': props.orientation == 'horizontal', 'w-1.5 h-full': props.orientation == 'vertical' },

                                // Shape
                                'border-0 rounded-[5px]',

                                // Colors
                                'bg-grey-300',

                                // States
                                { 'opacity-60 select-none pointer-events-none cursor-default': props.disabled }
                            ]
                        }),
                        range: ({ props }) => ({
                            class: [
                                // Position
                                'block absolute',
                                {
                                    'top-0 left-0': props.orientation == 'horizontal',
                                    'bottom-0 left-0': props.orientation == 'vertical'
                                },

                                //Size
                                {
                                    'h-full': props.orientation == 'horizontal',
                                    'w-full': props.orientation == 'vertical'
                                },

                                // Colors
                                'bg-gradient-to-r from-primary-900 to-primary-950'
                            ]
                        }),
                        handle: ({ props }) => ({
                            class: [
                                'block',

                                // Size
                                'size-[1.143rem]',
                                {
                                    'top-[50%] mt-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'horizontal',
                                    'left-[50%] mb-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'vertical'
                                },

                                // Shape
                                'rounded-full',
                                'border-2',

                                // Colors
                                'bg-grey-100',
                                'border-primary',

                                // States
                                'hover:bg-primary-emphasis',
                                'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring',
                                'ring-primary-400/50',

                                // Transitions
                                'transition duration-200',

                                // Misc
                                'cursor-grab',
                                'touch-action-none'
                            ]
                        }),
                        startHandler: ({ props }) => ({
                            class: [
                                'block',

                                // Size
                                'size-[1.143rem]',
                                {
                                    'top-[50%] mt-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'horizontal',
                                    'left-[50%] mb-[-0.5715rem] ml-[-0.4715rem]': props.orientation == 'vertical'
                                },

                                // Shape
                                'rounded-full',
                                'border-0',

                                // Colors
                                'bg-white shadow-md',

                                // Transitions
                                'transition duration-200',

                                // Misc
                                'cursor-grab',
                                'touch-action-none'
                            ]
                        }),
                        endHandler: ({ props }) => ({
                            class: [
                                'block',

                                // Size
                                'size-[1.143rem]',
                                {
                                    'top-[50%] mt-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'horizontal',
                                    'left-[50%] mb-[-0.5715rem] ml-[-0.4715rem]': props.orientation == 'vertical'
                                },

                                // Shape
                                'rounded-full',
                                'border-0',

                                // Colors
                                'bg-white shadow-md',

                                // Transitions
                                'transition duration-200',

                                // Misc
                                'cursor-grab',
                                'touch-action-none'
                            ]
                        })
                    }"
                />
                <!-- Start Handle Tooltip -->
                <div 
                    v-if="!disabled"
                    class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 z-10"
                    :style="{ left: `${(value[0] / (maxValue - minValue)) * 100}%` }"
                >
                    <div class="relative">
                        <div class="absolute -top-14 left-1/2 -translate-x-1/2 bg-white text-primary-900 text-xs p-2 rounded-[5px] font-bold whitespace-nowrap">
                            RM {{ value[0] }}
                        </div>
                        <!-- Downward pointing arrow -->
                        <div class="absolute left-1/2 bottom-3.5 -translate-x-1/2 w-0 h-0 
                            border-l-8 border-r-8 border-t-[10px] 
                            border-l-transparent border-r-transparent 
                            border-t-white"></div>
                    </div>
                </div>
                
                <!-- End Handle Tooltip -->
                <div 
                    v-if="!disabled"
                    class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 z-10"
                    :style="{ left: `${(value[1] / (maxValue - minValue)) * 100}%` }"
                >
                    <div class="relative">
                        <div class="absolute -top-14 left-1/2 -translate-x-1/2 bg-white text-primary-900 text-xs p-2 rounded-[5px] font-bold whitespace-nowrap">
                            RM {{ value[1] }}
                        </div>
                        <!-- Downward pointing arrow -->
                        <div class="absolute left-1/2 bottom-3.5 -translate-x-1/2 w-0 h-0 
                            border-l-8 border-r-8 border-t-[10px] 
                            border-l-transparent border-r-transparent 
                            border-t-white"></div>
                    </div>
                </div>
            </div>
        </div>
        <InputError :message="props.errorMessage" v-if="props.errorMessage" />
    </div>
</template>
