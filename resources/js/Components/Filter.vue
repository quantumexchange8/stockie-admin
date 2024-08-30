<script setup>
import { toRefs, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { FilterIcon, TimesIcon } from "./Icons/solid";
import OverlayPanel from '@/Components/OverlayPanel.vue';

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

const op = ref(null);

const openOverlay = (event) => {
    op.value.show(event);
};

const closeOverlay = () => {
    op.value.hide();
};

const handleClick = (event) => {
    if (disabled.value) {
        event.preventDefault();
        event.stopPropagation();
        return;
    }
    emit("click", event);
    openOverlay(event);
};

const Tag = external ? "a" : Link;
</script>

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

    <!-- Assign Seat -->
    <OverlayPanel ref="op" withArrow bgColor="bg-grey-100">
        <div class="flex flex-col gap-6 max-w-80">
            <div class="flex items-center justify-between">
                <span class="text-primary-950 text-center text-md font-medium">Filter by</span>
                <TimesIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="closeOverlay"
                />
            </div>
            <slot :hideOverlay="closeOverlay"></slot>
        </div>
    </OverlayPanel>
</template>
