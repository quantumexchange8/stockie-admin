<script setup>
import { toRefs, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { FilterIcon } from "./Icons/solid";

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

// const baseClasses = [
//     "flex w-full items-center justify-center transition-colors font-medium select-none disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none",
// ];

// const variantClasses = (variant) => ({
//     "bg-white hover:bg-[#FFF9F9] hover:text-primary-800 text-primary-900 border border-primary-800":
//         variant == "tertiary",
// });

// const classes = computed(() => [
//     ...baseClasses,
//     iconOnly
//         ? {
//               "px-4 py-3 text-base": true,
//           }
//         : null,

//     variantClasses(variant),
//     {
//         "rounded-tr-md": iconPosition !== "right", // Rounded top-right corner if icon is not on the right
//         "rounded-br-md": iconPosition !== "right", // Rounded bottom-right corner if icon is not on the right
//         "rounded-tl-md": squared, // Always rounded top-left corner
//         "rounded-bl-md": squared, // Always rounded bottom-left corner
//         "rounded-full": pill,
//     },
//     {
//         "pointer-events-none": href && disabled.value,
//     },
//     {
//         "!bg-grey-100 !text-grey-200":
//             disabled.value === true && variant === "primary",
//         "!bg-primary-100": disabled.value === true && variant === "secondary",
//         "!bg-primary-200": disabled.value === true && variant === "tertiary",
//         "!bg-primary-300": disabled.value === true && variant === "red",
//     },
// ]);

const handleClick = (e) => {
    if (disabled.value) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    emit("click", e);
};

const Tag = external ? "a" : Link;
</script>

<style scoped>
.margin-right {
    margin-right: 6px;
}

.margin-left {
    margin-left: 6px;
}

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
</template>
