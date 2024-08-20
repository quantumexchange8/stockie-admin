<script setup>
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { computed } from 'vue';

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const tranformedTabs = computed(() => {
    return props.tabs.map((tab) => tab.toLowerCase().replace(/ /g,"-"))
});
</script>

<template>
    <TabGroup>
        <TabList class="flex border-b border-gray-200">
            <slot name="startheader"></slot> 
            <template v-for="(tab, index) in tabs" :key="index">
                <Tab as="template" v-slot="{ selected }">
                    <button
                        :class="[
                            'p-3 text-sm font-medium leading-none',
                            'focus:outline-none',
                            selected
                                ? 'text-primary-900 border-b-2 border-primary-900'
                                : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                        ]"
                    >
                        {{ tab }}
                    </button>
                </Tab>
            </template>
            <slot name="endheader"></slot>
        </TabList>

        <TabPanels class="mt-2">
            <!-- The panel templates will have the same name as the tab but lower case and spaces are replaced with hyphens (-) -->
            <template v-for="(tab, index) in tranformedTabs" :key="index">
                <TabPanel
                    :class="[
                        'rounded-xl bg-white p-3',
                        'focus:outline-none',
                    ]"
                >
                    <slot :name="tab"></slot>
                </TabPanel>
            </template>
        </TabPanels>
    </TabGroup>
</template>
