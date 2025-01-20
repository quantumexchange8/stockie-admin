<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { DropdownIcon } from '@/Components/Icons/solid';
import Chart from "primevue/chart";
import { ref, onMounted, watch } from "vue";
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps ({
    waiterName: {
        type: Array,
        default: () => {},
    },
    waiterSales: {
        type: Array,
        default: () => {},
    },
    waiterImages: {
        type: Array,
        default: () => {},
    },
    isLoading: Boolean,
})
const emit = defineEmits(['applyFilter', 'isLoading']);
const graphFilter = ref(['This month', 'This year']);
const selected = ref(graphFilter.value[0]);
const chartData = ref();
const chartOptions = ref();

const isSelected = (filter) => {
    return selected.value && filter === selected.value;
};

const applyFilter = (filter) => {
    emit('isLoading', true);
    selected.value = graphFilter.value.find(f => f === filter);
    emit('applyFilter', selected.value);
}

const preloadedImages = props.waiterImages.map((src) => {
    const img = new Image();
    img.src = src;
    return img;
});

const setChartData = () => {
    const backgroundColor = [];
    const sortedSales = [...props.waiterSales].sort((a, b) => b - a); 

    props.waiterSales.forEach(sale => {
        if (sale === sortedSales[0]) {
            backgroundColor.push('#48070A');
        } else if (sale === sortedSales[1]) {
            backgroundColor.push('#7E171B');
        } else {
            backgroundColor.push('#9F151A');
        }
    });

    // console.log(props.waiterSales);
    return {
        labels: props.waiterName,
        datasets: [
            {
                data: props.waiterSales,
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
                hitRadius: 10,
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
            duration: 150, 
            easing: 'linear',
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: false,
                position: 'nearest',
                external: customTooltipHandler,
            },
            userImages: props.waiterImages,
        },
        scales: {
            x: {
                ticks: {
                    display: false,
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
                title: {
                    display: true,
                    text: 'RM',
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
                beginAtZero: true,
            }
        }
    };
};

function customTooltipHandler(context) {
    // Tooltip element creation or selection
    let tooltipEl = document.getElementById('sales-performance-tooltip');

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'sales-performance-tooltip';
        tooltipEl.innerHTML = '<div></div>'; // Ensure inner <div> is present
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

    // Ensure tooltip has inner <div>
    let tooltipContentEl = tooltipEl.querySelector('div');
    if (!tooltipContentEl) {
        tooltipContentEl = document.createElement('div');
        tooltipEl.appendChild(tooltipContentEl);
    }

    // Determine data index and fetch user image
    const dataIndex = tooltipModel.dataPoints?.[0]?.dataIndex;
    const userImage = props.waiterImages?.[dataIndex] || 'default-image-url.jpg';

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
            justify-items: center;
            pointer-events: none;
            box-shadow: 3px -10px 36.7px rgba(151, 97, 99, 0.13);
        ">`;

        // Add tooltip body (e.g., sales amount)
        innerHtml += `<div style="
        font-size: 16px; 
        font-weight: 600; 
        text-align: center;
        ">RM ${body}</div>`;

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
            ${title}</div></div>`;
        }

        innerHtml += '</div>';
        tooltipContentEl.innerHTML = innerHtml;
    }

    // Set tooltip position
    const position = context.chart.canvas.getBoundingClientRect();
    tooltipEl.style.opacity = 1;
    tooltipEl.style.position = 'absolute';
    tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
    tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
}

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

const drawLabelsOnBarsPlugin = {
    id: 'drawLabelsOnBars',
    afterDatasetsDraw: (chart) => {
        const ctx = chart.ctx;

        chart.data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex);

            meta.data.forEach((bar, index) => {
                const image = preloadedImages[index] || new Image();
                const x = bar.x;
                const y = bar.y - 20;
                const imageSize = 20;
                const radius = imageSize / 2;

                image.onload = () => {
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(x, y, radius, 0, Math.PI * 2); 
                    ctx.clip(); 
                    ctx.drawImage(image, x - radius, y - radius, imageSize, imageSize); 
                    ctx.restore(); 
                };

                // Ensure the image is loaded before drawing
                if (image.complete) {
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(x, y, radius, 0, Math.PI * 2);
                    ctx.clip();
                    ctx.drawImage(image, x - radius, y - radius, imageSize, imageSize);
                    ctx.restore();
                } else {
                    image.src = preloadedImages[index].src;
                }
            });
        });
    },
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions(); 
};

watch(
    [()=> props.waiterName.value, () => props.waiterSales.value],
    () => {
        updateChart();
    },
    { deep: true }
);

// watch(selected, () => {
//     emit('isLoading')
//     console.log(isLoading.value)
// } ,{ immediate: true });

onMounted(() => {
    updateChart();
});

</script>

<template>
    <div class="flex flex-col gap-6 w-full h-full">
        <div class="flex justify-between items-center self-stretch whitespace-nowrap w-full">
            <span class="text-md font-medium text-primary-900">Sales Performance</span>

            <!-- menu for year filter -->
            <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton 
                        :disabled="isLoading"
                        :class="{'border-grey-100 opacity-60 pointer-events-none cursor-default': isLoading }" 
                        class="inline-flex items-center gap-3 justify-end py-3 pl-4 w-full">
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
                                @click="applyFilter(filter)"
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
        <template v-if="props.waiterName.length > 0 && props.waiterSales.length > 0 && !isLoading">
            <Chart
                type="bar"
                :data="chartData"
                :options="chartOptions"
                :plugins="[customPlugin, drawLabelsOnBarsPlugin]"
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
</template>
