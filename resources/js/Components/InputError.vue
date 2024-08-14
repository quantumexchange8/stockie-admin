<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div 
        v-show="message"
        class="text-red-500 flex items-center"
    >
        <svg 
            xmlns="http://www.w3.org/2000/svg" 
            width="12" 
            height="13" 
            viewBox="0 0 12 13" 
            fill="none"
            class="mr-1 flex-shrink-0"
        >
            <g clip-path="url(#clip0_16_2484)">
                <path 
                    d="M6 4.5V6.5M6 8.5H6.005M11 6.5C11 9.26142 8.76142 11.5 6 11.5C3.23858 11.5 
                        1 9.26142 1 6.5C1 3.73858 3.23858 1.5 6 1.5C8.76142 1.5 11 3.73858 11 6.5Z" 
                    stroke="currentColor" 
                    stroke-width="1.3" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                />
            </g>
            <defs>
                <clipPath id="clip0_16_2484">
                    <rect 
                        width="12" 
                        height="12" 
                        fill="white" 
                        transform="translate(0 0.5)"
                    />
                </clipPath>
            </defs>
        </svg>
        <p class="text-xs pt-[1px]">
            {{ message }}
        </p>
    </div>
</template>
