<script setup>
import { toRefs, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { FilterIcon, TimesIcon } from "./Icons/solid";
import OverlayPanel from 'primevue/overlaypanel';

const props = defineProps({
    type: {
        type: String,
        default: "submit",
    },
    href: {
        type: String,
    },
    disabled: {
        type: Boolean,
        default: false,
    },

    srText: {
        type: String || undefined,
        default: undefined,
    },
    external: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["click"]);

const { type, href, srText, external } = props;

const { disabled } = toRefs(props);

const op = ref();

const toggle = (event) => {
    op.value.toggle(event);
}

const hideOverlay = () => {
    op.value.hide();
}

const handleClick = (event) => {
    if (disabled.value) {
        event.preventDefault();
        event.stopPropagation();
        return;
    }
    emit("click", event);
    toggle(event);
};

const Tag = external ? "a" : Link;
</script>

<style scoped>
button:hover svg,
button:hover span,
button:active span,
button:focus span {
    color: #9f151a; /* Equivalent to red-800 in Tailwind CSS */
}
button:active svg,
button:active svg {
    color: #7e171b; /* Equivalent to red-800 in Tailwind CSS */
    fill: #7e171b; /* Equivalent to red-800 in Tailwind CSS */
}
</style>

<template>
    <component
        :is="Tag"
        v-if="href"
        :href="!disabled ? href : null"
        :class="classes"
        :aria-disabled="disabled.toString()"
    >
        <span v-if="srText" class="sr-only">
            {{ srText }}
        </span>
    </component>

    <button
        v-else
        :type="type"
        @click="handleClick"
        :disabled="disabled"
        style="width: 103px; height: 44px"
        :class="[
            'w-[103px] max-h-[44px] px-4 py-3 flex justify-between items-center',
            ' text-primary-900 hover:text-red-800 active:text-red-800 focus:text-red-800',
            'bg-white hover:bg-[#FFF9F9] focus:bg-[#FFF1F2] active:bg-white',
            'rounded-tr-[5px] rounded-br-[5px] active:ring-0 border border-primary-900',
            'hover:border-red-95 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
            'active:border-red-800',
            'focus:border-red-800 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
            {
                'border-grey-100': props.disabled === true,
                'border-grey-300': props.disabled === false,
            },
        ]"
    >
        <div class="flex justify-center items-center gap-3">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 20 20"
                fill="none"
            >
                <path
                    d="M2.82125 4.72239C2.19097 4.01796 1.87583 3.66574 1.86394 3.3664C1.85361 3.10636 1.96536 2.85643 2.16603 2.69074C2.39704 2.5 2.86966 2.5 3.81491 2.5H16.184C17.1293 2.5 17.6019 2.5 17.8329 2.69074C18.0336 2.85643 18.1453 3.10636 18.135 3.3664C18.1231 3.66574 17.808 4.01796 17.1777 4.72239L12.4225 10.037C12.2968 10.1774 12.234 10.2477 12.1892 10.3276C12.1495 10.3984 12.1203 10.4747 12.1027 10.554C12.0828 10.6435 12.0828 10.7377 12.0828 10.9261V15.382C12.0828 15.5449 12.0828 15.6264 12.0565 15.6969C12.0333 15.7591 11.9955 15.8149 11.9463 15.8596C11.8907 15.9102 11.815 15.9404 11.6637 16.001L8.83039 17.1343C8.5241 17.2568 8.37096 17.3181 8.24802 17.2925C8.14052 17.2702 8.04617 17.2063 7.98551 17.1148C7.91613 17.0101 7.91613 16.8452 7.91613 16.5153V10.9261C7.91613 10.7377 7.91613 10.6435 7.89623 10.554C7.87858 10.4747 7.84943 10.3984 7.8097 10.3276C7.76491 10.2477 7.70209 10.1774 7.57645 10.037L2.82125 4.72239Z"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            <span class="text-primary-950 focus:text-primary-800">Filter</span>
        </div>
    </button>

    <OverlayPanel 
        ref="op" 
        appendTo="body"
        :pt="{
            root: {
                class: [
                    // Shape
                    'rounded-[5px] shadow-lg',
                    'border-0',

                    // Position
                    'absolute left-0 top-0 mt-2',
                    'z-40 transform origin-center',

                    // Color
                    'bg-white opacity-95',

                    // Before: Triangle
                    'before:absolute before:-top-[9px] before:-ml-[9px] before:left-[calc(var(--overlayArrowLeft,0)+1.25rem)] z-0',
                    'before:w-0 before:h-0 before:shadow-inner',
                    'before:border-transparent before:border-solid',
                    'before:border-x-[8px] before:border-[8px]',
                    'before:border-t-0 before:border-b-grey-300/10',

                    'after:absolute after:-top-2 after:-ml-[8px] after:left-[calc(var(--overlayArrowLeft,0)+1.25rem)]',
                    'after:w-0 after:h-0 after:shadow-inner',
                    'after:border-transparent after:border-solid',
                    'after:border-x-[0.5rem] after:border-[0.5rem]',
                    'after:border-t-0 after:border-b-white after:opacity-80'
                ]
            },
            content: {
                class: 'p-6'
            },
            transition: {
                enterFromClass: 'opacity-0 scale-y-[0.8]',
                enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
                leaveActiveClass: 'transition-opacity duration-100 ease-linear',
                leaveToClass: 'opacity-0'
            }
        }"
    >
        <div class="flex flex-col gap-6 max-w-72">
            <div class="flex items-center justify-between">
                <span class="text-primary-950 text-center text-md font-medium">Filter by</span>
                <TimesIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="hideOverlay"
                />
            </div>
            <slot name="overlayContent"></slot>
        </div>
    </OverlayPanel>
</template>
