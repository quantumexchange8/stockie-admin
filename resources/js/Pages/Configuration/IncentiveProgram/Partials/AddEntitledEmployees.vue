<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import SearchBar from '@/Components/SearchBar.vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    achievement: {
        type: Object,
        default: () => {}
    }
})

const emit = defineEmits(['update:waiters', 'isDirty', 'closeModal']);
const { showMessage } = useCustomToast();

const isLoading = ref(false);
const initialWaiters = ref([]);
const waiters = ref([]);
const searchQuery = ref('');
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    entitledEmployees: [],
});

const getAllWaiters = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/configurations/configurations/getAllWaiters/${props.achievement.id}`);

        initialWaiters.value = response.data;
        waiters.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => getAllWaiters());

const unsaved = (status) => {
    emit('closeModal', status)
}

const submit = async () => {
    form.processing = true;
    try {
        const response = await axios.post(`/configurations/configurations/addEntitledEmployees/${props.achievement.id}`, form);

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Entitled employees have been successfully added to this achievement.',
            });
        }, 200);
        
        unsaved('close');
        emit('update:waiters', response.data);
    } catch (error) {
        form.setError(error.response?.data?.errors);
        console.log(error);
    } finally {
        form.processing = false;
    }
}

const selectWaiter = (waiter) => {
    const foundIndex = form.entitledEmployees.findIndex(employee => employee.id === waiter.id);
    
    if (foundIndex !== -1) {
        form.entitledEmployees.splice(foundIndex, 1);
    } else {
        form.entitledEmployees.push(waiter);
    }
};

const isFormValid = computed(() => {
    return ['entitledEmployees'].every(field => form[field]) && form.entitledEmployees.length > 0 && !form.processing;
});

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        waiters.value = initialWaiters.value;
        return;
    }

    const query = newValue.toLowerCase();

    waiters.value = initialWaiters.value.filter(waiter => {
        const waiterName = waiter.full_name.toLowerCase();

        return waiterName.includes(query);
    });
}, { immediate: true });
</script>

<template>
    <div class="flex flex-col gap-5 rounded-[5px] bg-white">
        <SearchBar 
            placeholder="Search"
            :showFilter="false"
            v-model="searchQuery"
        />

        <form novalidate @submit.prevent="submit">
            <div class="flex flex-col items-start max-h-[316px] gap-0.5 self-stretch divide-y divide-grey-100 overflow-auto scrollbar-webkit">
                <template v-if="waiters.length > 0">
                    <div class="flex p-2 items-center gap-3 self-stretch" v-for="waiter in waiters">
                        <img 
                            :src="waiter.image ? waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                            alt="WaiterProfileImage"
                            class="size-10 object-cover rounded-[40px]"
                        />
                        <span class="line-clamp-1 flex-[1_0_0] text-grey-900 text-ellipsis text-sm font-medium">{{ waiter.full_name }}</span>
                        <Checkbox 
                            :checked="form.entitledEmployees.some(employee => employee.id === waiter.id)"
                            @update:checked="selectWaiter(waiter)"
                        />
                    </div>
                </template>
                <div class="w-full flex flex-col items-center justify-center" v-else>
                    <UndetectableIllus class="w-44 h-44" />
                    <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
                </div>
            </div>
            
            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="unsaved('close')"
                >
                    Cancel
                </Button>
                <Button
                    :size="'lg'"
                    :disabled="!isFormValid"
                >
                    Done
                </Button>
            </div>
        </form>
    </div>
    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    >
    </Modal>
</template>

