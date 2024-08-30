export default {
    root: ({ context, props }) => ({
        class: [
            // Position and Shadows
            'absolute bg-primary-50 rounded py-1 px-3 shadow-[0px_6px_8.3px_-4px_rgba(16,24,40,0.10)]',
            // Spacing
            {
                'ml-1': context?.right,
                '-ml-1': context?.left,
                '-mt-1': context?.top || (!context?.right && !context?.left && !context?.top && !context?.bottom),
                'mt-1': context?.bottom
            }
        ]
    }),
    arrow: ({ context, props }) => ({
        class: [
            'absolute size-0 border-solid bg-transparent',
            {
                // Right-aligned arrow (triangle pointing left)
                'border-y-[0.25rem] border-r-[0.25rem] border-l-0 border-transparent border-r-primary-50': context?.right || (!context?.right && !context?.left && !context?.top && !context?.bottom),
                // Left-aligned arrow (triangle pointing right)
                'border-y-[0.25rem] border-l-[0.25rem] border-r-0 border-transparent border-l-primary-50': context?.left,
                // Top-aligned arrow (triangle pointing down)
                'border-x-[0.25rem] border-t-[0.25rem] border-b-0 border-transparent border-t-primary-50': context?.top,
                // Bottom-aligned arrow (triangle pointing up)
                'border-x-[0.25rem] border-b-[0.25rem] border-t-0 border-transparent border-b-primary-50': context?.bottom,
            },
            {
                '-mt-1 !-left-1': context?.right,
                '-mt-1 !-right-1': context?.left,
                '-ml-1 !-bottom-1': context?.top || (!context?.right && !context?.left && !context?.top && !context?.bottom),
                '-ml-1 !-top-1': context?.bottom,
            }
        ]
    }),
    text: {
        class: ['text-primary-900 font-normal text-2xs leading-normal rounded whitespace-pre-wrap font-normal']
    }
};
