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

dayjs.extend(duration);
dayjs.extend(relativeTime);

const props = defineProps({
    zones: Array,
    activeTab: Number,
    zoneName: String,
    waiters: {
        type: Array,
        default: () => []
    },
    isMainTab: {
        type: Boolean,
        default: false
    },
});

const emit = defineEmits(['fetchZones']);

const op = ref(null);
// const zones = ref(props.zones);
const selectedTable = ref(null);
const reservationListIsOpen = ref(false);
const reservationsRowsPerPage = ref(6);
const drawerIsVisible = ref(false);

const reservationsColumns = ref([
    { field: "reservation_at", header: "Date", width: "25", sortable: true },
    { field: "reservation_time", header: "Time", width: "25", sortable: true },
    { field: "pax", header: "No. of Pax", width: "25", sortable: true },
    { field: "action", header: "", width: "25", sortable: false, edit: true, delete: true },
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const actions = {
    view: () => ``,
    replenish: () => '',
    edit: () => '',
    delete: (id) => `/order-management/orders/reservation/${id}`,
};

const openOverlay = (event, table) => {
    selectedTable.value = table;

    if (table && !table.order_table) {
        op.value.show(event);
    } else {
        if (!drawerIsVisible.value) {
            drawerIsVisible.value = true;
        }
    }
};

const closeOverlay = () => {
    selectedTable.value = null;
    op.value.hide();
};

const closeDrawer = () => {
    drawerIsVisible.value = false;
    setTimeout(() => {
        emit('fetchZones');
    }, 200)
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
        'w-full flex justify-center py-1.5 px-6',
        {
            'bg-grey-50': table.status === 'Empty Seat',
            'bg-primary-900': table.status === 'Pending Order',
            'bg-yellow-500': table.status === 'Order Placed',
            'bg-green-600': table.status === 'All Order Served',
            'bg-yellow-700': table.status === 'Pending Clearance',
        }
    ]),
    text: computed(() => [
        'text-sm font-medium',
        {
            'text-grey-200': table.status === 'Empty Seat',
            'text-primary-25': table.status === 'Pending Order',
            'text-yellow-25': table.status === 'Order Placed',
            'text-green-50': table.status === 'All Order Served',
            'text-white': table.status === 'Pending Clearance',
        }
    ])
});

const waitersArr = computed(() => {
    return props.waiters.map((waiter) => {
        return {
            'text': waiter.name,
            'value': waiter.id,
        }
    })
});

const getTooltipMessage = (table) => {
    if (!table.reservations || table.reservations.length === 0) {
        return 'No reservations';
    }

    const reservationList = table.reservations
        .map((res, index) => {
            const formattedDate = dayjs(res.reservation_date).format('DD/MM/YYYY, hh:mm A');
            return `${index + 1}. ${formattedDate}`;
        })
        .join('\n');

    return `${reservationList}`;
}

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

onUnmounted(() => {
    intervals.value.forEach(clearInterval);
});

const showReservationList = (event, table) => {
    event.preventDefault();
    event.stopPropagation();
    
    selectedTable.value = table;
    reservationListIsOpen.value = true;
}

const hideReservationList = () => {
    reservationListIsOpen.value = false;
    setTimeout(() => {
        selectedTable.value = null;
    }, 200);
}
</script>

<template>
    <template v-if="selectedTable">
        <RightDrawer 
            :header="'Detail - ' + selectedTable.table_no" 
            v-model:show="drawerIsVisible"
            @close="closeDrawer"
        >
            <OrderInfo 
                :selectedTable="selectedTable" 
                @close="closeDrawer"
            />
        </RightDrawer>
    </template>
    
    <div class="flex flex-col gap-6">
        <!-- Display all zones along with their table(s) -->
        <template v-if="isMainTab"> 
            <Accordion v-for="zone in filteredZones" :key="zone.value" accordionClasses="gap-[24px]">
                <template #head>
                    <span class="text-sm text-grey-900 font-medium">{{ zone.text }}</span>
                </template>
                <template #body>
                    <div class="grid xl:grid-cols-4 md:grid-cols-3 sm:grid-cols-2 gap-x-6">
                        <div class="relative" v-for="table in zone.tables" :key="table.id">
                            <Card :class="getTableClasses(table).card.value" @click="openOverlay($event, table)">
                                <template #title>
                                    <div class="flex flex-col text-center items-center px-6 pt-6 pb-4 gap-2">
                                        <div class="text-xl text-primary-900 font-bold">{{ table.table_no }}</div>
                                        <div class="text-base text-grey-900 font-medium" v-if="table.status !== 'Empty Seat'">
                                            {{ setupDuration(table.order_table.created_at) }}
                                        </div>
                                        <div class="text-base text-grey-900 font-medium" v-else>{{ table.seat }} seats</div>
                                    </div>
                                </template>
                                <template #footer>
                                    <div :class="getTableClasses(table).state.value">
                                        <p :class="getTableClasses(table).text.value">{{ table.status }}</p>
                                    </div>
                                </template>
                            </Card>
                            <div 
                                v-if="table.reservations && table.reservations.length > 0"
                                v-tooltip.top="{ value: getTooltipMessage(table) }"
                                class="absolute top-[5px] left-0 size-6 bg-primary-200 rounded-r-[5px] flex justify-center items-center gap-2.5 py-1 px-2 w-fit cursor-pointer" 
                                @click="showReservationList($event, table)"
                            >
                                <p class="text-primary-700 text-2xs font-medium">Reservation: {{ table.reservations.length }}</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Accordion>
        </template>
        
        <!-- Display specified zone along with its table(s) -->
        <template v-else>
            <div v-if="filteredZones.tables.length > 0" class="grid xl:grid-cols-4 md:grid-cols-3 sm:grid-cols-2 gap-x-6">
                <div class="relative" v-for="table in filteredZones.tables" :key="table.id">
                    <Card :class="getTableClasses(table).card.value" @click="openOverlay($event, table)">
                        <template #title>
                            <div class="flex flex-col text-center items-center px-6 pt-6 pb-4 gap-2">
                                <div class="text-xl text-primary-900 font-bold">{{ table.table_no }}</div>
                                <div class="text-base text-grey-900 font-medium" v-if="table.status !== 'Empty Seat'">
                                    {{ setupDuration(table.order_table.created_at) }}
                                </div>
                                <div class="text-base text-grey-900 font-medium" v-else>{{ table.seat }} seats</div>
                            </div>
                        </template>
                        <template #footer>
                            <div :class="getTableClasses(table).state.value">
                                <p :class="getTableClasses(table).text.value">{{ table.status }}</p>
                            </div>
                        </template>
                    </Card>
                    <div 
                        v-if="table.reservations && table.reservations.length > 0"
                        v-tooltip.top="{ value: getTooltipMessage(table) }"
                        class="absolute top-[5px] left-0 size-6 bg-primary-200 rounded-r-[5px] flex justify-center items-center gap-2.5 py-1 px-2 w-fit cursor-pointer" 
                        @click="showReservationList($event, table)"
                    >
                        <p class="text-primary-700 text-2xs font-medium">Reservation: {{ table.reservations.length }}</p>
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
        <Modal 
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
        </Modal>
    </div> 

    <!-- Assign Seat -->
    <OverlayPanel ref="op">
        <AssignSeatForm 
            :table="selectedTable"
            :waiters="waitersArr"
            @close="closeOverlay"
        />
    </OverlayPanel>
</template>
