<script setup>
import Button from '@/Components/Button.vue';
import { CheckWhiteIcon, DeleteIcon, PlusIcon, TimeIcon } from '@/Components/Icons/solid';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import Calendar from 'primevue/calendar';
import { onMounted, ref, watch } from 'vue';
import DateInput from '@/Components/Date.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Dropdown from "@/Components/Dropdown.vue";
import InputError from '@/Components/InputError.vue';

const emit = defineEmits(['close', 'fetch-shift']);
const time = ref();
const selectedColor = ref(null);

const props = defineProps({
    shift: Object,
});

const colorCode = [
    { color: '#7E171B'},
    { color: '#E46A2B'},
    { color: '#DEA622'},
    { color: '#46A12B'},
    { color: '#014FA3'},
    { color: '#45535F'},
]

const days = [
    { name: 'Monday'},
    { name: 'Tuesday'},
    { name: 'Wednesday'},
    { name: 'Thursday'},
    { name: 'Friday'},
    { name: 'Saturday'},
    { name: 'Sunday'},
]

const timeType = [
    { text: 'hours', value: 'hours'},
    { text: 'minutes', value: 'minutes'},
]

const form = useForm({
    shift_id: props.shift.id,
    shift_name: '',
    shift_code: '',
    time_start: props.shift ? props.shift.shift_start.slice(0,5) : '',
    time_end: props.shift ? props.shift.shift_end.slice(0,5) : '',
    late: '',
    color: '',
    breaks: ref([]),
    days: [],
});

const initializeForm = () => {
    if (props.shift) {
        form.shift_name = props.shift.shift_name || '';
        form.shift_code = props.shift.shift_code || '';
        form.time_start = props.shift.shift_start.slice(0,5) || '10:00';
        form.time_end = props.shift.shift_end || '';
        form.late = props.shift.late || '';
        form.color = props.shift.color || '';
        if (props.shift && props.shift.shift_breaks) {
            form.breaks = props.shift.shift_breaks.map((item, index) => ({
                id: item.id, // Assign a unique ID if missing
                break_value: String(item.break_value),
                break_time: String(item.break_time),
            }));
        } else {
            form.breaks = [{ id: 1, break_value: '', break_time: '' }];
        }
        form.days = props.shift.apply_days || [];
    }
};

onMounted(() => {
    initializeForm();
});

watch(() => props.shift, () => {
    initializeForm();
}, { deep: true });

const selectColor = (color) => {
    selectedColor.value = color;
    form.color = color; // Store selected color in the form
};

onMounted(() => {
    if (props.shift && props.shift.color) {
        selectedColor.value = props.shift.color;
    }
});

const addBreak = () => {
    form.breaks.push({
        id: '', // Unique ID
        break_value: '',
        break_time: ''
    });
};

const removeBreak = (index) => {
    if (form.breaks.length === 1) {
        Object.assign(form.breaks[0], {
            id: '', // Unique ID
            break_value: '',
            break_time: ''
        });
    } else {
        form.breaks.splice(index, 1);
    }
};

const submit = () => {
    const updatedFields = {};

    for (const key in form) {
        if (form[key] !== props.shift[key]) {
            updatedFields[key] = form[key];
        }
    }

    updatedFields.id = props.shift.id;

    form.post('/configurations/edit-shift', {
        preserveScroll: true,
        data: updatedFields,
        onSuccess: () => {
            form.reset();
            emit('close');
            emit('fetch-shift')
        }
    });
}

</script>


<template>

    <div class="flex flex-col gap-6 overflow-y-auto max-h-[75vh]">
        <div class="flex items-start gap-4">
            <div class="flex flex-col gap-4 w-full">
                <div class="flex flex-col gap-1">
                    <div class="text-gray-950 text-base font-bold">Shift Name</div>
                    <div class="text-gray-950 text-sm font-normal">Provide a name for this shift.</div>
                </div>
                <div>
                    <TextInput
                        :inputName="'shift_name'"
                        :placeholder="'Morning shift'"
                        :errorMessage="form.errors.shift_name ? form.errors.shift_name : ''"
                        v-model="form.shift_name"
                    />
                </div>
            </div>
            <div class="flex flex-col gap-4 w-full">
                <div class="flex flex-col gap-1">
                    <div class="text-gray-950 text-base font-bold">Shift Code</div>
                    <div class="text-gray-950 text-sm font-normal">Provide a code for this shift.</div>
                </div>
                <div>
                    <TextInput
                        :inputName="'shift_code'"
                        :placeholder="'M1'"
                        :errorMessage="form.errors.shift_code ? form.errors.shift_code : ''"
                        v-model="form.shift_code"
                    />
                </div>
            </div>
        </div>
        <div class="flex items-start gap-4">
            <div class="flex flex-col gap-1 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-950 text-base font-bold">Shift Time</div>
                        <div class="text-gray-950 text-sm font-normal">Start time and end time.</div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="">
                            <DateInput
                                :timeOnly="true"
                                :placeholder="'09:00'"
                                v-model="form.time_start"
                                class="[&>span>input]:min-w-0 [&>span>input]:max-w-[106px]"
                            />
                        </div>
                        <div>to</div>
                        <div class="">
                            <DateInput
                                :timeOnly="true"
                                :placeholder="'09:00'"
                                v-model="form.time_end"
                                class="[&>span>input]:min-w-0 [&>span>input]:max-w-[106px]"
                            />
                        </div>
                    </div>
                </div>
                <InputError 
                    :message="form.errors.time_start 
                        ? form.errors.time_start 
                        : form.errors.time_end 
                            ? form.errors.time_end 
                            : ''" 
                />
            </div>
            <div class="flex flex-col gap-4 w-full">
                <div class="flex flex-col gap-1">
                    <div class="text-gray-950 text-base font-bold">Lateness Grace Period</div>
                    <div class="text-gray-950 text-sm font-normal">Max. delay for the employees.</div>
                </div>
                <div class="relative">
                    <TextInput 
                        :inputName="'late'"
                        :inputType="'number'"
                        :placeholder="'15'"
                        :iconPosition="'right'"
                        :errorMessage="form.errors?.late"
                        v-model="form.late"
                        class="w-full [&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pl-4 [&>div:nth-child(1)>input]:mb-0"
                    >
                        <template #prefix>
                            <span class="text-grey-700 text-base font-normal">minute</span>
                        </template>
                    </TextInput>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-1">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <div class="text-gray-900 text-base font-bold leading-tight">Color Label</div>
                    <div class="text-gray-950 text-sm font-normal leading-tight">Select a colour to represent this shift.</div>
                </div>
                <div class="flex items-center gap-3">
                    <div
                        v-for="color in colorCode"
                        :key="color.color"
                        class=" flex justify-center items-center cursor-pointer  rounded-[6px]"
                        :class="{
                            'border-2 border-primary-700 relative': selectedColor === color.color
                        }"
                        @click="selectColor(color.color)"
                    >
                        <div class="p-0.5">
                            <div class="w-9 h-9 flex items-center justify-center rounded-[5px]" :style="{ background: color.color }">
                                <CheckWhiteIcon v-if="selectedColor === color.color" class="w-5 h-5 text-white absolute " />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.color ? form.errors.color : ''" />
        </div>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <div class="text-gray-950 text-base font-bold">Break Time</div>
                <div class="text-gray-950 text-sm font-normal">The time and duration for break.</div>
            </div>
            <div class="flex flex-col gap-4">
                <div v-for="(breakItem, index) in form.breaks" :key="breakItem.id">
                    <div class="flex flex-col gap-1">
                        <div>Break {{ index + 1 }}</div>
                        <div class="flex items-center gap-2 w-full">
                            <div class="w-full">
                                <TextInput
                                    required
                                    :inputType="'number'"
                                    :inputName="'break_value' + index"
                                    :placeholder="'1'"
                                    :errorMessage="form.errors ? form.errors['breaks.' + index + '.break_value']  : ''"
                                    v-model="breakItem.break_value"
                                />
                            </div>
                            <div class="flex flex-col items-start w-full">
                                <div class="flex items-center gap-2 w-full">
                                    <Dropdown
                                        :inputName="'break_time'"
                                        :labelText="''"
                                        :inputArray="timeType"
                                        v-model="breakItem.break_time"
                                        :dataValue="breakItem.break_time"
                                    />
                                    <button @click="removeBreak(index)">
                                        <DeleteIcon class="text-primary-700" />
                                    </button>
                                </div>
                                <InputError :message="form.errors ? form.errors['breaks.' + index + '.break_time']  : ''" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :iconPosition="'left'"
                        :size="'lg'"
                        class="col-span-full"
                        @click="addBreak"
                    >
                        <template #icon>
                            <PlusIcon class="size-6" />
                        </template>
                        Another Break
                    </Button>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-1">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <div class="text-gray-950 text-base font-bold">Select Day(s)</div>
                    <div class="text-gray-950 text-sm font-normal">Applicable days for this shift.</div>
                </div>
                <div class="flex flex-wrap gap-5">
                    <div v-for="day in days" :key="day.name" class="flex items-center gap-2">
                        <Checkbox 
                            v-model:checked="form.days"
                            :value="day.name"
                        />
                        <span>{{ day.name }}</span>
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.days ? form.errors.days : ''" />
        </div>
        <div class="pt-3 flex items-center gap-4 w-full sticky bottom-0 bg-white">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="emit('close')"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                @click="submit"
            >
                Add
            </Button>
        </div>
    </div>

</template>