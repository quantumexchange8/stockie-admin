<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Chart from 'primevue/chart';

const props = defineProps({
    inventories: {
        type: Array,
        required: true,
    },
    keepItemsCount: Number,
})

const allInventories = ref([]);

watch(
    () => props.inventories,
    (newValue) => {
        allInventories.value = newValue;
    },
    { immediate: true }
);

const totalStock = ref(
    allInventories.value.reduce((total, item) => total + item.stock_qty, 0)
)

const chartData = ref();
const chartOptions = ref();

// const createGrad = (colorOne = "#62D7FA", opacityOne = 1, colorTwo = "#ffffff", opacityTwo = 1, colorStop = 95) => {

//     var rgbOne = hexToRgb(colorOne);	
//     var rgbTwo = hexToRgb(colorTwo);
//     colorStop = colorStop/100;

//     grd = ctx.getContext("2d").createRadialGradient(
//     canvasWidth/2, 
//     canvasWidth/2, 
//     0.000, 
//     canvasWidth/2, 
//     canvasWidth/2, 
//     canvasWidth/2
//     );

//     // Add colors
//     grd.addColorStop(0.000, 'rgba(' + rgbOne.r + ', ' + rgbOne.g + ', ' + rgbOne.b + ', ' + opacityOne + ')');
//     grd.addColorStop(colorStop, 'rgba(' + rgbOne.r + ', ' + rgbOne.g + ', ' + rgbOne.b + ', ' + opacityOne + ')');
//     grd.addColorStop(colorStop, 'rgba(' + rgbTwo.r + ', ' + rgbTwo.g + ', ' + rgbTwo.b + ', ' + opacityTwo + ')');
//     grd.addColorStop(1.000, 'rgba(' + rgbTwo.r + ', ' + rgbTwo.g + ', ' + rgbTwo.b + ', ' + opacityTwo + ')');

//     return grd;
// }

const setChartData = () => {
    return {
        labels: ['In stock', 'Keep'],
        datasets: [
            {
                data: [totalStock.value, props.keepItemsCount],
                backgroundColor: (context) => {
                    let bgColor = [
                        '#7E171B',
                        '#5E0A0E',
                    ]

                    if (!context.chart.chartArea) {
                        return;
                    }

                    let { ctx, chartArea: {top, bottom} } = context.chart;

                    let gradientBg = ctx.createLinearGradient(0, top, 0, bottom);
                    gradientBg.addColorStop(0, '#7E171B');
                    gradientBg.addColorStop(1, '#5E0A0E');

                    // Return the array of colors, first one as gradient, second one as solid color
                    return [gradientBg, '#FFE1E2'];
                },
                // hoverBackgroundColor: (context) => {
                //     if (!context.chart.chartArea) return;

                //     const { ctx, chartArea: { top, bottom } } = context.chart;
                //     const gradientBg = ctx.createLinearGradient(0, top, 0, bottom);

                //     gradientBg.addColorStop(0, '#7E171B');
                //     gradientBg.addColorStop(1, '#5E0A0E');

                //     return [gradientBg, '#FFE1E2'];
                // },
                // hoverBorderColor: (context) => {
                //     let bgColor = [
                //         '#7E171B',
                //         '#5E0A0E',
                //     ]

                //     if (!context.chart.chartArea) {
                //         return;
                //     }

                //     let { ctx, chartArea: {top, bottom} } = context.chart;

                //     let gradientBg = ctx.createLinearGradient(0, top, 0, bottom);
                //     gradientBg.addColorStop(0, '#7E171B');
                //     gradientBg.addColorStop(1, '#5E0A0E');

                //     // Return the array of colors, first one as gradient, second one as solid color
                //     return [gradientBg, '#FFE1E2'];
                // },
                hoverBorderWidth: 0,
                hoverBorderRadius: 0,
                cutout: '60%', // Sweep angle (converts 180 degrees to radians)
                circumference: 180, // Sweep angle (converts 180 degrees to radians)
                rotation: -90, // Start angle (converts 90 degrees to radians)
                maintainAspectRatio: true,
                responsive: true,
                // hoverOffset: 20,
            }
        ]
    };
};

const setChartOptions = () => {
    const stockText = totalStock.value;
    
    return {
        maintainAspectRatio: true,
        responsive: true,
        // hover: {
        //     mode: 'nearest',
        //     intersect: true,
        //     animationDuration: 400,
        //     onHover: (event, chartElement) => {
        //         if (chartElement.length) {
        //             event.native.target.style.cursor = 'pointer';
        //         } else {
        //             event.native.target.style.cursor = 'default';
        //         }
        //     }
        // },
        plugins: {
            legend: {
                position: 'bottom',  // Position the legend at the bottom
                labels: {
                    color: '#45535F',
                    usePointStyle: true,
                }
            },
            // onResize: function(chartInstance, newSize) {
            //     chartInstance.data.datasets.backgroundColor = createGrad(newSize);
            // },
            tooltip: {
                enabled: false,
                external: customTooltip,
            },
            // Custom plugin to draw text in the center of the donut
            totalStockText: {
                display: true,
                text: stockText
            },
            titleText: {
                display: true,
                text: 'Amount'
            },
            animation: {
                animateScale: true,
                animateRotate: true
            },
            hover: {
                mode: 'nearest',
                animationDuration: 400,
            },
        }
    };
};

const totalStockPlugin = {
    id: 'totalStock',
    beforeDraw: (chart) => {
        let { ctx, width, height } = chart;
        let text = chart.config.options.plugins.totalStockText.text || '0';
        ctx.save();
        ctx.font = '600 26px Lexend';
        ctx.fillStyle = '#7E171B';
        ctx.textAlign = 'center';
        ctx.fillText(text, width / 2, height / 1.9);
        ctx.restore();
    }
};

// Custom plugin to draw text in the center of the donut
const titleTextPlugin = {
    id: 'titleText',
    beforeDraw: (chart) => {
        let { ctx, width, height } = chart;
        let text = chart.config.options.plugins.titleText.text || 'Active';
        ctx.save();
        ctx.font = '12px Lexend';
        ctx.fillStyle = chart.config.options.plugins.legend.labels.color || '#45535F';
        ctx.textAlign = 'center';
        ctx.fillText(text, width / 2, height / 1.6);
        ctx.restore();
    }
};

const customTooltip = (context) => {
    // Tooltip Element
    let tooltipEl = document.getElementById('total-stock-tooltip');

    // Create element on first render
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'total-stock-tooltip';
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
            const colors = tooltipModel.labelColors[i];
            let style = 'font-size: 12px; font-weight: 900; letter-spacing: -0.24px; color: #7E171B; width: fit-content; position:absolute; z-index: 100; display: inline-flex; padding: 8px; justify-content: center; align-items: center; border-radius: 5px; background: rgba(255, 255, 255, 0.93); box-shadow: -2px 4px 10.9px 0px rgba(159, 64, 64, 0.09); backdrop-filter: blur(12.600000381469727px);';
            const span = '<span style="' + style + '">' + body + '</span>';
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

const hoverEffectPlugin = {
    id: 'hoverEffect',
    afterEvent: (chart, args) => {
        const { event, replay } = args;
        if (replay) return;

        const elements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);

        chart.data.datasets.forEach((dataset, datasetIndex) => {
            dataset.data.forEach((value, dataIndex) => {
                const element = chart.getDatasetMeta(datasetIndex).data[dataIndex];
                // element.shadow
                // if (elements.length && elements[0].index === dataIndex) {
                //     element.outerRadius = 155;
                // } else {
                //     // element.outerRadius -= 10;
                // }
            });
        });

        chart.update();
    }
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions();
};

watch(
    [totalStock],
    updateChart
);

onMounted(() => {
    updateChart();
});
</script>

<template>
    <div class="flex flex-col p-6 items-center rounded-[5px] border border-red-100 overflow-hidden">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Total Stock</span>
        <div class="flex justify-center items-center">
            <Chart 
                type="doughnut" 
                :data="chartData" 
                :options="chartOptions" 
                class="w-full md:w-48 xl:w-60 2xl:w-full [&>canvas]:flex" 
                :plugins="[totalStockPlugin, titleTextPlugin, hoverEffectPlugin]"
            />
        </div>
    </div>
</template>
