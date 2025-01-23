<script setup>
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import { computed, ref, onUnmounted } from 'vue';
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
import { MergedIcon } from '@/Components/Icons/solid';

dayjs.extend(duration);
dayjs.extend(relativeTime);

const props = defineProps({
    zones: Array,
    occupiedTables: Array,
    customers: Array,
    activeTab: Number,
    zoneName: String,
    users: Array,
    isMainTab: {
        type: Boolean,
        default: false
    },
    isFullScreen: Boolean,
});

const emit = defineEmits(['fetchZones']);

const op = ref(null);
const selectedTable = ref(null);
// const reservationListIsOpen = ref(false);
// const reservationsRowsPerPage = ref(6);
const drawerIsVisible = ref(false);

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

const openOverlay = (event, table) => {
    selectedTable.value = table;

    if (table && table.order_tables.length > 0) {
        if (!drawerIsVisible.value) drawerIsVisible.value = true;
    } else {
        op.value.show(event);
    }
};

const closeOverlay = () => {
    selectedTable.value = null;
    op.value.hide();
};

const closeDrawer = () => {
    drawerIsVisible.value = false;
    // setTimeout(() => emit('fetchZones'), 200)
};

const getTableClasses = (table) => ({
    card: computed(() => [
        'border rounded-[5px] gap-6 mb-6 hover:cursor-pointer',
        {
            'bg-white border-grey-100': table.status === 'Empty Seat',
            'bg-primary-50 border-primary-50': table.status !== 'Empty Seat'
        }
    ]),
    state: computed(() => [
        // 'w-full flex justify-center py-1.5 px-6',
        {
            'bg-grey-50': table.status === 'Empty Seat',
            'bg-green-600': table.status === 'All Order Served' || table.status === 'Order Placed' || table.status === 'Pending Order',
            'bg-orange-500': table.status === 'Pending Clearance',
        }
    ]),
    text: computed(() => [
        'text-xl font-bold self-stretch text-center',
        {
            'text-primary-900': table.status === 'Empty Seat',
            // 'text-primary-25': table.status === 'Pending Order',
            'text-green-50': table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order',
            'text-orange-25': table.status === 'Pending Clearance',
        }
    ]),
    duration: computed(() => [
        'text-base font-normal self-stretch text-center',
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
    return props.zones.reduce((allTables, zone) => {
        zone.tables.forEach(({ table_no, id }) => {
            if (!seenTableNos.has(table_no)) {
                seenTableNos.add(table_no);
                allTables.push({
                    text: table_no,
                    value: id,
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

let intervals = ref([]);

const setupDuration = (created_at) => {
    const startTime = dayjs(created_at);
    const formattedDuration = ref(dayjs.duration(dayjs().diff(startTime)).format('HH:mm:ss'));

    const updateDuration = () => {
        const now = dayjs();
        const diff = now.diff(startTime);
        formattedDuration.value = dayjs.duration(diff).format('HH:mm:ss');
    };

    const intervalId = setInterval(updateDuration, 1000);
    intervals.value.push(intervalId);

    return formattedDuration.value;
};

const filteredZones = computed(() => {
    let tempZones = props.zones.map(zone => ({
        ...zone,
        tables: zone.tables.map(table => {
            return table;
        }).filter(table => props.isMainTab || table.zone_id === props.activeTab),
    }));

    return props.isMainTab ? tempZones.filter(zone => zone.tables.length > 0)
                            : tempZones.filter(zone => zone.text === props.zoneName)[0];
});

onUnmounted(() => intervals.value.forEach(clearInterval));

const getCurrentOrderTableDuration = (table) => {
    let currentOrderTable = table.order_tables.filter((table) => table.status !== 'Pending Clearance').length === 1 
            ? table.order_tables.filter((table) => table.status !== 'Pending Clearance')[0].created_at
            : table.order_tables[0].created_at;

    return setupDuration(currentOrderTable);
};

const getStatusCount = (status) => {
    let count = 0;

    if(props.isMainTab) {
        props.zones.forEach((zone) => {
            count += zone.tables.filter((table) => table.status === status).length;
        });
    } else {
        count += filteredZones.value.tables.filter((table) => table.status === status).length;
    }

    return count;
}

const isMerged = (targetTable) => {
    return props.zones.some(zone =>
        zone.tables.some(table =>
            table.id !== targetTable.id && table.order_id === targetTable.order_id && table.status !== 'Empty Seat'
        )
    );
};

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
            @close="drawerIsVisible = false"
        >
            <OrderInfo 
                :selectedTable="selectedTable" 
                :customers="customers"
                :users="users"
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
        
        <!-- Display all zones along with their table(s) -->
        <template v-if="isMainTab"> 
            <div class="grid xl:grid-cols-6 lg:grid-cols-5 md:grid-cols-3 items-start gap-6 self-stretch" v-if="props.isFullScreen === true" >
                <template v-for="zone in filteredZones" class="flex">
                    <div class="col-span-1 flex items-start content-start gap-6 self-stretch flex-wrap relative" v-for="table in zone.tables">
                        <div class="flex flex-col p-6 justify-center items-center gap-2 rounded-[5px] border border-solid border-grey-100 min-h-[137px] cursor-pointer w-full relative"
                            :class="getTableClasses(table).state.value"
                            @click="openOverlay($event, table)"
                        >
                            <MergedIcon class="absolute left-[8.375px] top-[8px] size-5 text-white" v-if="isMerged(table)"/>
                            <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                            <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                {{ getCurrentOrderTableDuration(table) }}
                            </div>
                            <div class="text-base text-primary-900 font-normal text-center" v-else>{{ table.seat }} seats</div>
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
                    <div class="grid xl:grid-cols-5 lg:grid-cols-4 md:grid-cols-3 gap-6">
                        <div class="relative" v-for="table in zone.tables" :key="table.id">
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
                            <div class="flex flex-col p-6 justify-center items-center gap-2 rounded-[5px] border border-solid border-grey-100 min-h-[137px] cursor-pointer"
                                :class="getTableClasses(table).state.value"
                                @click="openOverlay($event, table)"
                            >
                                 <MergedIcon class="absolute left-[8.375px] top-[8px] size-5 text-white" v-if="isMerged(table)"/>
                                <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                    {{ getCurrentOrderTableDuration(table) }}
                                </div>
                                <div class="text-base text-primary-900 font-normal text-center" v-else>{{ table.seat }} seats</div>
                                <div class="flex px-2 py-1 justify-center items-center gap-2.5 absolute top-[5px] right-0 rounded-l-[5px] bg-primary-600 shadow-[0_2px_4.2px_0_rgba(0,0,0,0.14)]"
                                    v-if="table.pending_count > 0"
                                >
                                    <span class="text-primary-25 text-md font-medium">{{ table.pending_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Accordion>
        </template>
        
        <!-- Display specified zone along with its table(s) -->
        <template v-else>
            <div v-if="filteredZones.tables.length > 0" class="gap-6" :class="props.isFullScreen === true ? 'grid xl:grid-cols-6 lg:grid-cols-4 md:grid-cols-3 ' : 'grid xl:grid-cols-4 md:grid-cols-3 sm:grid-cols-2'">
                <div class="relative" v-for="table in filteredZones.tables" :key="table.id">
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
                    <div class="flex flex-col p-6 justify-center items-center gap-2 rounded-[5px] border border-solid border-grey-100 min-h-[137px] cursor-pointer"
                        :class="getTableClasses(table).state.value"
                        @click="openOverlay($event, table)"
                    >
                        <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                        <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                            {{ getCurrentOrderTableDuration(table) }}
                        </div>
                        <div class="text-base text-primary-900 font-normal text-center" v-else>{{ table.seat }} seats</div>
                        <div class="flex px-2 py-1 justify-center items-center gap-2.5 absolute top-[5px] right-0 rounded-l-[5px] bg-primary-600 shadow-[0_2px_4.2px_0_rgba(0,0,0,0.14)]"
                            v-if="table.pending_count > 0"
                        >
                            <span class="text-primary-25 text-md font-medium">{{ table.pending_count }}</span>
                        </div>
                    </div>
                </div>
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
    </div> 

    <!-- Assign Seat -->
    <OverlayPanel ref="op">
        <AssignSeatForm 
            :table="selectedTable"
            :waiters="waitersArr"
            :tablesArr="tablesArr"
            @close="closeOverlay"
        />
    </OverlayPanel>
</template>
