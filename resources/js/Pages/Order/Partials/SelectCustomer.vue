<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import RadioButton from '@/Components/RadioButton.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Tag from '@/Components/Tag.vue';
import CreateCustomer from '@/Pages/Customer/Partials/CreateCustomer.vue';
import { useForm } from '@inertiajs/vue3';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    orderId: Number,
    customers: {
        type: Array,
        default: () => {},
    }
    
})
const emit = defineEmits(['closeModal', 'isDirty', 'closeOrderDetails']);
const customerList = ref(props.customers);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const isCreateCustomerOpen = ref(false);
const searchQuery = ref('');
const selectedCustomer = ref('');

const form = useForm({
    customer_id: ''
})

const submit = () => {
    form.put(route('orders.updateOrderCustomer', props.orderId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            emit('closeOrderDetails');
            emit('closeModal', 'leave');
        },
    })
}

const unsaved = (status) => {
    emit('closeModal', status);
}

const openModal = () => {
    isDirty.value = false;
    isCreateCustomerOpen.value = true;
}

const closeModal = (status) => {
    switch(status) {
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isCreateCustomerOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isCreateCustomerOpen.value = false;

            break;
        }
    }

}

const filterCustomerList = computed(() => {
    if (!searchQuery.value) {
        return props.customers;
    }

    const search = searchQuery.value.toLowerCase();

    return customerList.value
            .filter(customer => {
                return customer.full_name.toLowerCase().includes(search)
                        || customer.phone.toLowerCase().includes(search);
            });
});

const clearSelection = () => {
    form.customer_id = '';
    selectedCustomer.value = '';
    isDirty.value = false;
};

watch(
    selectedCustomer,
    (newValue) => {
        emit('isDirty', newValue !== '' && newValue !== null);
    }
);

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <SearchBar 
                :placeholder="'Search'"
                :showFilter="false"
                v-model="searchQuery"
            />

            <div class="flex flex-col items-start self-stretch divide-y divide-grey-100 max-h-[calc(100dvh-28.4rem)] overflow-auto scrollbar-webkit scrollbar-thin">
                <template v-for="customer in filterCustomerList">
                    <div class="flex justify-between items-center self-stretch py-2.5 pr-1">
                        <div class="flex flex-nowrap items-center gap-2 w-fit">
                            <img 
                                :src="customer.image ? customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                alt='CustomerProfileImage'
                                class="size-11 rounded-full"
                            />
                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                <span class="text-grey-900 text-base font-bold">{{ customer.full_name }}</span>
                                <span class="text-grey-500 text-base font-normal">{{ customer.phone }}</span>
                            </div>
                        </div>

                        <RadioButton 
                            :name="'customer'"
                            :dynamic="false"
                            :value="customer.id"
                            class="!w-fit"
                            :errorMessage="''"
                            v-model:checked="selectedCustomer"
                            @onChange="form.customer_id = $event"
                        />
                    </div>
                </template>
            </div>

            <div class="flex flex-col justify-end items-center gap-5 self-stretch">
                <div class="flex justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="clearSelection"
                    >
                        Clear
                    </Button>

                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
                        @click="openModal"
                    >
                        Create New
                    </Button>
                </div>

                <Button
                    :variant="'primary'"
                    :size="'lg'"
                    :disabled="!selectedCustomer || form.processing"
                >
                    Confirm
                </Button>
            </div>
        </div>
    </form>
    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    >
    </Modal>

    <Modal 
        :title="'Create New Customer'"
        :show="isCreateCustomerOpen" 
        :maxWidth="'xs'" 
        :closeable="true" 
        @close="closeModal('close')"
    >
        <CreateCustomer
            @update:customerListing="customerList = $event"
            @close="closeModal('leave')" 
            @isDirty="isDirty=$event"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

</template>
