export default {
    root: {
        class: [
            // Shape
            'rounded-[5px] shadow-lg',
            'border-0',

            // Position
            'absolute left-0 top-0 mt-2',
            'z-40 transform origin-center',

            // Color
            'bg-[rgba(255,255,255,0.80)]',

            // Before: Triangle
            'before:absolute before:-top-[9px] before:-ml-[9px] before:left-[calc(var(--overlayArrowRight,0)+1.25rem)] z-0',
            'before:w-0 before:h-0',
            'before:border-transparent before:border-solid',
            'before:border-x-[8px] before:border-[8px]',
            'before:border-t-0 before:border-b-grey-300/10',

            'after:absolute after:-top-2 after:-ml-[8px] after:left-[calc(var(--overlayArrowRight,0)+1.25rem)]',
            'after:w-0 after:h-0',
            'after:border-transparent after:border-solid',
            'after:border-x-[0.5rem] after:border-[0.5rem]',
            'after:border-t-0 after:border-b-grey-0'
        ]
    },
    content: {
        class: 'p-5 items-center flex'
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};
