export default {
    root: {
        class: [
            // Shape
            'rounded-md',

            // Spacing
            'p-0',

            // Color
            'bg-grey-0',
            'border-0',

            // Misc
        ]
    },
    menu: {
        class: [
            // Flex & Alignment
            'flex items-center flex-nowrap',

            // Spacing
            'm-0 p-0'
        ]
    },
    action: {
        class: [
            // Flex & Alignment
            'flex items-center',

            // Shape
            'rounded-md',

            // Color
            'text-grey-600',

            // States
            'focus-visible:outline-none focus-visible:outline-offset-0',
            'focus-visible:ring focus-visible:ring-primary-400/50',

            // Transitions
            'transition-shadow duration-200',

            // Misc
            'text-decoration-none'
        ]
    },
    icon: {
        class: 'text-grey-600'
    },
    separator: {
        class: [
            // Flex & Alignment
            'flex items-center',

            // Spacing
            ' mx-2',

            // Color
            'text-grey-600'
        ]
    }
};
