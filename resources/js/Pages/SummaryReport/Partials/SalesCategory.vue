<script setup>
import Button from '@/Components/Button.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { DropdownIcon, UploadIcon } from '@/Components/Icons/solid';
import { useFileExport } from '@/Composables';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import Chart from 'primevue/chart';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    salesCategory: {
        type: Array,
        required: true,
    },
    lastPeriodSales: {
        type: Array,
        required: true,
    },
    isLoading: Boolean,
})
const emit = defineEmits(['applySalesFilter', 'isLoading']);
const chartData = ref();
const chartOptions = ref();
const categories = ['Beer', 'Wine', 'Liquor', 'Others'];
const { exportToCSV } = useFileExport();

const selectedCategory = ref(categories[0]);

const setActive = (button, year) => {
    emit('isLoading', true);
    selectedCategory.value = button;
    selectedYear.value = year;
    emit('applySalesFilter', selectedCategory.value, selectedYear.value);
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
const selectedYear = ref(years.value[0]);

const useYearFilter = (year, category) => {
    emit('isLoading', true);
    selectedYear.value = years.value.find(y => y === year);
    selectedCategory.value = category;
    emit('applySalesFilter', selectedCategory.value, selectedYear.value);
};

const csvExport = () => {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const salesYear = selectedYear.value || 'Unknown';
    const salesCategory = selectedCategory.value || 'Unknown category';
    const mappedSalesCategory = props.salesCategory.map((salesCategory, index) => ({
        'Month': months[index], 
        'Sales': `RM ${parseFloat(salesCategory).toFixed(2)}`,
    }));
    exportToCSV(mappedSalesCategory, `${salesYear}_Sales in ${salesCategory}`);
}

const setChartData = () => {
    const salesData = props.salesCategory.map(value => parseFloat(value).toFixed(2));
    const lastPeriod = props.lastPeriodSales.map(value => parseFloat(value).toFixed(2));

    return {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [
            {
                label: 'Current Period',
                data: salesData,
                fill: true,
                borderColor: '#7E171B',
                tension: 0.4,
                borderWidth: 2.5,
                pointStyle: 'circle',
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHitRadius: 5,
                pointBorderWidth: 3,
                pointHoverBackgroundColor: '#FFF9F9',
                pointHoverBorderWidth: 5,
                backgroundColor: (context) => {
                if (!context.chart.chartArea) {
                    return;
                }

                const { ctx, chartArea: { top, bottom } } = context.chart;

                const gradientBg = ctx.createLinearGradient(0, top, 0, bottom);

                gradientBg.addColorStop(0, '#FFA1A5'); 
                gradientBg.addColorStop(0.525, 'rgba(234, 193, 194, 0.436872)');
                gradientBg.addColorStop(0.932292, 'rgba(217, 217, 217, 0)'); 

                return gradientBg;
                },
            },
            {
                label: 'Last Period',
                data: lastPeriod,
                fill: false,
                borderColor: '#FFC7C9',
                tension: 0.4,
                borderWidth: 2.5,
                pointStyle: 'circle',
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHitRadius: 5,
                pointBorderWidth: 3,
                pointHoverBackgroundColor: '#FFF9F9',
                pointHoverBorderWidth: 5,
            }
        ]
    };
};

const setChartOptions = () => {

    return {
        maintainAspectRatio: false,
        aspectRatio: 0.6,
        responsive: true,
        animation: {
            duration: 100,  
            easing: 'linear',
        },
        plugins: {
            hoverLabelColorChange: [customPlugin], 
            point: {
                display: false
            },
            legend: {
                display: false,
            },
            tooltip: {
                enabled: false,
                external: customTooltipHandler,
            },
            hover: {
                mode: 'nearest',
                animationDuration: 400,
            },
        },
        interaction: {
            mode: 'nearest', 
            intersect: true, 
        },
        onHover: (event, elements, chart) => {
            if (elements.length) {
                const hoveredIndex = elements[0].index;

                const activeElements = [];

                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    activeElements.push({
                        datasetIndex,
                        index: hoveredIndex
                    });
                });

                chart.setActiveElements(activeElements);
                chart.update();
            } else {
                chart.setActiveElements([]);
                chart.update();
            }
        },
        scales: {
            x: {
                ticks: {
                    color: (context) => {
                        const hoveredIndex = context.chart.hoveredIndex;
                        const index = context.index;

                        if (hoveredIndex === null) {
                            return '#7E171B';
                        }
                        return hoveredIndex === index ? '#7E171B' : '#D6DCE1';
                    },
                    font: {
                        family: 'Lexend',
                        size: 14,
                        style: 'normal',
                        weight: 500,
                        lineHeight: 'normal',
                    }
                },
                grid: {
                    display: false,
                },
            },
            y: {
                ticks: {
                    color: '#7E171B',
                    font: {
                        family: 'Lexend',
                        size: 14,
                        style: 'normal',
                        weight: 500,
                        lineHeight: 'normal',
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
};

function customTooltipHandler(context) {
    let tooltipEl = document.getElementById('sales-category-tooltip');

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'sales-category-tooltip';
        tooltipEl.innerHTML = '<div></div>';
        document.body.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    const tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
    }

    const monthMapping = {
        "Jan": "January",
        "Feb": "February",
        "Mar": "March",
        "Apr": "April",
        "May": "May",
        "Jun": "June",
        "Jul": "July",
        "Aug": "August",
        "Sep": "September",
        "Oct": "October",
        "Nov": "November",
        "Dec": "December"
    };

    // Get all datasets and the current hovered index
    const chart = context.chart;
    const dataIndex = tooltipModel.dataPoints[0].dataIndex;
    const xAxisLabel = tooltipModel.dataPoints[0].label;
    const displayLabel = monthMapping[xAxisLabel] || xAxisLabel;

    let innerHtml = `<div style="
        display: flex; 
        flex-direction: column;  
        background: #7E171B; 
        border-radius: 10px; 
        color: #FFF9F9; 
        padding: 12px 24px; 
        align-items: start; 
        pointer-events: none;
    ">`;

    innerHtml += `<div style="
        font-size: 12px; 
        font-weight: 400; 
        color: #FFF9F9;
        margin-bottom: 16px;
    ">${displayLabel}</div>`;

    // Loop through each dataset to display data for the same x-axis point
    chart.data.datasets.forEach((dataset, datasetIndex) => {
        const datasetLabel = dataset.label || `Dataset ${datasetIndex + 1}`;
        const value = dataset.data[dataIndex];

        innerHtml += `<div style="
            font-size: 12px; 
            font-weight: 400; 
        ">${datasetLabel}</div>
        <div style="
            font-size: 16px; 
            font-weight: 600;
        ">RM ${value}`;
    });

    innerHtml += '</div>';

    tooltipEl.querySelector('div').innerHTML = innerHtml;

    const position = chart.canvas.getBoundingClientRect();
    const chartWidth = chart.width;
    const chartHeight = chart.height;
    const tooltipWidth = tooltipEl.offsetWidth;
    const tooltipHeight = tooltipEl.offsetHeight;

    // Calculate the tooltip's position
    let left = position.left + window.pageXOffset + tooltipModel.caretX;
    let top = position.top + window.pageYOffset + tooltipModel.caretY;

    // Check for horizontal overflow
    if (left + tooltipWidth > position.left + window.pageXOffset + chartWidth) {
        left = position.left + window.pageXOffset + chartWidth - tooltipWidth;
    }
    if (left < position.left + window.pageXOffset) {
        left = position.left + window.pageXOffset;
    }

    // Check for vertical overflow
    if (top + tooltipHeight > position.top + window.pageYOffset + chartHeight) {
        top = position.top + window.pageYOffset + chartHeight - tooltipHeight;
    }
    if (top < position.top + window.pageYOffset) {
        top = position.top + window.pageYOffset;
    }

    // Apply the adjusted position
    tooltipEl.style.opacity = 1;
    tooltipEl.style.position = 'absolute';
    tooltipEl.style.left = `${left}px`;
    tooltipEl.style.top = `${top}px`;
    tooltipEl.style.padding = `${tooltipModel.padding}px ${tooltipModel.padding}px`;

}

const customPlugin = {
  id: 'hoverLabelColorChange',
  beforeEvent(chart, args) {
        const { event } = args;
        const { x, y } = event;
        const hoveredElements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
        
        if (hoveredElements.length > 0) {
            const index = hoveredElements[0].index;
            chart.hoveredIndex = index;
        } else {
            chart.hoveredIndex = null;
        }
    },
    afterDraw(chart) {
        // Ensure chart.hoveredIndex is defined
        if (chart.hoveredIndex === undefined) {
            chart.hoveredIndex = null;
        }

        chart.update();
    }
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions(); 
};

watch(
    [() => props.salesCategory.value, () => props.lastPeriodSales.value],
    () => {
        updateChart();
    },
    { deep: true }
);

onMounted(() => {
    updateChart();
});
</script>

<template>
    <div class="w-full h-full flex flex-col py-6 items-center gap-6 rounded-[5px] border border-solid border-primary-100">
        <div class="flex px-6 justify-between items-center self-stretch">
            <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Sales in Category</span>
            <!-- year filter and export here -->
            <div class="flex items-center gap-6">
                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton 
                            :disabled="isLoading"
                            :class="{'border-grey-100 opacity-60 pointer-events-none cursor-default': isLoading }" 
                            class="inline-flex items-center gap-3 justify-end py-3 pl-4 w-full ">
                            <span class="text-primary-900 font-base text-md">{{ selectedYear }}</span>
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
                                        { 'bg-primary-50 flex justify-between': selectedYear === year },
                                        { 'bg-white hover:bg-[#fff9f980]': selectedYear !== year },
                                        'group flex w-full items-center rounded-md px-6 py-3 text-base text-gray-900',
                                    ]"
                                    @click="useYearFilter(year, selectedCategory)"
                                >
                                    <span
                                        :class="[
                                            { 'text-primary-900 text-center text-base font-normal': selectedYear === year },
                                            { 'text-grey-700 text-center text-base font-normal group-hover:text-primary-800': selectedYear !== year },
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
                                    { 'bg-grey-50 pointer-events-none': salesCategory.length === 0 && lastPeriodSales.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" :disabled="salesCategory.length === 0 && lastPeriodSales.length === 0" @click="csvExport">
                                    CSV
                                </button>
                            </MenuItem>

                            <MenuItem v-slot="{ active }">
                                <button type="button" :class="[
                                    // { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': salesCategory.length === 0 && lastPeriodSales.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" :disabled="salesCategory.length === 0 && lastPeriodSales.length === 0">
                                    PDF
                                </button>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>

        <!-- chart, category filters and label here -->
        <template v-if="!isLoading">
            <div class="flex flex-col items-center gap-4 self-stretch">
                <!-- category filters + label -->
                <div class="w-full flex px-6 justify-between items-center self-stretch">
                    <div class="flex justify-between items-center">
                        <template v-for="category in categories" :key="category">
                            <div class="card flex flex-row flex-start gap-3">
                                <Button
                                    :type="'button'"
                                    :variant="'secondary'"
                                    :size="'md'"
                                    :class="
                                        {'bg-primary-50 hover:bg-primary-100 !px-6': selectedCategory === category, 
                                        'bg-white !text-grey-200 hover:!bg-[#ffe1e233] hover:!text-primary-800 !px-6': selectedCategory !== category}
                                        "
                                    @click="setActive(category, selectedYear)"
                                >
                                <span class="text-base font-medium">{{ category }}</span>
                                </Button>
                            </div>
                        </template>
                    </div>
                    <!-- labels -->
                    <div class="flex justify-center items-start gap-4">
                        <div class="flex items-center gap-2">
                            <div class="size-4 bg-primary-900 rounded-full"></div>
                            <span class="text-grey-700 text-sm font-medium">Current Period</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="size-4 bg-primary-200 rounded-full"></div>
                            <span class="text-grey-700 text-sm font-medium">Last Period</span>
                        </div>
                    </div>
                </div>

                <!-- chart -->
                <div class="flex flex-col items-center gap-4 self-stretch w-full h-full">
                    <Chart 
                        type="line"
                        :data="chartData"
                        :options="chartOptions"
                        :plugins="[customPlugin]"
                        class="w-full h-full min-h-[430px] px-6"
                    />
                </div>
            </div>
        </template>
        
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </template>
    </div>
</template>
