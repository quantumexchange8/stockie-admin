<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import Breadcrumb from 'primevue/breadcrumb';

const props = defineProps({
    home: {
        type: Object,
        default: () => ({
            label: 'Dashboard',
            route: '/dashboard'
        })
    },
    items: {
        type: Array,
        default: () => []
    }
});

const home = computed(() => props.home);
const items = computed(() => props.items);
</script>

<template>
    <div class="card flex justify-content-center">
        <Breadcrumb 
            :home="home" 
            :model="items"
        >
            <template #item="{ item }">
                <Link v-if="item.route" :href="item.route">
                    <span class="font-medium text-lg text-grey-200 hover:text-primary-900">
                        {{ item.label }}
                    </span>
                </Link>
                <span v-else class="font-medium text-lg text-primary-900">{{ item.label }}</span>
            </template>

            <template #separator>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-grey-200">
                    <path d="M7.08301 15.8334L12.9163 10L7.08301 4.16669" stroke="currentColor" stroke-width="3" stroke-linecap="square"/>
                </svg>
            </template>
        </Breadcrumb>
    </div>
</template>