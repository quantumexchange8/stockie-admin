<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import RadioButton from 'primevue/radiobutton';
import Dropdown from '@/Components/Dropdown.vue';

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
const shifts = ref([]);
const waiters = ref([]);

const fetchShift = async () => {
    try {

        const response = await axios.get('/configurations/getShift');
        shifts.value = response.data;

    } catch (error) {
        console.error(error)
    }
}

const fetchWaiter = async () => {
    try {

        const response = await axios.get('/configurations/getWaiter');
        waiters.value = response.data;

    } catch (error) {
        console.error(error)
    }
}

onMounted(() => {
    fetchShift();
    fetchWaiter();
});

const waitersArr = computed(() => {
    return waiters.value.map(waiter => ({
        text: waiter.full_name,
        value: waiter.id
    }));
});

const form = useForm({
    waiter_id: '',
    days: [], // Holds selected days e.g., ["Monday", "Tuesday"]
    assign_shift: {} // Object to store selected shift per day { "Monday": shift_id, "Tuesday": shift_id }
});

const submit = () => {
    form.post('/configurations/assign-shift', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        }
    });
};

</script>

<template>

    <div class="flex flex-col gap-6 overflow-y-auto max-h-[75vh]">
        <div class="flex flex-col gap-4 w-full">
            <div class="flex flex-col gap-1">
                <div class="text-gray-950 text-base font-bold">Select Employee</div>
                <div class="text-gray-950 text-sm font-normal">Assign shift to selected employee.</div>
            </div>
            <div>
                <Dropdown 
                    imageOption
                    :inputName="'waiter_id'" 
                    :labelText="'Assign Waiter'"
                    :inputArray="waitersArr"
                    :errorMessage="form.errors?.waiter_id || ''"
                    v-model="form.waiter_id"
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
                        v-model:checked="form.days"
                        :value="day.name"
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
                <div v-for="selectedDay in form.days" :key="selectedDay">
                    <div class="font-semibold text-lg">{{ selectedDay }}</div>

                    <div class="flex flex-wrap items-center gap-4 w-full">
                        <div v-for="shift in shifts" :key="shift.id" :class="[
                                'p-4 rounded-[5px] max-w-[246px] w-1/2 flex gap-4 border',
                                form.assign_shift[selectedDay] === shift.id 
                                    ? 'border-primary-900 bg-primary-25' 
                                    : 'border-gray-100'
                            ]"
                        >
                            <div class="flex flex-col max-w-[165px] w-full">
                                <div class="text-gray-950 text-base font-semibold">{{ shift.shift_name }}</div>
                                <div class="text-gray-700 text-base font-normal">{{ shift.shift_start }} - {{ shift.shift_end }}</div>
                            </div>

                            <!-- Radio Button to select one shift per day -->
                            <div>
                                <RadioButton 
                                    v-model="form.assign_shift[selectedDay]" 
                                    :inputId="`shift-${selectedDay}-${shift.id}`" 
                                    :name="selectedDay" 
                                    :value="shift.id" 
                                />
                            </div>
                        </div>
                    </div>
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