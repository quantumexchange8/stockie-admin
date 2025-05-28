<script setup>
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import { computed, ref, onUnmounted, watch, onMounted } from 'vue';
import Card from 'primevue/card';
import Button from '@/Components/Button.vue';
import Accordion from '@/Components/Accordion.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import { EmptyTableIllus } from '@/Components/Icons/illus';
import AssignSeatForm from './AssignSeatForm.vue';
import Modal from "@/Components/Modal.vue";
import ReservationListTable from './ReservationListTable.vue';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import OrderInfo from './OrderInfo.vue';
import { MergedIcon, ShiftWorkerIcon } from '@/Components/Icons/solid';
import { useCustomToast } from '@/Composables';

dayjs.extend(duration);
dayjs.extend(relativeTime);

const props = defineProps({
    zones: Array,
    occupiedTables: Array,
    hasOpenedShift: Boolean,
    customers: Array,
    activeTab: Number,
    zoneName: String,
    users: Array,
    isMainTab: {
        type: Boolean,
        default: false
    },
    isFullScreen: Boolean,
    autoUnlockSetting: Object,
});

const { showMessage } = useCustomToast();
const emit = defineEmits(['fetchZones']);

const op = ref(null);
const allZones = ref(props.zones);
const selectedTable = ref(null);
// const reservationListIsOpen = ref(false);
// const reservationsRowsPerPage = ref(6);
const drawerIsVisible = ref(false);
const shiftIsOpened = ref(props.hasOpenedShift);
const orderInfoDrawer = ref(null);
const lockingInProgress = ref(false);

// Locking control
const autoUnlockTimer = ref(props.autoUnlockSetting);
const isLocking = ref(false);
const lockTimeoutId = ref(null);
const wasLocked = ref(false);

// const reservationsColumns = ref([
//     { field: "reservation_at", header: "Date", width: "25", sortable: true },
//     { field: "reservation_time", header: "Time", width: "25", sortable: true },
//     { field: "pax", header: "No. of Pax", width: "25", sortable: true },
//     { field: "action", header: "", width: "25", sortable: false, edit: true, delete: true },
// ]);

// const rowType = {
//     rowGroups: false,
//     expandable: false,
//     groupRowsBy: "",
// };

// const actions = {
//     view: () => ``,
//     replenish: () => '',
//     edit: () => '',
//     delete: (id) => `/order-management/orders/reservation/${id}`,
// };

const openOverlay = async (event, table) => {
    if (!table.is_reserved) {
        selectedTable.value = table;
    
        if (table.order_tables.length > 0) {
            if (!table.is_locked && !isLocking.value) {
                drawerIsVisible.value = true;

                isLocking.value = true;
                wasLocked.value = false;

                // Clear any existing lock timeout
                if (lockTimeoutId.value) {
                    clearTimeout(lockTimeoutId.value);
                }

                // Set new timeout to lock table
                lockTimeoutId.value = setTimeout(() => {
                    if (orderInfoDrawer.value?.handleTableLock) {
                        orderInfoDrawer.value.handleTableLock('lock');
                        wasLocked.value = true;
                    }
                    lockTimeoutId.value = null;
                    isLocking.value = false;
                }, 3000);

            } else if (table.is_locked) {
                showMessage({
                    severity: 'warn',
                    summary: 'Table is currently locked',
                    detail: 'This table is being viewed by another user. It will be unlocked after inactivity or by the user.',
                });
            }
        } else {
            op.value.show(event);
        }
    }
};

const closeOverlay = () => {
    selectedTable.value = null;
    op.value.hide();
};

const closeDrawer = (unlock = false) => {
    drawerIsVisible.value = false;

    // Cancel pending lock attempt if still in progress
    if (isLocking.value && lockTimeoutId.value) {
        clearTimeout(lockTimeoutId.value);
        lockTimeoutId.value = null;
        isLocking.value = false;
        wasLocked.value = false;
        return;
    }

    // Unlock only if the lock was successfully applied
    if (unlock && wasLocked.value && orderInfoDrawer.value?.handleTableLock && !orderInfoDrawer.value?.wasAutoUnlocked) {
        if (wasLocked.value) orderInfoDrawer.value.handleTableLock(); // unlock
        wasLocked.value = false;
    }

    // setTimeout(() => emit('fetchZones'), 200)
};

const getTableClasses = (table) => ({
    card: computed(() => [
        'border rounded-[5px] gap-6 mb-6',
        {
            'hover:cursor-pointer': !table.is_reserved,
            'bg-white border-grey-100': table.status === 'Empty Seat',
            'bg-primary-50 border-primary-50': table.status !== 'Empty Seat'
        }
    ]),
    state: computed(() => [
        // 'w-full flex justify-center py-1.5 px-6',
        table.is_reserved ? 'cursor-not-allowed' : 'cursor-pointer',
        {
            'bg-grey-50': table.status === 'Empty Seat' && table.is_reserved,
            'bg-white': table.status === 'Empty Seat' && !table.is_reserved,
            'bg-green-500': (table.status === 'All Order Served' || table.status === 'Order Placed' || table.status === 'Pending Order') && !table.is_reserved,
            'bg-orange-500': table.status === 'Pending Clearance' && !table.is_reserved,
        }
    ]),
    text: computed(() => [
        'text-xl font-bold self-stretch text-center',
        {
            'text-primary-900': table.status === 'Empty Seat' && !table.is_reserved,
            // 'text-primary-25': table.status === 'Pending Order',
            'text-green-50': (table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order') && !table.is_reserved,
            'text-orange-25': table.status === 'Pending Clearance'  && !table.is_reserved,
            'text-grey-400': table.is_reserved,
        }
    ]),
    amount: 'text-base font-semibold self-stretch text-center text-green-100',
    duration: computed(() => [
        'text-xs font-normal self-stretch text-center',
        {
            // 'text-primary-100': table.status === 'Pending Order',
            'text-green-100': table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order',
            'text-orange-100': table.status === 'Pending Clearance',
        }
    ]),
    count: computed(() => [
        'text-xs font-medium self-stretch text-center',
        {
            'text-primary-900': table.status === 'Empty Seat',
            // 'text-white': table.status === 'Pending Order',
            'text-green-100': table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order',
            'text-orange-100': table.status === 'Pending Clearance',
        }
    ]),
});

const waitersArr = computed(() => {
    return props.users
        .filter(({position}) => position === 'waiter')
        .map(({id, full_name, image}) => {
            return {
                'text': full_name,
                'value': id,
                'image': image,
            }
        });
});

const tablesArr = computed(() => {
    const seenTableNos = new Set();
    return allZones.value.reduce((allTables, zone) => {
        zone.tables.forEach(({ table_no, id, seat }) => {
            if (!seenTableNos.has(table_no)) {
                seenTableNos.add(table_no);
                allTables.push({
                    text: table_no,
                    value: id,
                    seat: seat,
                    'disabled': props.occupiedTables.some((occupiedTable) => occupiedTable.id === id),
                });
            }
        });
        return allTables;
    }, []);
});

// const getTooltipMessage = (table) => {
//     if (!table.reservations || table.reservations.length === 0) {
//         return 'No reservations';
//     }

//     const reservationList = table.reservations
//         .map((res, index) => {
//             const formattedDate = dayjs(res.reservation_date).format('DD/MM/YYYY, hh:mm A');
//             return `${index + 1}. ${formattedDate}`;
//         })
//         .join('\n');

//     return `${reservationList}`;
// }

// let intervals = ref([]);
const durations = ref({});
const intervals = ref({});

// Setup fetchZones emitter only once
onMounted(() => {
    const fetchZoneInterval = setInterval(() => {
        emit('fetchZones');
    }, 5000);
    intervals.value['fetchZones'] = fetchZoneInterval;
});

const setupDuration = (tableId, created_at) => {
    if (durations.value[tableId]) {
        return durations.value[tableId];
    }

    const startTime = dayjs(created_at);
    const formattedDuration = ref(dayjs.duration(dayjs().diff(startTime)).format('HH:mm:ss'));
    durations.value[tableId] = formattedDuration;

    const updateDuration = () => {
        const now = dayjs();
        const diff = now.diff(startTime);
        formattedDuration.value = dayjs.duration(diff).format('HH:mm:ss');
    };

    const durationInterval = setInterval(updateDuration, 1000);
    intervals.value[tableId] = durationInterval;

    return formattedDuration;
};

const filteredZones = computed(() => {
    let tempZones = allZones.value.map(zone => ({
        ...zone,
        tables: zone.tables.map(table => {
            return table;
        }).filter(table => props.isMainTab || table.zone_id === props.activeTab),
    }));

    return props.isMainTab ? tempZones.filter(zone => zone.tables.length > 0)
                            : tempZones.filter(zone => zone.text === props.zoneName)[0];
});

onUnmounted(() => {
    // intervals.value.forEach(clearInterval);
    Object.values(intervals.value).forEach(clearInterval);
});

const getCurrentOrderTableDuration = (table) => {
    let currentOrderTable = table.order_tables?.toSorted((a, b) => dayjs(b.created_at).diff(dayjs(a.created_at)));
    // // let currentOrderTable = table.order_tables.filter((table) => table.status !== 'Pending Clearance').length === 1 
    // //         ? table.order_tables.filter((table) => table.status !== 'Pending Clearance')[0].created_at
    // //         : table.order_tables[0].created_at;

    // return table.order_tables.length > 0 ? setupDuration(currentOrderTable[0].created_at) : '';
    
    // const currentOrderTable = table.order_tables?.toSorted(
    //     (a, b) => dayjs(b.created_at).diff(dayjs(a.created_at))
    // );

    if (!currentOrderTable?.length) return '';

    const latestCreatedAt = currentOrderTable[0].created_at;
    return setupDuration(table.id, latestCreatedAt);
};

const getStatusCount = (status) => {
    let count = 0;

    if(props.isMainTab) {
        allZones.value.forEach((zone) => {
            count += zone.tables.filter((table) => table.status === status).length;
        });
    } else {
        count += filteredZones.value.tables.filter((table) => table.status === status).length;
    }

    return count;
}

const isMerged = (targetTable) => {
    return allZones.value.some(zone =>
        zone.tables.some(table =>
            table.id !== targetTable.id && table.order_id === targetTable.order_id && table.status !== 'Empty Seat'
        )
    );
};

watch(() => props.autoUnlockSetting, (newValue) => {
    autoUnlockTimer.value = newValue;
});

watch(() => props.hasOpenedShift, (newValue) => {
    shiftIsOpened.value = newValue;
});

watch(() => props.zones, (newValue) => {
    allZones.value = newValue;
    if (selectedTable.value) {
        const updatedSelectedTable = allZones.value.map((z) => z.tables).flat().find((t) => t.id === selectedTable.value.id);
        selectedTable.value = updatedSelectedTable;
    }
});

watch(selectedTable, (newValue, oldValue) => {
    if (drawerIsVisible.value == true && newValue.is_locked == false && oldValue && oldValue.is_locked == true) {
        drawerIsVisible.value = false;
        clearTimeout(lockTimeoutId.value);
        lockTimeoutId.value = null;
        isLocking.value = false;
        wasLocked.value = false;

        showMessage({
            severity: 'info',
            summary: 'The table you were viewing was manually unlocked',
        });
    };
});

// const showReservationList = (event, table) => {
//     event.preventDefault();
//     event.stopPropagation();
    
//     selectedTable.value = table;
//     selectedTable.value.reservations.forEach(res => {
//         res['table_no'] = selectedTable.value.table_no;
//     });
//     reservationListIsOpen.value = true;
// }

// const hideReservationList = () => {
//     reservationListIsOpen.value = false;
//     setTimeout(() => {
//         selectedTable.value = null;
//     }, 200);
// }
</script>

<template>
    <template v-if="selectedTable">
        <RightDrawer 
            :withHeader="false"
            v-model:show="drawerIsVisible"
            @close="closeDrawer(true)"
        >
            <OrderInfo 
                ref="orderInfoDrawer"
                :selectedTable="selectedTable" 
                :customers="customers"
                :users="users"
                :autoUnlockSetting="autoUnlockTimer"
                @fetchZones="$emit('fetchZones')"
                @close="closeDrawer"
            />
        </RightDrawer>
    </template>
    
    <div class="flex flex-col gap-6" :class="props.isFullScreen === true ? 'bg-primary-25' : 'bg-white'">
        <!-- Count table status tab -->
        <div class="flex items-center gap-5 self-stretch">
            <!-- Empty Seat -->
            <div class="flex items-center gap-3">
                <div class="flex size-7 justify-center items-center rounded-[3.889px] border border-solid border-grey-100 bg-white">
                    <span class="text-xs font-medium text-center text-primary-900">{{ getStatusCount('Empty Seat') }}</span>
                </div>
                <span class="text-grey-700 text-center text-sm font-normal">Empty</span>
            </div>

            <!-- Pending Order -->
            <!-- <div class="flex items-center gap-3">
                <div class="flex size-7 justify-center items-center rounded-[3.889px] border border-solid border-primary-700 bg-primary-800">
                    <span class="text-xs font-medium text-center text-white">{{ getStatusCount('Pending Order') }}</span>
                </div>
                <span class="text-grey-700 text-center text-sm font-normal">Pending Order</span>
            </div> -->

            <!-- Pending Order / Order Placed / All Order Served -->
            <div class="flex items-center gap-3">
                <div class="flex size-7 justify-center items-center rounded-[3.889px] border border-solid border-green-400 bg-green-500">
                    <span class="text-xs font-medium text-center text-white">{{ getStatusCount('Pending Order') + (getStatusCount('Order Placed')) + (getStatusCount('All Order Served')) }}</span>
                </div>
                <span class="text-grey-700 text-center text-sm font-normal">In use</span>
            </div>

            <!-- Pending Clearance -->
            <div class="flex items-center gap-3">
                <div class="flex size-7 justify-center items-center rounded-[3.889px] border border-solid border-orange-400 bg-orange-500">
                    <span class="text-xs font-medium text-center text-white">{{ getStatusCount('Pending Clearance') }}</span>
                </div>
                <span class="text-grey-700 text-center text-sm font-normal">Pending Clearance</span>
            </div>
        </div>
        
        <template v-if="shiftIsOpened">
            <!-- Display all zones along with their table(s) -->
            <template v-if="isMainTab"> 
                <div class="grid min-[528px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 items-start gap-6 self-stretch" v-if="props.isFullScreen === true" >
                    <template v-for="zone in filteredZones" class="flex">
                        <div 
                            class="col-span-1 flex items-start content-start gap-6 self-stretch flex-wrap relative" 
                            v-for="table in zone.tables"
                        >
                            <div class="flex flex-col px-6 pt-6 pb-3 justify-center items-center gap-y-3 rounded-[5px] border border-solid border-grey-100 min-h-[140.6px] w-full relative"
                                :class="getTableClasses(table).state.value"
                                @click="openOverlay($event, table)"
                            >
                                <div class="flex flex-col justify-center items-center gap-y-1">
                                    <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                    <span v-if="table.order && table.status !== 'Pending Clearance'" :class="getTableClasses(table).amount">RM {{ table.order.amount }}</span>
                                    <template v-if="table.status === 'Empty Seat'">
                                        <div class="flex py-1 px-3 justify-center items-center gap-2.5 rounded-lg bg-primary-600" v-if="table.is_reserved">
                                            <p class="text-white text-center font-semibold text-2xs">RESERVED</p>
                                        </div>
                                        <div class="text-base font-normal text-center" :class="table.is_reserved ? 'text-grey-200' : 'text-primary-900'" v-else>{{ table.seat }} seats</div>
                                    </template>
                                </div>
                                <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                    {{ getCurrentOrderTableDuration(table) }}
                                </div>

                                <MergedIcon class="absolute left-[8.375px] top-[8px] size-5 text-white" v-if="isMerged(table)"/>
                                <div class="flex px-2 py-1 justify-center items-center gap-2.5 absolute top-[5px] right-0 rounded-l-[5px] bg-primary-600 shadow-[0_2px_4.2px_0_rgba(0,0,0,0.14)]"
                                    v-if="table.pending_count > 0"
                                >
                                    <span class="text-primary-25 text-md font-medium">{{ table.pending_count }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <Accordion v-for="zone in filteredZones" :key="zone.value" accordionClasses="gap-[24px]" v-else>
                    <template #head>
                        <span class="text-sm text-grey-900 font-medium">{{ zone.text }}</span>
                    </template>
                    <template #body>
                        <div class="grid min-[528px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 items-start content-start gap-6 self-stretch">
                            <template v-for="table in zone.tables" :key="table.id">
                                <!-- <Card :class="getTableClasses(table).card.value" @click="openOverlay($event, table)">
                                    <template #title>
                                        <div class="flex flex-col text-center items-center px-6 pt-6 pb-4 gap-2">
                                            <div class="text-xl text-primary-900 font-bold">{{ table.table_no }}</div>
                                            <div class="text-base text-grey-900 font-medium" v-if="table.status !== 'Empty Seat'">
                                                {{ getCurrentOrderTableDuration(table) }}
                                            </div>
                                            <div class="text-base text-grey-900 font-medium" v-else>{{ table.seat }} seats</div>
                                        </div>
                                    </template>
                                    <template #footer>
                                        <div :class="getTableClasses(table).state.value">
                                            <p :class="getTableClasses(table).text.value">{{ table.status }}</p>
                                        </div>
                                    </template>
                                </Card> -->
                                <!-- <div 
                                    v-if="table.reservations && table.reservations.length > 0"
                                    v-tooltip.top="{ value: getTooltipMessage(table) }"
                                    class="absolute top-[5px] left-0 size-6 bg-primary-200 rounded-r-[5px] flex justify-center items-center gap-2.5 py-1 px-2 w-fit cursor-pointer" 
                                    @click="showReservationList($event, table)"
                                >
                                    <p class="text-primary-700 text-2xs font-medium">Reservation: {{ table.reservations.length }}</p>
                                </div> -->
                                <div class="flex flex-col px-6 pt-6 pb-3 justify-center items-center gap-y-3 rounded-[5px] border border-solid border-grey-100 min-h-[140.6px] relative"
                                    :class="getTableClasses(table).state.value"
                                    @click="openOverlay($event, table)"
                                >
                                    <div class="flex flex-col justify-center items-center gap-y-1">
                                        <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                        <span v-if="table.order && table.status !== 'Pending Clearance'" :class="getTableClasses(table).amount">RM {{ table.order.amount }}</span>
                                        <template v-if="table.status === 'Empty Seat'">
                                            <div class="flex py-1 px-3 justify-center items-center gap-2.5 rounded-lg bg-primary-600" v-if="table.is_reserved">
                                                <p class="text-white text-center font-semibold text-2xs">RESERVED</p>
                                            </div>
                                            <div class="text-base font-normal text-center" :class="table.is_reserved ? 'text-grey-200' : 'text-primary-900'" v-else>{{ table.seat }} seats</div>
                                        </template>
                                    </div>
                                    <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                        {{ getCurrentOrderTableDuration(table) }}
                                    </div>

                                    <MergedIcon class="absolute left-2 top-2 size-5 text-white" v-if="isMerged(table)"/>
                                    <div class="flex px-2 py-1 justify-center items-center gap-2.5 absolute top-[5px] right-0 rounded-l-[5px] bg-primary-600 shadow-[0_2px_4.2px_0_rgba(0,0,0,0.14)]"
                                        v-if="table.pending_count > 0"
                                    >
                                        <span class="text-primary-25 text-md font-medium">{{ table.pending_count }}</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </Accordion>
            </template>
            
            <!-- Display specified zone along with its table(s) -->
            <template v-else>
                <div v-if="filteredZones.tables.length > 0" class="grid min-[528px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 items-start content-start gap-6 self-stretch">
                    <template v-for="table in filteredZones.tables" :key="table.id">
                        <!-- <Card :class="getTableClasses(table).card.value" @click="openOverlay($event, table)">
                            <template #title>
                                <div class="flex flex-col text-center items-center px-6 pt-6 pb-4 gap-2">
                                    <div class="text-xl text-primary-900 font-bold">{{ table.table_no }}</div>
                                    <div class="text-base text-grey-900 font-medium" v-if="table.status !== 'Empty Seat'">
                                        {{ getCurrentOrderTableDuration(table) }}
                                    </div>
                                    <div class="text-base text-grey-900 font-medium" v-else>{{ table.seat }} seats</div>
                                </div>
                            </template>
                            <template #footer>
                                <div :class="getTableClasses(table).state.value">
                                    <p :class="getTableClasses(table).text.value">{{ table.status }}</p>
                                </div>
                            </template>
                        </Card> -->
                        <!-- <div 
                            v-if="table.reservations && table.reservations.length > 0"
                            v-tooltip.top="{ value: getTooltipMessage(table) }"
                            class="absolute top-[5px] left-0 size-6 bg-primary-200 rounded-r-[5px] flex justify-center items-center gap-2.5 py-1 px-2 w-fit cursor-pointer" 
                            @click="showReservationList($event, table)"
                        >
                            <p class="text-primary-700 text-2xs font-medium">Reservation: {{ table.reservations.length }}</p>
                        </div> -->
                        <div class="flex flex-col px-6 pt-6 pb-3 justify-center items-center gap-y-3 rounded-[5px] border border-solid border-grey-100 min-h-[140.6px] relative"
                            :class="getTableClasses(table).state.value"
                            @click="openOverlay($event, table)"
                        >
                            <div class="flex flex-col justify-center items-center gap-y-1">
                                <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                <span v-if="table.order && table.status !== 'Pending Clearance'" :class="getTableClasses(table).amount">RM {{ table.order.amount }}</span>
                                <template v-if="table.status === 'Empty Seat'">
                                    <div class="flex py-1 px-3 justify-center items-center gap-2.5 rounded-lg bg-primary-600" v-if="table.is_reserved">
                                        <p class="text-white text-center font-semibold text-2xs">RESERVED</p>
                                    </div>
                                    <div class="text-base font-normal text-center" :class="table.is_reserved ? 'text-grey-200' : 'text-primary-900'" v-else>{{ table.seat }} seats</div>
                                </template>
                            </div>
                            <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                {{ getCurrentOrderTableDuration(table) }}
                            </div>

                            <MergedIcon class="absolute left-2 top-2 size-5 text-white" v-if="isMerged(table)"/>
                            <div class="flex px-2 py-1 justify-center items-center gap-2.5 absolute top-[5px] right-0 rounded-l-[5px] bg-primary-600 shadow-[0_2px_4.2px_0_rgba(0,0,0,0.14)]"
                                v-if="table.pending_count > 0"
                            >
                                <span class="text-primary-25 text-md font-medium">{{ table.pending_count }}</span>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="flex flex-col items-center text-center gap-5" v-else>
                    <EmptyTableIllus />
                    <span class="text-primary-900 text-sm font-medium">
                        You've to add a table/room before you can manage your order
                    </span>
                    <Button
                        :href="route('table-room')"
                        type="button"
                        size="lg"
                        class="!w-fit"
                    >
                        Go to add
                    </Button>
                </div>
            </template>
            <!-- <Modal 
                :title="'Reservation List'"
                :show="reservationListIsOpen" 
                :maxWidth="'sm'" 
                @close="hideReservationList"
            >
                <template v-if="selectedTable">
                    <ReservationListTable
                        :table="selectedTable"
                        :columns="reservationsColumns"
                        :rows="selectedTable.reservations"
                        :rowType="rowType"
                        :actions="actions"
                        :rowsPerPage="reservationsRowsPerPage"
                    />
                </template>
            </Modal> -->
        </template>

        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <ShiftWorkerIcon />
                <div class="flex flex-col gap-y-1 items-center">
                    <span class="text-grey-950 text-md font-semibold">No shift has been opened yet</span>
                    <span class="text-grey-950 text-sm font-normal">You’ll need to open a shift before you can place order.</span>
                </div>
                <Button
                    :href="route('shift-management.control')"
                    :type="'button'"
                    :size="'lg'"
                    class="!w-fit"
                >
                    Open shift
                </Button>
            </div>
        </template>

    </div> 

    <!-- Assign Seat -->
    <OverlayPanel ref="op">
        <AssignSeatForm 
            :table="selectedTable"
            :waiters="waitersArr"
            :tablesArr="tablesArr"
            @close="closeOverlay"
            @fetchZones="$emit('fetchZones')"
        />
    </OverlayPanel>
</template>
