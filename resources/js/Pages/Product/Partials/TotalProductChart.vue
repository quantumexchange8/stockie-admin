<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Chart from 'primevue/chart';
import { UndetectableIllus } from '@/Components/Icons/illus.jsx';

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
    categoryArr: Array,
    isLoading: Boolean,
})

const allProducts = ref([]);
const emit = defineEmits('isLoading');

watch(() => props.products, (newValue) => {
    allProducts.value = newValue;
}, { immediate: true });

// const beerProducts = computed(() => {
//     return allProducts.value.filter(item => item.category.name === 'Beer').length;
// });

// const wineProducts = computed(() => {
//     return allProducts.value.filter(item => item.category.name === 'Wine').length;
// });

// const liquorProducts = computed(() => {
//     return allProducts.value.filter(item => item.category.name === 'Liquor').length;
// });

// const otherProducts = computed(() => {
//     return allProducts.value.filter(item => item.category.name === 'Others').length;
// });

// Dynamic computation of products per category
const productsByCategory = computed(() => {
    const categoryCounts = props.categoryArr.reduce((acc, category, index) => {
        acc[index] = {
            'name': category.text,
            'count': allProducts.value.filter(
                        item => item.category.name === category.text
                    ).length
        };
        return acc;
    }, []);

    const sortedCategories = categoryCounts.sort((a, b) => {
        if (b.count === a.count) {
            // If counts are equal, sort alphabetically by category name
            return a.name.localeCompare(b.name);
        }
        return b.count - a.count;
    });

    return sortedCategories;
});

// Color mapping for categories
const categoryColors = {
    'Beer': '#9F151A',
    'Wine': '#0C82EB',
    'Liquor': '#388E22',
    'Others': '#E46A2B'
};

const getColorForCategory = (categoryName) => {
    if (categoryColors[categoryName]) {
        return categoryColors[categoryName];
    }

    // Function to generate a random RGB color with controlled brightness
    const randomColor = () => {
        const min = 0; // Minimum brightness for each color channel
        const max = 230; // Maximum brightness to avoid colors too close to white
        const r = Math.floor(Math.random() * (max - min + 1)) + min;
        const g = Math.floor(Math.random() * (max - min + 1)) + min;
        const b = Math.floor(Math.random() * (max - min + 1)) + min;

        return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
    };

    const color = randomColor();
    categoryColors[categoryName] = color;
    
    return color;
};

const totalProductChart = ref(null);
const chartLegendData = ref();
const chartData = ref();
const chartOptions = ref();

const setChartData = () => {
    const categories = productsByCategory.value.map(cat => cat.name);
    const data = categories.map(category => productsByCategory.value.find(cat => cat.name === category).count || 0);
    const backgroundColor = categories.map(category => getColorForCategory(category));

    return {
        labels: categories,
        datasets: [
            {
                data: data,
                backgroundColor: backgroundColor,
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
                display: false,
                position: 'bottom',  // Position the legend at the bottom
                labels: {
                    color: '#45535F',
                    usePointStyle: true,
                    padding: 25,
                },
                maxWidth: 5
            },
            tooltip: {
                enabled: false,
                external: customTooltip,
            },
        },
        layout: {
            autoPadding: true
        },
    };
};

const customTooltip = (context) => {
    // Tooltip Element
    let tooltipEl = document.getElementById('product-tooltip');

    // Create element on first render
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'product-tooltip';
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

// Custom legend generation
const generateCustomLegend = () => {
    if (!chartData.value) return [];
    return chartData.value.labels.map((label, index) => ({
        label,
        color: chartData.value.datasets[0].backgroundColor[index],
        value: chartData.value.datasets[0].data[index]
    }));
};

const updateChart = () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions();
    
};

// watch(
//     [beerProducts, wineProducts, liquorProducts, otherProducts],
//     updateChart
// );

// Watch for changes in products by category
watch(
    () => productsByCategory.value,
    updateChart,
    { deep: true }
);

watch(
    () => props.categoryArr,
    updateChart,
    { deep: true }
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
    <div class="flex flex-col p-6 items-center rounded-[5px] gap-y-6 border border-red-100 overflow-hidden md:max-w-[463px]">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Total Product</span>

        <div class="flex flex-col justify-content-center items-center justify-center size-full" v-if="allProducts.length > 0 && !isLoading">
            <Chart 
                ref="totalProductChart"
                type="doughnut" 
                :data="chartData" 
                :options="chartOptions" 
                class="max-w-full xl:max-w-64" 
            />
            
        </div>
        <div v-else>
            <UndetectableIllus class="w-44 h-44" />
            <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
        </div>

        <!-- Scrollable legend container -->
        <div class="overflow-y-auto min-h-11 max-w-full" v-if="allProducts.length > 0 && !isLoading">
            <div class="flex gap-4 justify-start">
                <div 
                    v-for="(item, index) in generateCustomLegend()" 
                    :key="index"
                    class="flex items-center gap-2 min-w-fit"
                >
                    <div class="size-3 rounded-full" :style="{ backgroundColor: item.color }"></div>
                    <span class="text-sm text-primary-900">{{ item.label }}</span>
                </div>
            </div>
        </div>
        
    </div>
</template>
