<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { DropdownIcon } from '@/Components/Icons/solid';
import Chart from "primevue/chart";
import { ref, onMounted, watch } from "vue";
import { UndetectableIllus } from '@/Components/Icons/illus';
import { transactionFormat } from '@/Composables';

const props = defineProps ({
    waiterNames: {
        type: Array,
        default: () => {},
    },
    waiterCommission: {
        type: Array,
        default: () => {},
    }
})

const emit = defineEmits(['applyCommFilter']);
const graphFilter = ref(['This month', 'This year']);
const selectedFilter = ref(graphFilter.value[0]);
const { formatAmount } = transactionFormat();
// const formattedCommission = props.waiterCommission.map(value => formatAmount(value));

const chartData = ref();
const chartOptions = ref();

const isSelected = (filter) => {
    return selectedFilter.value && filter === selectedFilter.value;
};

const applyCommFilter = (filter) => {
    selectedFilter.value = filter;
    emit('applyCommFilter', selectedFilter.value);
}

const setChartData = () => {
    const backgroundColor = [];
    const sortedComm = [...props.waiterCommission].sort((a, b) => b - a); 

    props.waiterCommission.forEach(comm => {
        if (comm === sortedComm[0]) {
            // Highest sales value
            backgroundColor.push('#48070A');
        } else if (comm === sortedComm[1]) {
            // Second highest sales value
            backgroundColor.push('#7E171B');
        } else {
            // Other sales values
            backgroundColor.push('#9F151A');
        }
    });

    return {
        labels: props.waiterNames,
        datasets: [
            {
                data: props.waiterCommission,
                backgroundColor: (context) => {
                    const hoveredIndex = context.chart.hoveredIndex;
                    const index = context.index;

                    if (hoveredIndex === null ) {
                        return backgroundColor;
                    }
                    return hoveredIndex === index ? backgroundColor : '#FFF1F2';
                },
                borderColor: '#7E171B',
                borderRadius: 5,
                hoverRadius: 5,
                hitRadius: 5,
            }
        ]
    };
};

const setChartOptions = () => {
    return {
        indexAxis: 'y',
        maintainAspectRatio: false,
        aspectRatio: 0.6,
        responsive: true,
        animation: {
            duration: 150,  
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
            ticksAsCirclesPlugin: {

            }
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
                    color: '#FFE1E2',
                    drawTicks: true,
                    offset: false,
                },
                border: {
                    dash: [5, 5]
                },
                title: {
                    display: true,
                    text: 'Commission Amount (RM)',
                    align: 'center',
                    color: '#48070A',
                    font: {
                        family: 'Lexend',
                        size: 12,
                        style: 'normal',
                        weight: 500,
                        lineHeight: 'normal',
                    }
                },
                beginAtZero: true,
            },
            y: {
                ticks: {
                    display: false,
                },
                grid: {
                    display: false,
                },
                title: {
                    display: true,
                    text: 'Waiter',
                    align: 'end',
                    color: '#48070A',
                    font: {
                        family: 'Lexend',
                        size: 12,
                        style: 'normal',
                        weight: 700,
                        lineHeight: 'normal',
                    }
                },
            }
        },
        
    };
    
};

function customTooltipHandler(context) {
    // Tooltip element creation or selection
    let tooltipEl = document.getElementById('chartjs-tooltip');

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'chartjs-tooltip';
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


        innerHtml += `<div style="
            font-size: 16px; 
            font-weight: 600; 
            text-align: right;
        ">RM ${body}</div>`;

        if (tooltipModel.title) {
        const title = tooltipModel.title.join('<br>');
        innerHtml += `<div style="
            font-size: 12px; 
            font-weight: 400; 
            text-align: right;
            color: #7E171B;
            margin-bottom: 8px; 
            display: flex;
            gap: 4px;
        ">
        <div style="
            width: 20px;
            height: 20px;
            border-radius: 20px;
            background: #C1141B;
            border: 1px solid var(--White-fixed, #FFF);
        "></div>
        ${title}</div>`;
        }

        innerHtml += '</div>';


        tooltipEl.querySelector('div').innerHTML = innerHtml;
    }

    const position = context.chart.canvas.getBoundingClientRect();
    tooltipEl.style.opacity = 1;
    tooltipEl.style.position = 'absolute';

    const tooltipWidth = tooltipEl.offsetWidth || 0;
    let tooltipX = position.left + window.pageXOffset + tooltipModel.caretX - tooltipWidth / 2;

    if (tooltipX < position.left + window.pageXOffset) {
        tooltipX = position.left + window.pageXOffset + 10;
    } else if (tooltipX + tooltipWidth > position.left + window.pageXOffset + position.width) {
        tooltipX = position.left + window.pageXOffset + position.width - tooltipWidth - 10; 
    }

    tooltipEl.style.left = tooltipX + 'px';

    const tooltipHeight = tooltipEl.offsetHeight || 0;
    tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY - tooltipHeight - 10 + 'px';

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

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions(); 
};

watch(
    [()=> props.waiterNames.value, () => props.waiterCommission.value],
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
    <div class="flex flex-col w-full h-full items-end gap-6">
        <div class="flex flex-col items-start gap-[10px] self-stretch">
            <div class="flex justify-between items-center self-stretch  whitespace-nowrap">
                <span class="text-md font-medium text-primary-900 w-full">Commission Earned</span>

                <!-- menu for year filter -->
                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton class="inline-flex items-center gap-3 justify-end py-3 pl-4 w-full ">
                            <span class="text-primary-900 font-base text-md">{{ selectedFilter }}</span>
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
                                v-for="filter in graphFilter"
                                :key="filter"
                            >
                                <button
                                    type="button"
                                    :class="[
                                        { 'bg-primary-50 flex justify-between': isSelected(filter) },
                                        { 'bg-white hover:bg-[#fff9f980]': !isSelected(filter) },
                                        'group flex w-full items-center rounded-md px-6 py-3 text-base text-gray-900',
                                    ]"
                                    @click="applyCommFilter(filter)"
                                >
                                    <span
                                        :class="[
                                            { 'text-primary-900 text-center text-base font-medium': isSelected(filter) },
                                            { 'text-grey-700 text-center text-base font-normal group-hover:text-primary-800': !isSelected(filter) },
                                        ]"
                                    >
                                        {{ filter }}
                                    </span>
                                </button>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>

        <div class="flex flex-col justify-center items-start gap-3 self-stretch min-h-[360px]">
            <div class="w-full !h-full">
                <template v-if="props.waiterCommission && props.waiterNames">
                    <Chart 
                        type="bar" 
                        :data="chartData" 
                        :options="chartOptions"
                        :plugins="[customPlugin]"
                        class="h-full"
                    />
                </template>
                <template v-else>
                    <div class="flex flex-col w-full h-full justify-center items-center">
                        <UndetectableIllus class="w-44 h-44"/>
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
