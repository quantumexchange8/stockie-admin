<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Chart from 'primevue/chart';
import { UndetectableIllus } from '@/Components/Icons/illus.jsx';

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
})

const allProducts = ref([]);

watch(
    () => props.products,
    (newValue) => {
        allProducts.value = newValue;
    },
    { immediate: true }
);

const beerProducts = computed(() => {
    return allProducts.value.filter(item => item.category.name === 'Beer').length;
});

const wineProducts = computed(() => {
    return allProducts.value.filter(item => item.category.name === 'Wine').length;
});

const liquorProducts = computed(() => {
    return allProducts.value.filter(item => item.category.name === 'Liquor').length;
});

const otherProducts = computed(() => {
    return allProducts.value.filter(item => item.category.name === 'Others').length;
});

const totalProductChart = ref();
const chartLegendData = ref();
const chartData = ref();
const chartOptions = ref();

const setChartData = () => {
    return {
        labels: ['Beer', 'Wine', 'Liquor', 'Others'],
        datasets: [
            {
                data: [beerProducts.value, wineProducts.value, liquorProducts.value, otherProducts.value],
                backgroundColor: ['#9F151A', '#0C82EB', '#388E22', '#E46A2B'],
                hoverBorderWidth: 0,
                hoverBorderRadius: 0,
            }
        ]
    };
};

const setChartOptions = () => {
    return {
        plugins: {
            legend: {
                position: 'bottom',  // Position the legend at the bottom
                labels: {
                    color: '#45535F',
                    usePointStyle: true,
                    padding: 25,
                },
            },
            tooltip: {
                enabled: false,
                external: customTooltip,
            },
        },
        layout: {
            autoPadding: true
        }
    };
};

const customTooltip = (context) => {
    // Tooltip Element
    let tooltipEl = document.getElementById('chartjs-tooltip');

    // Create element on first render
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'chartjs-tooltip';
        tooltipEl.innerHTML = '<table></table>';
        document.body.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    const tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
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
            let calculatedPercentage = (allProducts.value.length > 0 ? body / allProducts.value.length * 100 : 0).toFixed(2) + '%';
            const colors = tooltipModel.labelColors[i];
            let style = 'font-size: 12px; font-weight: 900; letter-spacing: -0.24px; color: #7E171B; width: fit-content; position:absolute; z-index: 100; display: inline-flex; padding: 8px; justify-content: center; align-items: center; border-radius: 5px; background: rgba(255, 255, 255, 0.93); box-shadow: -2px 4px 10.9px 0px rgba(159, 64, 64, 0.09); backdrop-filter: blur(12.600000381469727px);';
            const span = '<span style="' + style + '">' + calculatedPercentage + '</span>';
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
    [beerProducts, wineProducts, liquorProducts, otherProducts],
    updateChart
);

// watch(
//     () => totalProductChart.value,
//     (newValue) => {
//         chartLegendData.value = newValue.data;
//     },
//     { deep: true }
// );

onMounted(() => {
    updateChart();
});
</script>

<template>
    <div class="flex flex-col p-6 gap-12 items-center rounded-[5px] border border-red-100 overflow-hidden">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Total Product</span>
        <div class="flex flex-col justify-content-center" v-if="allProducts.length > 0">
            <Chart 
                ref="totalProductChart"
                type="doughnut" 
                :data="chartData" 
                :options="chartOptions" 
                class="w-64 xl:w-72" 
            />
            <!-- <ul>
                <li v-for="(item, index) in chartLegendData." :key="index"></li>
            </ul>-->
        </div>
        
        <div v-else>
            <UndetectableIllus class="w-44 h-44" />
            <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
        </div>
    </div>
</template>
