<script setup>
import Button from "@/Components/Button.vue";
import { UndetectableIllus } from "@/Components/Icons/illus";
import Chart from "primevue/chart";
import { ref, onMounted, watch, computed } from "vue";

const props = defineProps ({
    salesData: Array,
    labels: Array,
    activeFilter: String,
    isLoading: Boolean,
})
const chartData = ref();
const chartOptions = ref();
const emit = defineEmits(["applyTimeFilter", "isLoading"]);
        
const setChartData = () => {
    const salesData = props.salesData;
    const monthLabels = props.labels;

    return {
        labels: monthLabels,
        datasets: [
            {
                label: '',
                data: salesData,
                fill: true,
                borderColor: '#7E171B',
                tension: 0.4,
                borderWidth: 2.5,
                pointStyle: 'circle',
                pointRadius: 0,
                pointHoverRadius: 10,
                pointHitRadius: 10,
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
            }
        ]
    };
};

const setChartOptions = () => {

    return {
        maintainAspectRatio: false,
        aspectRatio: 0.6,
        responsive: true,
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
    // Tooltip element creation or selection
    let tooltipEl = document.getElementById('dashboard-tooltip');

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'dashboard-tooltip';
        tooltipEl.innerHTML = '<div></div>';
        document.body.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    const tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
    }

    if (tooltipModel.body) {
        const body = tooltipModel.body.map(item => item.lines);

        let innerHtml = `<div style="
            display: flex; 
            flex-direction: column;  
            background: #7E171B; 
            border-radius: 10px; 
            color: #FFF9F9; 
            padding: 12px 24px; 
            gap: 4px;
            align-items: center; 
            pointer-events: none;
        ">`;

        // Add title at the bottom of the body (customized position)
        innerHtml += `<div style="
            font-size: 14px; 
            font-weight: bold; 
            margin-top: 5px;
        ">RM ${body}</div>`;

        // Add tooltip title (e.g., waiter name)
        if (tooltipModel.title) {
            const title = tooltipModel.title.join('<br>');
            innerHtml += `<div style="
                margin-bottom: 5px;
                color: #FFC7C9;
                text-align:right;
                font-size: 12px;
                font-weight: 400;
                line-height: normal;
            ">
            ${title}</div></div>`;
        }

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

const activeFilter = ref('month');

const setActive = (button) => {
    emit('isLoading', true);
    activeFilter.value = button;
    emit('applyTimeFilter', activeFilter.value);
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions(); 
};

watch(
    [() => props.salesData.value, () => props.activeFilter],
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
    <div class="flex flex-col p-6 items-start gap-8 rounded-[5px] border border-red-100 overflow-hidden min-h-[350px]">
        <div class="w-full flex justify-center items-center">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">{{ $t('public.sales') }}</span>
            <div class="card flex flex-row flex-start gap-3">
                <Button
                    :type="'button'"
                    :variant="'secondary'"
                    :size="'md'"
                    :disabled="isLoading"
                    :class="
                        { 'pointer-events-none cursor-not-allowed !bg-white': isLoading,
                        'bg-primary-50 hover:bg-primary-100': activeFilter === 'week', 
                        'bg-white !text-grey-200 hover:!bg-[#ffe1e233] hover:!text-primary-800': activeFilter !== 'week'}
                        "
                    @click="setActive('week')"
                >
                    {{ $t('public.week') }}
                </Button>
                <Button
                    :type="'button'"
                    :variant="'secondary'"
                    :size="'md'"
                    :disabled="isLoading"
                    :class="
                        { 'pointer-events-none cursor-not-allowed !bg-white': isLoading,
                        'bg-primary-50 hover:bg-primary-100': activeFilter === 'month', 
                        'bg-white !text-grey-200 hover:!bg-[#ffe1e233] hover:!text-primary-800': activeFilter !== 'month'}
                        "
                    @click="setActive('month')"
                >
                    {{ $t('public.month') }}
                </Button>
                <Button
                    :type="'button'"
                    :variant="'secondary'"
                    :size="'md'"
                    :disabled="isLoading"
                    :class="
                        { 'pointer-events-none cursor-not-allowed !bg-white': isLoading,
                        'bg-primary-50 hover:bg-primary-100': activeFilter === 'year', 
                        'bg-white !text-grey-200 hover:!bg-[#ffe1e233] hover:!text-primary-800': activeFilter !== 'year'}
                        "
                    @click="setActive('year')"
                >
                    {{ $t('public.year') }}
                </Button>
            </div>
        </div>
        <div class="flex justify-content-center h-full w-full" v-if="props.salesData.length > 0 && !isLoading">
            <Chart 
                type="line" 
                :data="chartData" 
                :options="chartOptions" 
                class="h-full w-full"
                :plugins="[customPlugin]"
            />
        </div>
        <div class="flex flex-col w-full h-full justify-center items-center" v-else>
            <UndetectableIllus class="w-44 h-44"/>
            <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
        </div>
    </div>
</template>


