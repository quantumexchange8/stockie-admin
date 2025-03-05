<script setup>
import Button from '@/Components/Button.vue';
import dayjs from 'dayjs';
import { ref, watchEffect } from 'vue';
import weekOfYear from 'dayjs/plugin/weekOfYear';

dayjs.extend(weekOfYear);
const today = dayjs().startOf('week').add(1, 'day');
const currentWeek = ref(today.week());

const emit = defineEmits(['close']);
const waiters = ref([]);
const props = defineProps({
    selectedWaiter: [Object, Number],
    weekNo: Number,
});

const fetchWaiterShift = async () => {

    try {
        const response = await axios.get('/configurations/viewShift', {
            params: { 
                waiter_id: props.selectedWaiter,
                weeks: props.weekNo,
            }, 
        });
        waiters.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

watchEffect(() => {
    fetchWaiterShift();
});

</script>

<template>

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-5">
            <div class="flex items-center">
                <div class="w-full max-w-10 max-h-10">
                    
                </div>
                <div class="flex flex-col gap-1">
                    <div>{{ waiters.length > 0 ? waiters[0].users.full_name : '-'}}</div>
                    <div>Total:</div>
                </div>
            </div>
            <div class="w-full h-[1px] bg-[#ECEFF2]"></div>
            <div v-if="waiters.length > 0">
                <div v-for="waiter in waiters" :key="waiter.id">
                    <div class="flex items-center gap-2">
                        <div class="font-bold">{{ dayjs(waiter.date, 'YYYY-MM-DD').format('DD MMM') }}</div>
                        <div>
                            {{ waiter.shift_id === 'off' 
                                ? 'Off-day' 
                                : `${waiter.shifts.shift_name} (${waiter.shifts.shift_start.slice(0,5)} - ${waiter.shifts.shift_end.slice(0,5)})` 
                            }}
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="w-full">
                <Button
                    variant="red"
                    size="lg"
                    :disabled="currentWeek === props.weekNo"
                >
                    Delete
                </Button>
            </div>
            <div class="w-full">
                <Button
                    variant="tertiary"
                    size="lg"
                >
                    Edit
                </Button>
            </div>
        </div>
    </div>
</template>