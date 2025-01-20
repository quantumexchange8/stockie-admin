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
    },
    waiterImages: {
        type: Array,
        default: () => {},
    },
    isLoading: Boolean,
})

const emit = defineEmits(['applyCommFilter', 'isLoading']);
const graphFilter = ref(['This month', 'This year']);
const selectedFilter = ref(graphFilter.value[0]);
const { formatAmount } = transactionFormat();
// const formattedCommission = props.waiterCommission.map(value => formatAmount(value));
const preloadedImages = props.waiterImages.map((src) => {
    const img = new Image();
    img.src = src;
    return img;
});

const chartData = ref([]);
const chartOptions = ref();

const isSelected = (filter) => {
    return selectedFilter.value && filter === selectedFilter.value;
};

const applyCommFilter = (filter) => {
    emit('isLoading', true)
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
                hoverRadius: 10,
                hitRadius: 10,
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
            drawYLabelsWithImagesPlugin: drawYLabelsWithImagesPlugin,
            userImages: props.waiterImages,
            horizontalYAxisTitlePlugin,
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
                    align: 'end',
                    color: '#48070A',
                    font: {
                        family: 'Lexend',
                        size: 12,
                        style: 'normal',
                        weight: 700,
                        lineHeight: 'normal',
                    },
                },
                beginAtZero: true,
            },
        },
    };
};

function customTooltipHandler(context) {
    // Tooltip element creation or selection
    let tooltipEl = document.getElementById('commission-earned-tooltip');
    
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'commission-earned-tooltip';
        tooltipEl.innerHTML = '<div></div>';
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

    // Determine data index and fetch user image
    const dataIndex = tooltipModel.dataPoints?.[0]?.dataIndex;
    const userImage = props.waiterImages?.[dataIndex] || 'default-image-url.jpg'; // Fallback for missing images

    // Clear previous alignment classes
    tooltipEl.classList.remove('top', 'bottom', 'center', 'no-transform');

    if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
    } else {
        tooltipEl.classList.add('no-transform');
    }

    // Construct tooltip body
    if (tooltipModel.body) {
        const body = tooltipModel.body.map(item => {
            const unformattedPrice = item.lines[0].replace(/,/g, "");
            const reformattedPrice = parseFloat(unformattedPrice).toFixed(2);

            return reformattedPrice;
        });

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
            box-shadow: 3px -10px 36.7px rgba(151, 97, 99, 0.13);
        ">`;
        
        // Add tooltip body (e.g., sales amount)
        innerHtml += `<div style="
        font-size: 16px; 
        font-weight: 600; 
        text-align: center;
        ">RM ${body}`;

        innerHtml += `<div style="
        display: flex; 
        justify-content: center; 
        align-items: center; 
        gap: 4px;">`;

        // Add user image
        innerHtml += `<img src="${userImage}" alt="User Image" style="
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 8px;
        " />`;

        // Add tooltip title (e.g., waiter name)
        if (tooltipModel.title) {
            const title = tooltipModel.title.join('<br>');
            innerHtml += `<div style="
                font-size: 12px; 
                font-weight: 400; 
                text-align: center;
                color: #7E171B;
                margin-bottom: 8px; 
                display: flex;
                gap: 4px;
            ">
            ${title}</div></div></div>`;
        }

        innerHtml += '</div>';
        tooltipEl.querySelector('div').innerHTML = innerHtml;
    }

        // Set tooltip position
        const position = context.chart.canvas.getBoundingClientRect();
        const chartWidth = position.right - position.left;
        const tooltipWidth = tooltipEl.offsetWidth;
        let tooltipX = tooltipModel.caretX;
            // Adjust tooltip position based on desired alignment 
            if (tooltipModel.caretX + tooltipWidth > chartWidth) {
            tooltipX = chartWidth - tooltipWidth;
            // Align tooltip to the right of the chart
            }
            else if (tooltipModel.caretX < tooltipWidth / 2) {
            tooltipX = 0;
            // Align tooltip to the left of the chart
            }
            else {
            tooltipX = tooltipModel.caretX - tooltipWidth / 2;
            // Center tooltip
        }
        tooltipEl.style.opacity = 1;
        tooltipEl.style.position = 'absolute';
        tooltipEl.style.left = position.left + window.pageXOffset + tooltipX + 'px';
        tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
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

const drawYLabelsWithImagesPlugin = {
    id: 'drawYLabelsWithImages',
    afterDraw: (chart) => {
        const ctx = chart.ctx;
        const yAxis = chart.scales.y;
        const xAxis = chart.scales.x;
        const {top, bottom} = yAxis;

        // Define the image size
        const imageSize = 20;

        // Get the user images from the preloaded images
        const userImages = preloadedImages;

        yAxis.ticks.forEach((tick, index) => {
            const image = userImages[index] || new Image();

            // Calculate the position for each image
            const yPosition = yAxis.getPixelForTick(index) - imageSize / 2;
            const xPosition = xAxis.left - imageSize - 10; // Adjust based on your layout

            // Ensure image is loaded before drawing
            ctx.save();
            ctx.beginPath();
            ctx.arc(xPosition + imageSize / 2, yPosition + imageSize / 2, imageSize / 2, 0, Math.PI * 2); // Create a circle
            ctx.clip(); // Clip to the circle
            ctx.drawImage(image, xPosition, yPosition, imageSize, imageSize); // Draw the image
            ctx.restore(); // Restore the context to avoid affecting other drawings
        });
    }
};

const horizontalYAxisTitlePlugin = {
    id: 'horizontalYAxisTitle',
    afterDraw(chart) {
        const ctx = chart.ctx;
        const yScale = chart.scales.y;
        
        const title = 'Waiter';
        if (!title) return;

        ctx.save();
        ctx.font = 'bold 12px Lexend';
        ctx.fillStyle = '#48070A';
        ctx.textAlign = 'center'; 
        ctx.textBaseline = 'middle'; 

        const x = yScale.left - 20; 
        const y = chart.chartArea.top - 10; 

        ctx.translate(x, y); 
        ctx.rotate(-Math.PI / 2); 
        ctx.fillText(title, 0, 0);
        
        ctx.restore();
    },
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
                        <MenuButton 
                            :disabled="isLoading"
                            :class="{'border-grey-100 opacity-60 pointer-events-none cursor-default': isLoading }"
                            class="inline-flex items-center gap-3 justify-end py-3 pl-4 w-full ">
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
            <div class="w-full h-full">
                <template v-if="props.waiterCommission && props.waiterNames && !isLoading">
                    <Chart 
                        type="bar" 
                        :data="chartData" 
                        :options="chartOptions"
                        :plugins="[customPlugin, drawYLabelsWithImagesPlugin, horizontalYAxisTitlePlugin]"
                        class="[&>canvas]:!w-full [&>canvas]:min-h-[360px]"
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
