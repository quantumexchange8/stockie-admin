<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Tag from '@/Components/Tag.vue';
import CreateCustomer from '@/Pages/Customer/Partials/CreateCustomer.vue';
import { useForm } from '@inertiajs/vue3';
import { FilterMatchMode } from 'primevue/api';
import { computed, onMounted, ref, watch } from 'vue';
import { useCustomToast, usePhoneUtils } from '@/Composables/index.js';
import { MovingIllus, UndetectableIllus } from '@/Components/Icons/illus';
import { CheckedIcon, DefaultIcon } from '@/Components/Icons/solid';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    currentOrder: Object,
    currentTable: Object,
    currentHasVoucher: Boolean,
})
const emit = defineEmits(['update:order', 'closeModal', 'isDirty', 'closeOrderDetails', 'update:reset-applied-discounts']);
const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const order = ref(props.currentOrder);
const initialZones = ref([]);
const customerList = ref([]);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const isConfirmShow = ref(false);
const isSelectedCustomer = ref();
const checkedIn = ref([]);
const searchQuery = ref('');
const zones = ref([]);
const mergedTables = ref([]);
const removeRewardFormIsOpen = ref(false);
const hasVoucher = ref(props.currentHasVoucher);

const form = useForm({
    id: props.currentTable.id,
    customer_id: props.currentOrder?.customer_id ?? null,
    tables: [],
});

const getAllZones = async() => {
    try {
        const lockedTables = JSON.parse(sessionStorage.getItem('table_locks'));

        const response = await axios.post(route('orders.getAllZones', { locked_tables: lockedTables }));
        initialZones.value = response.data.zones;
        zones.value = response.data.zones;

    } catch(error) {
        console.error(error)
    } finally {
    }
}

const getAllCustomers = async () => {
    try {
        const response = await axios.get(route('orders.getAllCustomer'));
        customerList.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        
    }
};

onMounted(() => {
    getAllZones();
    getAllCustomers();
});

const showRemoveRewardForm = () => {
    removeRewardFormIsOpen.value = true;
};

const hideRemoveRewardForm = () => {
    removeRewardFormIsOpen.value = false;
};

const closeConfirm = () => {
    if (!removeRewardFormIsOpen.value) isConfirmShow.value = false;
}

const mergeTable = async () => {
    const selectedCustomer = isSelectedCustomer.value?.id ?? form.customer_id;
    form.customer_id = selectedCustomer;
    
    const tables = zones.value.flatMap((zone) => zone.tables);

    tables.forEach((table) => {
        if (order.value.order_table.some((orderTable) => orderTable.table_id === table.id)) {
            if (!form.tables.find((formTable) => formTable.id === table.id)) {
                form.tables.push(table);
            }
        }
    })
    
    try {
        const response = await axios.post('/order-management/orders/mergeTable', form);

        setTimeout(() => {
            showMessage({
                severity: 'success',
                summary: wTrans('public.toast.merged_bill_success')
            })
        }, 200);

        form.reset();
        emit('update:order', response.data);
        hideRemoveRewardForm();
        closeConfirm();
        emit('closeModal', 'leave');
        emit('closeOrderDetails');
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const unsaved = (status) => {
    emit('closeModal', status);
}

const addToMerged = (targetTable) => {
    const index = form.tables.indexOf(targetTable);
    if (index > -1) {
        form.tables.splice(index, 1); 
    } else {
        form.tables.push(targetTable); 
    }
    
    // Find if there is any table already merged with the target table.
    const matchingTables = zones.value
        .flatMap((zone) => zone.tables)
        .filter(
            (table) =>
                table.order_id &&
                table.order_id === targetTable.order_id &&
                table.id !== targetTable.id &&
                table.status !== 'Empty Seat' && 
                table.status !== 'Pending Clearance'
        );
    
    matchingTables.forEach((matchingTable) => {
        // If exists then push into mergedTables to show disabled state on layout
        const existingIndex = mergedTables.value.findIndex(
            (table) => table.id === matchingTable.id
        );
        if (existingIndex > -1) {
            mergedTables.value.splice(existingIndex, 1); 
        } else {
            mergedTables.value.push(matchingTable); 
        }

        // At the same time push into form.tables since it will be merged together
        const formIndex = form.tables.findIndex(
            (table) => table.id === matchingTable.id
        );
        if (formIndex > -1) {
            form.tables.splice(formIndex, 1);
        } else {
            form.tables.push(matchingTable);
        }
    });
};

const removeOrderRewards = () => {
    emit('update:reset-applied-discounts');
    mergeTable();
};

const openConfirm = () => {
    const isCheckedIn = form.tables.flatMap((table) => table.order_tables)
                                    .filter((order_table) => !!order_table.order.customer_id)
                                    .map((order_table) => order_table.order.customer_id);

    isCheckedIn.push(order.value.customer?.id);
    checkedIn.value = customerList.value.filter((customer) =>
        isCheckedIn.includes(customer.id)
    );

    isSelectedCustomer.value = checkedIn.value.length > 0 ? checkedIn.value[0] : null;
    if (checkedIn.value.length > 1) {
        isConfirmShow.value = true;
    } else {
        if (hasVoucher.value) {
            showRemoveRewardForm();
        } else {
            removeOrderRewards();
        }
    }
};

const clearSelection = () => {
    form.tables = [];
    isDirty.value = false;
};

const tablesArray = computed(() => {
    if (!searchQuery.value) {
        return initialZones.value
            .flatMap((zone) => zone.tables)
            .filter((table) => table.status !== 'Empty Seat' && table.status !== 'Pending Clearance');
    }

    const search = searchQuery.value.toLowerCase();

    return zones.value
        .flatMap((zone) => zone.tables)
        .filter((table) => table.status !== 'Empty Seat' && table.status !== 'Pending Clearance')
        .filter(table => {
                return table.table_no.toLowerCase().includes(search)
                        || table.order.order_no.toLowerCase().includes(search);
            });
})

watch(() => props.currentOrder, (newValue) => (order.value = newValue));

watch(() => props.currentHasVoucher, (newValue) => {
    hasVoucher.value = newValue;
});

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <SearchBar 
                :placeholder="$t('public.search')"
                :showFilter="false"
                v-model="searchQuery"
            />

            <div class="flex flex-col items-center gap-y-4 pr-2 self-stretch max-h-[calc(100dvh-28.4rem)] overflow-auto scrollbar-webkit scrollbar-thin">
                <template v-if="tablesArray.length > 0">
                    <template v-for="table in tablesArray">
                        <div 
                            class="flex justify-between items-center self-stretch p-4 rounded-[5px] border border-grey-100 bg-white shadow-sm"
                            @click="table.id === props.currentTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) ? '' : addToMerged(table)"
                        >
                            <div class="flex flex-nowrap items-center gap-2 w-fit">
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <span class="text-primary-900 text-xl font-bold">{{ table.table_no }}</span>
                                    <div class="flex items-start gap-x-4">
                                        <span class="text-grey-950 text-base font-normal">{{ table.order.order_no }}</span>
                                        <span class="text-grey-200">&#x2022;</span>
                                        <span class="text-grey-950 text-base font-normal">RM {{ table.order.amount }}</span>
                                    </div>
                                </div>
                            </div>

                            <Checkbox 
                                :checked="!!form.tables.find((formTable) => formTable.id === table.id)"
                                :disabled="table.id === props.currentTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id)"
                            />
                        </div>
                    </template>
                </template>
                <div class="flex flex-col items-center justify-center" v-else>
                    <UndetectableIllus/>
                    <span class="text-primary-900 text-sm font-medium pb-5">{{ $t('public.empty.no_data') }}</span>
                </div>
            </div>

            <div class="flex flex-col justify-end items-center gap-5 self-stretch">
                <div class="flex justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="clearSelection"
                    >
                        {{ $t('public.action.clear') }}
                    </Button>

                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="form.processing"
                        @click="openConfirm"
                    >
                        {{ $t('public.action.apply') }}
                    </Button>
                </div>
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
        :show="isConfirmShow"
        :title="$t('public.order.select_customer')"
        :maxWidth="'xs'"
        @close="closeConfirm"
    >
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <div class="flex flex-col items-start gap-1 self-stretch">
                <span class="self-stretch text-grey-950 text-base font-bold">
                    {{ $t('public.order.existing_customer') }}
                </span>
                <span class="self-stretch text-grey-950 text-sm font-normal">
                    {{ $t('public.order.choose_customer_stay') }}
                </span>
            </div>

            <div class="flex flex-col items-start gap-5 self-stretch">
                <template v-for="customer in checkedIn">
                    <div class="flex p-4 items-start gap-3 self-stretch rounded-[5px] border border-solid cursor-pointer relative"
                        :class="customer === isSelectedCustomer ? 'border-primary-900 bg-primary-25' : 'border-grey-100 bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)]'"
                        @click="isSelectedCustomer = customer"
                    >
                        <img
                            :src="customer.image"
                            alt="CustomerImage"
                            class="size-[52px] rounded-full"
                            v-if="customer.image"
                        >
                        <DefaultIcon class="size-[52px] object-contain" v-else />
                        <div class="flex flex-col items-start gap-2 flex-[1_0_0] self-stretch">
                            <span class="self-stretch text-grey-900 text-md font-semibold">{{ customer.full_name }}</span>
                            <div class="flex items-center gap-2 flex-[1_0_0] self-stretch">
                                <span class="text-grey-500 text-sm font-base">{{ formatPhone(customer.phone) }}</span>
                                <span class="text-grey-300">&#x2022;</span>
                                <span class="line-clamp-1 flex-[1_0_0] text-sm font-normal">{{ customer.email }}</span>
                            </div>
                        </div>
                        <CheckedIcon 
                            class="size-4 absolute top-[-5px] right-[-5px]"
                            v-if="isSelectedCustomer === customer"
                        />
                    </div>
                </template>
            </div>
            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="closeConfirm"
                >
                    {{ $t('public.action.cancel') }}
                </Button>
                <Button
                    :type="'button'"
                    :variant="'primary'"
                    :size="'lg'"
                    @click="hasVoucher ? showRemoveRewardForm() : removeOrderRewards()"
                >
                    {{ $t('public.action.confirm') }}
                </Button>
            </div>
        </div>
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="removeRewardFormIsOpen"
        :withHeader="false"
        class="[&>div>div>div]:!p-0"
        @close="hideRemoveRewardForm"
    >
        <div class="flex flex-col gap-9">
            <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                <MovingIllus />
            </div>
            <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6" >
                <div class="text-center text-primary-900 text-lg font-medium self-stretch">{{ $t('public.order.remove_reward') }}</div>
                <div class="text-center text-grey-900 text-base font-medium self-stretch" >{{ $t('public.order.both_applied_rewards_removed') }}</div>
            </div>
            <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                <Button
                    variant="tertiary"
                    size="lg"
                    type="button"
                    @click="hideRemoveRewardForm"
                >
                    {{ $t('public.action.cancel') }}
                </Button>
                <Button
                    size="lg"
                    @click="removeOrderRewards"
                >
                    {{ $t('public.action.remove') }}
                </Button>
            </div>
        </div>
    </Modal>

</template>
