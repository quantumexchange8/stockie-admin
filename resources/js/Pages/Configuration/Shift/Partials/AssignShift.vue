<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const days = [
    { name: 'Monday'},
    { name: 'Tuesday'},
    { name: 'Wednesday'},
    { name: 'Thursday'},
    { name: 'Friday'},
    { name: 'Saturday'},
    { name: 'Sunday'},
]

const emit = defineEmits(['close']);
const selectDays = ref([]);

const form = useForm({
    shift_name: '',
    shift_code: '',
    time_start: '',
    time_end: '',
    late: '',
    color: '',
    breaks: [{ id: 1, break_value: '', break_time: '' }],
    days: [],
});

const submit = () => {
    
}

</script>

<template>

    <div class="flex flex-col gap-6 overflow-y-auto max-h-[75vh]">
        <div class="flex flex-col gap-4 w-full">
            <div class="flex flex-col gap-1">
                <div class="text-gray-950 text-base font-bold">Select Employee</div>
                <div class="text-gray-950 text-sm font-normal">Assign shift to selected employee.</div>
            </div>
            <div>
                <TextInput
                    :inputName="'shift_name'"
                    :placeholder="'Morning shift'"
                    :errorMessage="form.errors.shift_name ? form.errors.shift_name[0] : ''"
                    v-model="form.shift_name"
                />
            </div>
        </div>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <div class="text-gray-950 text-base font-bold">Select Day(s)</div>
                <div class="text-gray-950 text-sm font-normal">Applicable days for this shift.</div>
            </div>
            <div class="flex flex-wrap gap-5">
                <div v-for="day in days" :key="day.name" class="flex items-center gap-2">
                    <Checkbox 
                        :checked="selectDays.includes(day.name)"
                    />
                    <span>{{ day.name }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-6">
            <div class="flex items-center">
                <div class="flex flex-col">
                    <div class="text-gray-950 text-base font-bold">Assign Shift</div>
                    <div class="text-gray-950 text-sm font-normal">Assign shift to different working day.</div>
                </div>
            </div>

            <!-- loop -->
            <div class="flex flex-col gap-6">
                <div v-for="item in items" :key="item.id">
                    
                </div>
            </div>
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