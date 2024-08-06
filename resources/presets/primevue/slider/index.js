export default {
    root: ({ props }) => ({
        class: [
            'relative self-stretch',

            // Size
            { 'h-1.5 w-full': props.orientation == 'horizontal', 'w-1.5 h-full': props.orientation == 'vertical' },

            // Shape
            'border-0 rounded-[5px]',

            // Colors
            'bg-grey-300',

            // States
            { 'opacity-60 select-none pointer-events-none cursor-default': props.disabled }
        ]
    }),
    range: ({ props }) => ({
        class: [
            // Position
            'block absolute',
            {
                'top-0 left-0': props.orientation == 'horizontal',
                'bottom-0 left-0': props.orientation == 'vertical'
            },

            //Size
            'w-full h-full',
            {
                'h-full': props.orientation == 'horizontal',
                'w-full': props.orientation == 'vertical'
            },

            // Colors
            'bg-gradient-to-r from-primary-900 to-primary-950'
        ]
    }),
    handle: ({ props }) => ({
        class: [
            'block',

            // Size
            'size-[1.143rem]',
            {
                'top-[50%] mt-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'horizontal',
                'left-[50%] mb-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'vertical'
            },

            // Shape
            'rounded-full',
            'border-2',

            // Colors
            'bg-grey-100',
            'border-primary',

            // States
            'hover:bg-primary-emphasis',
            'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring',
            'ring-primary-400/50',

            // Transitions
            'transition duration-200',

            // Misc
            'cursor-grab',
            'touch-action-none'
        ]
    }),
    startHandler: ({ props }) => ({
        class: [
            'block',

            // Size
            'size-[1.143rem]',
            {
                'top-[50%] mt-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'horizontal',
                'left-[50%] mb-[-0.5715rem] ml-[-0.4715rem]': props.orientation == 'vertical'
            },

            // Shape
            'rounded-full',
            'border-0',

            // Colors
            'bg-white shadow-md',
            // 'border-primary',

            // States
            // 'hover:bg-primary-emphasis',
            // 'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring',
            // 'focus-visible:ring-primary-400/50',

            // Transitions
            'transition duration-200',

            // Misc
            'cursor-grab',
            'touch-action-none'
        ]
    }),
    endHandler: ({ props }) => ({
        class: [
            'block',

            // Size
            'size-[1.143rem]',
            {
                'top-[50%] mt-[-0.5715rem] ml-[-0.5715rem]': props.orientation == 'horizontal',
                'left-[50%] mb-[-0.5715rem] ml-[-0.4715rem]': props.orientation == 'vertical'
            },

            // Shape
            'rounded-full',
            'border-0',

            // Colors
            'bg-white shadow-md',
            // 'border-primary',

            // States
            // 'hover:bg-primary-emphasis',
            // 'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring',
            // 'focus-visible:ring-primary-400/50',

            // Transitions
            'transition duration-200',

            // Misc
            'cursor-grab',
            'touch-action-none'
        ]
    })
};
