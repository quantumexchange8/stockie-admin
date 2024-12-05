<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { DropdownIcon, UploadIcon } from '@/Components/Icons/solid';
import { useFileExport } from '@/Composables';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import Chart from 'primevue/chart';
import { ref, onMounted, watch } from "vue";

const props = defineProps ({
    ordersArray: {
        type: Array,
        required: true
    },
    isLoading: Boolean,
})
const chartData = ref();
const chartOptions = ref();
const emit = defineEmits(['applyYearFilter', 'isLoading']);
const { exportToCSV } = useFileExport();

const setChartData = () => {
    const orders = props.ordersArray.map(value => parseInt(value, 10));

    return {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [
            {
                label: '',
                data: orders,
                backgroundColor: (context) => {
                    const hoveredIndex = context.chart.hoveredIndex;
                    const index = context.index;

                    if (hoveredIndex === null ) {
                        return '#7E171B';
                    }
                    return hoveredIndex === index ? '#7E171B' : '#FFF1F2';
                },
                borderColor: '#7E171B',
                borderRadius: 5,
            }
        ],
    };
};

const setChartOptions = () => {

    return {
        maintainAspectRatio: false,
        aspectRatio: 0.6,
        responsive: true,
        animation: {
            duration: 200,  
            easing: 'linear',
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: false,
                external: customTooltipHandler,
            },
        },
        scales: {
            x: {
                ticks: {
                    color: '#7E171B',
                    font: {
                        family: 'Lexend',
                        overflow: 'hidden',
                        size: 14,
                        weight: 500,
                        lineHeight : 'normal',
                    }
                },
                grid: {
                    display: false,
                }
            },
            y: {
                ticks: {
                    color: '#7E171B',
                    font: {
                        family: 'Lexend',
                        overflow: 'hidden',
                        size: 14,
                        weight: 500,
                        lineHeight : 'normal',
                    }
                },
                grid: {
                    color: '#FFE1E2',
                    drawTicks: true,
                    offset: false,
                },
                border: {
                    dash: [5, 5]
                },
                beginAtZero: true,
            }
        }
    };
}

function customTooltipHandler(context) {
    // Tooltip element creation or selection
    let tooltipEl = document.getElementById('order-summary-tooltip');

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'order-summary-tooltip';
        tooltipEl.innerHTML = '<div></div>';
        document.body.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    const tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
    }

        // Clear previous alignment classes
        tooltipEl.classList.remove('top', 'bottom', 'center', 'no-transform');

    if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
    } else {
        tooltipEl.classList.add('no-transform');
    }

    if (tooltipModel.body) {
        const body = tooltipModel.body.map(item => item.lines);

        let innerHtml = `<div style="
            display: flex; 
            flex-direction: column;  
            background: #FFF9F9; 
            border-radius: 10px; 
            color: #48070A; 
            padding: 12px 24px; 
            gap: 4px;
            align-items: center; 
            pointer-events: none;
            box-shadow: 3px -10px 36.7px 0px rgba(151, 97, 99, 0.13);
        ">`;

        // Add title at the bottom of the body (customized position)

        innerHtml += `<div style="
            font-size: 16px; 
            font-weight: 600; 
            text-align: right;
        ">${body}</div>`;
        innerHtml += `<div style="
            color: #7E171B;
            text-align:right;
            font-size: 12px;
            font-weight: 400;
            line-height: normal;
        ">orders</div>`;
        innerHtml += '</div>';

        tooltipEl.querySelector('div').innerHTML = innerHtml;
    }

    const position = context.chart.canvas.getBoundingClientRect();
    tooltipEl.style.opacity = 1;
    tooltipEl.style.position = 'absolute';
    tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
    tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
    tooltipEl.style.padding = tooltipModel.padding + 'px ' + tooltipModel.padding + 'px';
};

const customPlugin = {
  id: 'hoverBarColorChange',
  beforeEvent(chart, args) {
    const { event } = args;
    const hoveredElements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);

    if (hoveredElements.length > 0) {
      chart.hoveredIndex = hoveredElements[0].index;
    } else {
      chart.hoveredIndex = null;
    }

  },
  afterDraw(chart) {
        if (chart.hoveredIndex === undefined) {
            chart.hoveredIndex = null;
        }

        chart.update();
    }
};

const getNextFiveYears = () => {
    const currentYear = new Date().getFullYear();
    const years = [];

    for (let i = 0; i < 5; i++) {
        years.push(currentYear - i);
    }

    return years;
}

const years = ref(getNextFiveYears());
const selected = ref(years.value[0]);

const useYearFilter = (year) => {
    emit('isLoading', true);
    selected.value = years.value.find(y => y === year);
    emit('applyYearFilter', selected.value);
};

const isSelected = (year) => {
    return selected.value && year === selected.value;
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions();
}

watch(
    () => props.ordersArray.value,
    () => {
        updateChart();
    },
    { deep: true }
);

const csvExport = () => {
    const orderYear = selected.value || 'Unknown';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const mappedOrders = props.ordersArray.map((orders, index) => ({
        'Month': months[index], 
        'Order Count': orders, 
    }));
    exportToCSV(mappedOrders, `${orderYear}_Order Summary`)
};

onMounted(() => {
    updateChart();
});

</script>

<template>
    <div class="flex flex-col !h-full justify-between py-6 items-center gap-6 rounded-[5px] border border-solid border-primary-100">
        <div class="flex px-6 justify-between items-center self-stretch">
            <span class="flex-[1_0_0] text-primary-900 text-md font-medium ">Order Summary</span>
            <div class="flex items-center gap-6">
                <!-- menu for year filter -->
                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton 
                            :disabled="isLoading"
                            :class="{'border-grey-100 opacity-60 pointer-events-none cursor-default': isLoading }" 
                            class="inline-flex items-center gap-3 justify-end py-3 pl-4 w-full ">
                            <span class="text-primary-900 font-base text-md">{{ selected }}</span>
                            <DropdownIcon class="rotate-180 text-primary-900"/>
                        </MenuButton>
                    </div>

                    <transition 
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0" 
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100" 
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="absolute z-20 right-0 w-fit p-1 mx-[-4] gap-0.5 flex flex-col origin-top-right whitespace-nowrap rounded-[5px] border-2 border-solid border-primary-50 bg-white shadow-lg"
                        >
                            <MenuItem
                                v-slot="{ active }"
                                v-for="year in years"
                                :key="year"
                            >
                                <button
                                    type="button"
                                    :class="[
                                        { 'bg-primary-50 flex justify-between': isSelected(year) },
                                        { 'bg-white hover:bg-[#fff9f980]': !isSelected(year) },
                                        'group flex w-full items-center rounded-md px-6 py-3 text-base text-gray-900',
                                    ]"
                                    @click="useYearFilter(year)"
                                >
                                    <span
                                        :class="[
                                            { 'text-primary-900 text-center text-base font-normal': isSelected(year) },
                                            { 'text-grey-700 text-center text-base font-normal group-hover:text-primary-800': !isSelected(year) },
                                        ]"
                                    >
                                        {{ year }}
                                    </span>
                                </button>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>

                 <!-- export menu -->
                 <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton
                            class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
                            Export
                            <UploadIcon class="size-4 cursor-pointer" />
                        </MenuButton>
                    </div>

                    <transition 
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0" 
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100" 
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg"
                            >
                            <MenuItem v-slot="{ active }">
                                <button type="button" :class="[
                                    { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': ordersArray.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" :disabled="ordersArray.length === 0" @click="csvExport">
                                    CSV
                                </button>
                            </MenuItem>

                            <MenuItem v-slot="{ active }">
                                <button type="button" :class="[
                                    // { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': ordersArray.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" :disabled="ordersArray.length === 0">
                                    PDF
                                </button>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>

        <template v-if="props.ordersArray.length === 0 || isLoading">
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </template>

        <template v-else>
            <div class="w-full h-full px-6">
                <Chart type="bar" 
                :data="chartData" 
                :options="chartOptions"
                :plugins="[customPlugin]"
                class="w-full h-full"
                />
            </div>
        </template>
    </div>
</template>
