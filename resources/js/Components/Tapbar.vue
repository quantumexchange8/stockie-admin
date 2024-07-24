<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { RadioGroup, RadioGroupLabel, RadioGroupOption } from '@headlessui/vue'
import { AppsIcon, BeerIcon, WineWithGlassIcon, LiquorIcon, CocktailIcon } from './Icons/solid';

const props = defineProps({
    optionArr: {
        type: Array,
        default: () => [],
    },
    checked: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['update:checked']);

const selected = ref(props.checked);
const categoryArr = ref([]);
const icons = {
    'All': AppsIcon,
    'Beer': BeerIcon,
    'Wine': WineWithGlassIcon,
    'Liquor': LiquorIcon,
    'Others': CocktailIcon,
};

watch(
    () => props.optionArr,
    (newValue) => {
        categoryArr.value = [...newValue];
    },
    { immediate: true }
);

onMounted(() => {
    categoryArr.value = [...props.optionArr];
})

const proxyChecked = computed({
    get() {
        return selected.value;
    },
    set(val) {
        selected.value = val;
        emit('update:checked', val);
    }
});
</script>
<template>
    <div class="w-full">
        <RadioGroup v-model="proxyChecked">
            <div class="flex flex-wrap sm:flex-nowrap items-center justify-start gap-4">
                <RadioGroupOption
                    as="template"
                    v-for="option in categoryArr"
                    :key="option.value"
                    :value="option.value"
                    v-slot="{ active, checked }"
                >
                    <div
                        :class="[
                            active
                                ? 'border-primary-950'
                                : '',
                            checked ? 'bg-primary-900 text-white border-primary-950' : 'bg-white border-primary-100 text-primary-900 hover:bg-primary-50 hover:border-primary-800 hover:text-primary-800',
                        ]"
                        class="relative flex cursor-pointer rounded-[5px] px-3 py-2 border focus:outline-none"
                    >
                        <RadioGroupLabel
                            as="p"
                            class="w-full text-base cursor-pointer flex flex-nowrap gap-2 items-center"
                        >
                            <component
                                :is="icons[option.text]"
                                class="w-5 h-5"
                            />
                            {{ option.text }}
                        </RadioGroupLabel>
                    </div>
                </RadioGroupOption>
            </div>
        </RadioGroup>
    </div>
</template>
