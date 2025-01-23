<script setup>
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { computed, ref, watch } from 'vue';

const props = defineProps({
    tabs: {
        type: Array,
        default: () => [],
    },
    selectedTab: {
        type: Number,
        default: 0
    },
    withDisabled: {
        type: Boolean,
        default: false
    },
    tabFooter: {
        type: [Object, Array],
        default: () => {}
    },
});

const emit = defineEmits(['onChange']);

const selectedTab = ref(props.selectedTab);

const changeTab = (index) => {
    selectedTab.value = index;
    emit('onChange', index);
};

watch(() => props.selectedTab, (newValue) => selectedTab.value = newValue);

// const formatTabTitle = (tab) => {
//     return tab.replace(/[/_]+/g, " ").replace(/^-+|-+$/g, " "); // Replace spaces, '/', and '_' with '-' | Remove leading or trailing '-'
// };

// Replace spaces, '/', and '_' with '-' 
// Remove leading or trailing '-'
const tranformedTabs = computed(() => {
    return props.tabs.map((tab) => 
        props.withDisabled 
            ?   tab.title.toLowerCase()
                    .replace(/[/\s_]+/g, "-") // Replace spaces and '/' with '-'
                    .replace(/[^a-z0-9-]+/g, "") // Remove any characters other than alphanumeric and '-'
                    .replace(/^-+|-+$/g, "") // Remove leading or trailing '-'
            :   tab.toLowerCase()
                    .replace(/[/\s_]+/g, "-") // Replace spaces and '/' with '-'
                    .replace(/[^a-z0-9-]+/g, "") // Remove any characters other than alphanumeric and '-'
                    .replace(/^-+|-+$/g, "") // Remove leading or trailing '-'
    )
});
</script>

<template>
    <TabGroup :selectedIndex="selectedTab" @change="changeTab">
        <TabList class="flex overflow-auto min-h-max scrollbar-webkit scrollbar-thin">
            <slot name="startheader"></slot> 
            <template v-for="(tab, index) in tabs" :key="index">
                <Tab 
                    as="template" 
                    v-slot="{ selected }" 
                    class="border-b border-grey-200"
                    :disabled="withDisabled ? tab.disabled : false"
                >
                    <button
                        :class="[
                            'p-3 text-sm font-medium leading-none focus:outline-none whitespace-nowrap group',
                            { 'text-grey-200': !selected },
                            { 'text-primary-900 border-b-2 border-primary-900': selected },
                            { 'hover:bg-white/[0.12] hover:text-primary-800': withDisabled ? !tab.disabled : true },
                            { 'flex gap-2.5 justify-center items-center': props.tabFooter }
                        ]"
                    >
                        {{ withDisabled ? tab.title : tab }}
                        <slot name="tabFooter" v-if="props.tabFooter === tab"></slot>
                        <slot name="count" :tabs="tab" :selected="selected"></slot>
                    </button>
                </Tab>
            </template>
            <slot name="endheader"></slot>
        </TabList>

        <TabPanels class="mt-2 !w-full">
            <!-- The panel templates will have the same name as the tab but lower case and spaces are replaced with hyphens (-) -->
            <template v-for="(tab, index) in tranformedTabs" :key="index">
                <TabPanel
                    :class="[
                        'rounded-xl',
                        'focus:outline-none',
                    ]"
                >
                    <slot :name="tab"></slot>
                </TabPanel>
            </template>
        </TabPanels>
    </TabGroup>
</template>
