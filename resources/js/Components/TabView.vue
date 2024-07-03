<script setup>
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import { defineProps } from "vue";

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        default: () => [],
    },
});
</script>

<template>
    <TabView
        :pt="{
            inkbar: {
                class: 'border-[2px] border-primary-900 absolute bottom-0 left-0 right-0',
            },
            navContent: {
                class: 'w-fit',
            },
        }"
    >
        <TabPanel v-for="(tab, index) in tabs" :key="index" :header="tab">
            <slot :name="'tab-' + index"></slot>
        </TabPanel>
    </TabView>
</template>
