<script setup>
import { WarningSmallIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { onMounted, ref, watchEffect } from 'vue';
import ViewShift from './ViewShift.vue';

const emit = defineEmits(['close']);
const waiters = ref([]);
const viewIsOpen = ref(false);
const selectedWaiter = ref(null);
const props = defineProps({
    selectedShift: Object,
    selectedDay: [Array, Object],
    thisWeekVal: Number,
}) 

const fetchWaiter = async () => {
    if (!props.selectedShift?.id) return; // Ensure selectedShift.id exists before making the request

    try {
        const response = await axios.get('/configurations/getFilterShift', {
            params: { 
                shift_id: props.selectedShift.id,
                date: props.selectedDay.date,
            }, 
        });
        waiters.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

watchEffect(() => {
    fetchWaiter();
});

const viewShift = (id) => {
    viewIsOpen.value = true;
    selectedWaiter.value = id;
}

const closeShift = () => {
    viewIsOpen.value = false
}

</script>

<template>

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4 text-base text-gray-900">
            <div class="flex flex-col gap-1">
                <div>Shift Name</div>
                <div class="font-bold">{{ props.selectedShift.shift_name }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div>Date</div>
                <div class="font-bold">{{ props.selectedDay.dayName }}, {{ props.selectedDay.date }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div>No. of waiter</div>
                <div class="font-bold">
                    {{ waiters ? waiters.length : '-' }}
                </div>
            </div>
        </div>
        <div v-if="waiters.length > 0 ">
            <div v-for="waiter in waiters" :key="waiters.id" class="flex flex-col border-b border-gray-100 py-3">
                <div class="flex items-center gap-4">
                    <div class="w-full max-w-10 max-h-10">
                        <img :src="waiter.users.profile_photo_url ? waiter.users.profile_photo_url : ''" alt="" >
                    </div>
                    <div class="flex items-center w-full">
                        <div class="flex flex-col gap-1 w-full">
                            <div>{{ waiter.users.full_name }}</div>
                            <div>Total:</div>
                            
                        </div>
                        <div class="text-primary-900 text-sm font-medium max-w-20 w-full" @click="viewShift(waiter.waiter_id)">View shift</div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <Modal 
        :title="'Employee’s Shift'"
        :show="viewIsOpen" 
        :maxWidth="'xs'" 
        :closeable="true" 
        @close="closeShift"
    >
        <ViewShift :selectedWaiter="selectedWaiter" :weekNo="thisWeekVal" />
    </Modal>

</template>