<script setup>
import Tree from 'primevue/tree';
import { ref, onMounted } from 'vue';

</script>

<template>
    <Tree
        :pt="{
            root: {
                class: [
                    'flex flex-col items-start self-stretch'
                ]
            },
            wrapper: {
                class: [
                    'flex flex-col items-start self-stretch'
                ]
            },
            node: {
                class: [
                    'flex py-2 pl-3 pr-6 items-center gap-3 self-stretch rounded-[5px] bg-primary-900 shadow-[0_0_38.9px_0_rgba(241,198,198,0.13)] shadow-[0_-1px_10.3px_0_rgba(163,45,50,0.45)]'
                ]
            },
            content: {
                class: [
                    'text-white text-base font-normal'
                ]
            }
        }"
    >

    </Tree>
</template>

