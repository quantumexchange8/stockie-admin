<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Chart from 'primevue/chart';
import { UndetectableIllus } from '@/Components/Icons/illus.jsx';

const props = defineProps({
    inventories: {
        type: Array,
        required: true,
    },
})

const allInventories = ref([]);

watch(props.inventories, (newValue) => allInventories.value = newValue, { immediate: true });

const inStockInventories = computed(() => {
    return allInventories.value.filter(item => item.stock_qty > 1 && item.stock_qty > item.low_stock_qty).length;
});

const lowInStockInventories = computed(() => {
    return allInventories.value.filter(item => item.stock_qty > 1 && item.stock_qty <= item.low_stock_qty).length;
});

const outOfStockInventories = computed(() => {
    return allInventories.value.filter(item => item.stock_qty < 1).length;
});

const activeInventoryCount = computed(() => {
    const totalInventories = allInventories.value.length;
    const totalOutOfStockInventories = outOfStockInventories.value;

    return totalInventories > 0 ? (totalInventories - totalOutOfStockInventories) / totalInventories * 100 : 0;
})

const chartData = ref();
const chartOptions = ref();

const setChartData = () => {
    return {
        labels: ['In stock', 'Low in stock', 'Out of stock'],
        datasets: [
            {
                data: [inStockInventories.value, lowInStockInventories.value, outOfStockInventories.value],
                backgroundColor: ['#7E171B', '#9F151A', '#E51D25'],
                hoverBorderWidth: 0,
                hoverBorderRadius: 0,
            }
        ]
    };
};

const setChartOptions = () => {
    const percentageText = activeInventoryCount.value.toFixed(2) + '%';
    
    return {
        plugins: {
            legend: {
                position: 'bottom',  // Position the legend at the bottom
                labels: {
                    color: '#45535F',
                    usePointStyle: true,
                }
            },
            tooltip: {
                enabled: false,
                external: customTooltip,
            },
            // Custom plugin to draw text in the center of the donut
            statusText: {
                display: true,
                text: 'Active'
            },
            percentageText: {
                display: true,
                text: percentageText
            }
        }
    };
};

// Custom plugin to draw text in the center of the donut
const statusTextPlugin = {
    id: 'statusText',
    beforeDraw: (chart) => {
        let { ctx, width, height } = chart;
        let text = chart.config.options.plugins.statusText.text || 'Active';
        ctx.save();
        ctx.font = '14px Lexend';
        ctx.fillStyle = chart.config.options.plugins.legend.labels.color || '#45535F';
        ctx.textAlign = 'center';
        ctx.fillText(text, width / 2, height / 2.95);
        ctx.restore();
    }
};

const percentageTextPlugin = {
    id: 'percentageText',
    beforeDraw: (chart) => {
        let { ctx, width, height } = chart;
        let text = chart.config.options.plugins.percentageText.text || '0%';
        ctx.save();
        ctx.font = '600 18px Lexend';
        ctx.fillStyle = '#7E171B';
        ctx.textAlign = 'center';
        ctx.fillText(text, width / 2, height / 2.35);
        ctx.restore();
    }
};

const customTooltip = (context) => {
    // Tooltip Element
    let tooltipEl = document.getElementById('inventory-summary-tooltip');

    // Create element on first render
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'inventory-summary-tooltip';
        tooltipEl.innerHTML = '<table></table>';
        document.body.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    const tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
    }

    if (!tooltipEl || !tooltipModel || !tooltipModel.dataPoints) {
        return; // Safely exit if there's no data
    }

    // Set caret Position
    tooltipEl.classList.remove('above', 'below', 'no-transform');
    if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
    } else {
        tooltipEl.classList.add('no-transform');
    }

    function getBody(bodyItem) {
        return bodyItem.lines;
    }

    // Set Text
    if (tooltipModel.body) {
        const titleLines = tooltipModel.title || [];
        const bodyLines = tooltipModel.body.map(getBody);

        let innerHtml = '<tbody>';

        // titleLines.forEach(function (title) {
        //     innerHtml += '<tr><th>' + title + '</th></tr>';
        // });
        // innerHtml += '</tbody><tbody>';

        bodyLines.forEach(function (body, i) {
            let calculatedStock = (allInventories.value.length > 0 ? body / allInventories.value.length * 100 : 0).toFixed(2) + '%';
            const colors = tooltipModel.labelColors[i];
            let style = 'font-size: 12px; font-weight: 900; letter-spacing: -0.24px; color: #7E171B; width: fit-content; position:absolute; z-index: 100; display: inline-flex; padding: 8px; justify-content: center; align-items: center; border-radius: 5px; background: rgba(255, 255, 255, 0.93); box-shadow: -2px 4px 10.9px 0px rgba(159, 64, 64, 0.09); backdrop-filter: blur(12.600000381469727px);';
            const span = '<span style="' + style + '">' + calculatedStock + '</span>';
            innerHtml += '<tr><td>' + span + '</td></tr>';
        });
        innerHtml += '</tbody>';

        let tableRoot = tooltipEl.querySelector('table');
        tableRoot.innerHTML = innerHtml;
    }

    // `this` will be the overall tooltip
    const position = context.chart.canvas.getBoundingClientRect();

    // Display, position, and set styles for font
    tooltipEl.style.opacity = 1;
    tooltipEl.style.position = 'absolute';
    tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
    tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
    tooltipEl.style.fontFamily = tooltipModel.options.bodyFont.family;
    tooltipEl.style.fontSize = tooltipModel.options.bodyFont.size + 'px';
    tooltipEl.style.fontStyle = tooltipModel.options.bodyFont.style;
    tooltipEl.style.padding = tooltipModel.padding + 'px ' + tooltipModel.padding + 'px';
    tooltipEl.style.pointerEvents = 'none';
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions();
};

watch(
    [inStockInventories, lowInStockInventories, outOfStockInventories, activeInventoryCount],
    updateChart
);

onMounted(() => {
    updateChart();
});
</script>

<template>
    <div class="flex flex-col p-6 gap-12 items-center rounded-[5px] border border-red-100 overflow-hidden justify-between w-full h-full">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Inventory Summary</span>
        <div class="flex justify-content-center" v-if="allInventories.length > 0">
            <Chart 
                type="doughnut" 
                :data="chartData" 
                :options="chartOptions" 
                class="w-full md:w-60" 
                :plugins="[statusTextPlugin, percentageTextPlugin]"
            />
        </div>
        
        <div v-else>
            <UndetectableIllus class="w-44 h-44" />
            <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
        </div>
    </div>
</template>
