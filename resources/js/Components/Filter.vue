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
const arrowPosition = ref('top');

const toggle = (event) => {
    calculateArrowPosition(event);
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

const calculateArrowPosition = (event) => {
    const buttonRect = event.target.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    if (buttonRect.top > windowHeight / 2) {
        arrowPosition.value = 'bottom';
    } else {
        arrowPosition.value = 'top';
    }
};

const Tag = external ? "a" : Link;
</script>

<style scoped>
/* button:hover svg,
button:hover span,
button:active span,
button:focus span {
    color: #9f151a;
}
button:active svg,
button:active svg {
    color: #7e171b;
    fill: #7e171b;
} */
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
            ' text-primary-900 hover:!text-primary-800 active:text-primary-800 focus:text-primary-800',
            'bg-white hover:bg-[#FFF9F9] focus:bg-[#FFF1F2] active:bg-white',
            'rounded-tr-[5px] rounded-br-[5px] active:ring-0 border border-primary-900',
            'hover:border-primary-100 hover:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)]',
            'active:border-primary-800',
            'focus:border-primary-800 focus:shadow-[0px_0px_6.4px_0px_rgba(255,96,102,0.49)] focus:ring-0',
            '[&>div>svg]:text-primary-900 [&>div>svg]:hover:text-primary-800 [&>div>svg]:hover:!fill-primary-25',
            '[&>div>svg]:active:fill-primary-900 [&>div>svg]:focus:fill-primary-900',
            {
                'border-grey-100': props.disabled === true,
                'border-grey-300': props.disabled === false,
            },
        ]"
    >
        <div class="flex justify-center items-center gap-3">
            <FilterIcon class="w-5 h-5"/>
            <span>Filter</span>
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
                    // 'bg-grey-200',

                    // Dynamic Positioning
                    arrowPosition === 'top' 
                        ? 'top-0 before:-top-[8px] after:-top-2'
                        : 'before:-bottom-[8px] after:-bottom-2',

                    // Before: Triangle
                    'before:absolute before:-ml-[9px] before:left-[calc(var(--overlayArrowLeft,0)+1.25rem)] z-0',
                    'before:w-0 before:h-0 before:shadow-inner',
                    'before:border-transparent before:border-solid',
                    'before:border-x-[8px] before:border-[8px]',
                    arrowPosition === 'top'
                        ? 'before:border-t-0 before:border-b-white'
                        : 'before:border-b-0 before:border-t-white',

                    'after:absolute after:-ml-[8px] after:left-[calc(var(--overlayArrowLeft,0)+1.25rem)]',
                    'after:w-0 after:h-0 after:shadow-inner',
                    'after:border-transparent after:border-solid',
                    'after:border-x-[0.5rem] after:border-[0.5rem]',
                    arrowPosition === 'top'
                        ? 'after:border-t-0 after:border-b-white after:opacity-95'
                        : 'after:border-b-0 after:border-t-white after:opacity-95'
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
        <div class="flex flex-col gap-6 max-w-80">
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
