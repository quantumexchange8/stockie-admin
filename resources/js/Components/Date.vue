<script setup>
import { ref } from "vue";
import Label from "@/Components/Label.vue";
import HintText from "@/Components/HintText.vue";
import InputError from "@/Components/InputError.vue";
import Calendar from 'primevue/calendar';
import { CircledTimesIcon, CircledArrowHeadLeftIcon, CircledArrowHeadRightIcon, CircledArrowHeadUpIcon, CircledArrowHeadDownIcon } from "./Icons/solid";

const props = defineProps({
    inputName: String,
    labelText: String,
    errorMessage: String,
	modelValue: [String, Date, Array],
    range: {
        type: Boolean,
        default: false,
    },
    withPresetRanges: {
        type: Boolean,
        default: true,
    },
    withTime: {
        type: Boolean,
        default: false,
    },
    hintText: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const modelValue = ref(props.modelValue);

const emit = defineEmits(['update:modelValue']);

function updateValue(value) {
    modelValue.value = value;
    emit('update:modelValue', value);
}

const setDateToday = () => {
    let date = new Date();

    date.setHours(0, 0, 0, 0);

    modelValue.value = [date];
};

const setDateYesterday = () => {
    let date = new Date();

    date.setDate(date.getDate() - 1);
    date.setHours(0, 0, 0, 0);

    modelValue.value = [date];
};

const setDateLast7Days = () => {
    let startDate = new Date();
    let currentDate = new Date();

    startDate.setDate(startDate.getDate() - 7);
    startDate.setHours(0, 0, 0, 0);
    currentDate.setHours(0, 0, 0, 0);

    modelValue.value = [startDate, currentDate];
};

const setDateLast30Days = () => {
    let startDate = new Date();
    let currentDate = new Date();

    startDate.setDate(startDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);
    currentDate.setHours(0, 0, 0, 0);

    modelValue.value = [startDate, currentDate];
};

const setDateThisMonth = () => {
    let date = new Date();
    let firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    let lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    firstDay.setHours(0, 0, 0, 0);
    lastDay.setHours(0, 0, 0, 0);

    modelValue.value = [firstDay, lastDay];
};

const setDateLastMonth = () => {
    let date = new Date();
    let firstDay = new Date(date.getFullYear(), date.getMonth() - 1, 1);
    let lastDay = new Date(date.getFullYear(), date.getMonth(), 0);

    firstDay.setHours(0, 0, 0, 0);
    lastDay.setHours(0, 0, 0, 0);

    modelValue.value = [firstDay, lastDay];
};
</script>

<template>
    <div class="w-full">
        <Label
            :value="labelText"
            :for="inputName"
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
        <Calendar 
            :name="props.inputName"
            :modelValue="modelValue"
            @update:modelValue="updateValue" 
            dateFormat="dd/mm/yy"
            :selectionMode="props.range === true ? 'range' : 'single'" 
            :manualInput="false"
            showIcon 
            iconDisplay="input"
            :showTime="props.withTime"
            hourFormat="12"
            :placeholder="props.placeholder"
            :disabled="disabled"
            :pt="{
                input: ({ props, parent }) => {
                    var _a;
                    return {
                    class: [
                        // Display
                        'flex flex-auto',
                        // Font
                        'leading-normal text-base font-normal',
                        // Colors
                        'text-primary-900',
                        'placeholder:text-grey-200',
                        'bg-white',
                        'border',
                        { 
                            'border-grey-300': !props.disabled && !props.invalid && props.modelValue === '',
                            'border-primry-900': !props.disabled && !props.invalid && props.modelValue !== '',
                        },
                        // Invalid State
                        { 'border-primary-950': props.invalid },
                        // Spacing
                        'm-0 py-3 px-4',
                        // Shape
                        'rounded-[5px] flex-1',
                        // States
                        { 'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]': !props.invalid && !props.disabled },
                        'focus:outline-none focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:border-primary-300 focus:ring-0',
                        'active:outline-none active:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] active:border-primary-300',
                        { 'border-primary-500 focus:border-primary-500 hover:border-primary-500': props.errorMessage },
                        { 'border-grey-300': props.disabled },
                        // Filled State *for FloatLabel
                        { filled: ((_a = parent.instance) == null ? void 0 : _a.$name) == 'FloatLabel' && props.modelValue !== null }
                    ]
                    };
                },
                daylabel: ({ context }) => {
                    var startDate = new Date(modelValue[0]);
                    var endDate = new Date(modelValue[1]);
                    var currentDate = context.date.day + '/' + context.date.month + '/' + context.date.year;

                    var convertedStartDate = startDate.getDate() + '/' + startDate.getMonth() + '/' + startDate.getFullYear();
                    var convertedEndDate = endDate.getDate() + '/' + endDate.getMonth() + '/' + endDate.getFullYear();
                    
                    return {
                        class: [
                            // Flexbox and Alignment
                            'flex items-center justify-center',
                            'mx-auto',
                            // Shape & Size
                            'w-7 h-7 py-[5px]',
                            'rounded-full text-sm font-normal',
                            'border-transparent border',
                            // Colors
                            {
                                'text-primary-900 outline outline-offset-1 outline-primary-900': !context.selected && !context.disabled && context.date.today,
                                'text-grey-900 bg-transparent': !context.selected && !context.disabled && !context.date.today,
                                '!text-primary-50 bg-primary-900': 
                                    context.selected && !context.disabled && (currentDate === convertedStartDate || currentDate === convertedEndDate),
                                '!text-primary-50 bg-primary-600': context.selected && !context.disabled && (currentDate > convertedStartDate || currentDate < convertedEndDate),
                                'outline-none': context.selected && !context.disabled && context.date.today,
                            },
                            // States
                            {
                                'hover:text-primary-200': !context.selected && !context.disabled,
                            },
                            {
                                'text-grey-300 cursor-default': context.disabled,
                                'cursor-pointer': !context.disabled
                            }
                        ]
                    };
                },
                panel: ({ props }) => ({
                    class: [
                    // Display & Position
                    {
                        'relative': !props.inline,
                        'inline-block': props.inline
                    },
                    // Size
                    '!min-w-0 pr-5 pt-3 pb-3',
                    { 'pl-40 max-w-[450px]': withPresetRanges },
                    { 'pl-5 max-w-80': !withPresetRanges },
                    // Shape
                    'border rounded-lg',
                    {
                        'shadow-[0px_24px_40px_0px_rgba(0,0,0,0.25)]': !props.inline
                    },
                    // Colors
                    'bg-white',
                    //misc
                    { 'overflow-x-auto': props.inline }
                    ]
                }),
            }"
        >
            <template #inputicon>
                <CircledTimesIcon 
                    class="w-4 h-4 fill-primary-50 text-primary-900 hover:text-primary-900 cursor-pointer right-4 absolute top-1/2 -mt-2 "
                    @click="modelValue = ''"
                    v-if="modelValue !== ''"
                />
            </template>
            <template #previousicon>
                <CircledArrowHeadLeftIcon 
                    class="w-4 h-4 text-grey-900 [&>rect]:hover:fill-grey-100"
                />
            </template>
            <template #nexticon>
                <CircledArrowHeadRightIcon 
                    class="w-4 h-4 text-grey-900 [&>rect]:hover:fill-grey-100"
                />
            </template>
            <template #incrementicon>
                <CircledArrowHeadUpIcon 
                    class="w-4 h-4 text-grey-900 [&>rect]:hover:fill-grey-100"
                />
            </template>
            <template #decrementicon>
                <CircledArrowHeadDownIcon 
                    class="w-4 h-4 text-grey-900 [&>rect]:hover:fill-grey-100"
                />
            </template>
            <template #footer>
                <div class="absolute left-[130px] top-[-1px] h-full -translate-x-full bg-white max-w-96 !min-w-0 py-5 rounded-lg" v-show="withPresetRanges">
                    <div class="flex pr-3 flex-col items-start self-stretch w-full h-full border-r-[0.5px] border-grey-200">
                        <span class="flex py-2 px-3 items-center gap-2 self-stretch rounded-[5px] text-xs font-normal text-grey-700 hover:text-primary-900 hover:bg-primary-50 cursor-pointer" @click="setDateToday">Today</span>
                        <span class="flex py-2 px-3 items-center gap-2 self-stretch rounded-[5px] text-xs font-normal text-grey-700 hover:text-primary-900 hover:bg-primary-50 cursor-pointer" @click="setDateYesterday">Yesterday</span>
                        <span class="flex py-2 px-3 items-center gap-2 self-stretch rounded-[5px] text-xs font-normal text-grey-700 hover:text-primary-900 hover:bg-primary-50 cursor-pointer" @click="setDateLast7Days">Last 7 days</span>
                        <span class="flex py-2 px-3 items-center gap-2 self-stretch rounded-[5px] text-xs font-normal text-grey-700 hover:text-primary-900 hover:bg-primary-50 cursor-pointer" @click="setDateLast30Days">Last 30 days</span>
                        <span class="flex py-2 px-3 items-center gap-2 self-stretch rounded-[5px] text-xs font-normal text-grey-700 hover:text-primary-900 hover:bg-primary-50 cursor-pointer" @click="setDateThisMonth">This Month</span>
                        <span class="flex py-2 px-3 items-center gap-2 self-stretch rounded-[5px] text-xs font-normal text-grey-700 hover:text-primary-900 hover:bg-primary-50 cursor-pointer" @click="setDateLastMonth">Last Month</span>
                    </div>
                </div>
            </template>
        </Calendar>
        <HintText v-if="hintText !== ''" :hintText="hintText" />
        <InputError :message="errorMessage" v-if="errorMessage" />
    </div>
</template>
