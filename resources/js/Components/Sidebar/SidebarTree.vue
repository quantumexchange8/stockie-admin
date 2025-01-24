<script setup>
import Tree from 'primevue/tree';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    value: {
        type: Array,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    expandedKeys: Object,
})

const emit = defineEmits(['expand']);
const expandedKeys = ref(props.expandedKeys);
const active = ref(props.active)
const url = ref(window.location.href)

const expandNode = (node) => {
    if (node.children && node.children.length) {
        const newExpandedKeys = { ...props.expandedKeys, [node.key]: true };
        emit('update:expandedKeys', newExpandedKeys);

        for (let child of node.children) {
            expandNode(child);
        }
    }
};

const isActive = (node) => {
    return url.value === node.data;
}

watch(() => props.expandedKeys, (newValue) =>{
    expandedKeys.value = newValue;
})
</script>

<template>
    <Tree
        :value="props.value"
        :active="active"
        v-model:expandedKeys="expandedKeys"
        :class="[
            'flex flex-col items-start rounded-[5px] ',
            {
                'text-red-900 hover:text-red-700 hover:bg-[#FFE1E261]':
                    !active,
                'text-white':
                    active,
            },
        ]"
        :pt="{
            root: {
                class: [
                    'flex flex-col items-start self-stretch'
                ]
            },
            wrapper: {
                class: [
                    'flex flex-col items-start self-stretch w-full'
                ]
            },
            container: {
                class: [
                    'w-full'
                ]
            },
            node: ({ props }) => {
                return {
                    class: [
                        'flex flex-col items-start self-stretch rounded-[5px]',
                        {
                            'bg-[#fff1f2]': !active && Object.keys(props.expandedKeys).length !== 0,
                        }
                    ]
                }
            },
            content: ({ props }) => {
                return {
                    class: [
                        'flex py-2 pl-3 pr-6 items-center gap-3 self-stretch rounded-[5px]',
                        {
                            'bg-primary-900 shadow-[0px_0px_38.9px_0px_rgba(241,198,198,0.13),_0px_-1px_10.3px_0px_rgba(163,45,50,0.45)]': active && props.level === 1,
                        }
                    ],
                }
            },
            label: ({ props }) => {
                return {
                    class: [
                        'text-base font-normal whitespace-nowrap',
                        {
                            'text-primary-700': props.level !== 1 && url.includes(props.node.data),
                            'text-primary-950': props.level !== 1 && !(url.includes(props.node.data)),
                        }
                    ]
                }
            },
            subgroup: ({ props }) => {
                return {
                    class: [
                    'flex flex-col items-start self-stretch rounded-[5px] bg-[#fff1f27f]',
                        {
                            'text-primary-700': active && props.level !== 1 && url.includes(props.node.data),
                            'text-primary-950': !active && props.level !== 1 && !(url.includes(props.node.data)),
                            'text-red-900 hover:text-red-700': !active && props.level === 1,
                            'text-white': active && props.level === 1,
                        }
                    ]
                }
            }
        }"
    >
        <template #nodeicon="slotProps">
            <slot name="nodeicon" :="slotProps"></slot>
        </template>
        <template #togglericon="slotProps">
            <slot name="togglericon" :="slotProps"></slot>
        </template>
        <template #url="slotProps">
            <a :href="slotProps.node.data">{{ slotProps.node.label }}</a>
        </template>
        <template #default="slotProps">
            <span @click="emit('expand', slotProps.node)" class="cursor-pointer">{{ slotProps.node.label }}</span>
        </template>
    </Tree>
</template>

