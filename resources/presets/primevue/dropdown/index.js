export default {
    root: ({ props, state, parent }) => ({
        class: [
        // Display and Position
        'inline-flex',
        'relative w-full',
        // Shape
        'max-h-[44px]',
        { 'rounded-md': parent.instance.$name !== 'InputGroup' },
        { 'first:rounded-l-md rounded-none last:rounded-r-md': parent.instance.$name == 'InputGroup' },
        { 'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup' },
        { 'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons },
        // Color and Background
        'bg-white',
        'border border-grey-300',
        { 'border-grey-300': !props.invalid },
        // Invalid State
        { 'border-red-500': props.invalid },
        // Transitions
        'transition-all',
        'duration-200',
        // States
        { 'hover:border-primary': !props.invalid },
        { 'outline-none outline-offset-0 ring ring-primary-400/50': state.focused },
        // Misc
        'cursor-pointer',
        'select-none',
        { 'opacity-60': props.disabled, 'pointer-events-none': props.disabled, 'cursor-default': props.disabled }
        ]
    }),
    input: ({ props, parent }) => {
        var _a;
        return {
        class: [
            //Font
            'text-base font-normal',
            // Display
            'block',
            'flex items-center',
            // Color and Background
            'bg-transparent',
            'border-0',
            { 'text-grey-800 ': props.modelValue != null, 'text-grey-400': props.modelValue == null },
            'placeholder:text-grey-200 placeholder:text-base placeholder:font-normal',
            // Sizing and Spacing
            'w-full',
            'px-4 py-3',
            { 'pr-7': props.showClear },
            //Shape
            'rounded-none',
            // Transitions
            'transition',
            'duration-200',
            // States
            'focus:outline-none focus:shadow-none',
            // Filled State *for FloatLabel
            { filled: ((_a = parent.instance) == null ? void 0 : _a.$name) == 'FloatLabel' && props.modelValue !== null },
            // Misc
            'relative',
            'cursor-pointer',
            'overflow-hidden overflow-ellipsis',
            'whitespace-nowrap',
            'appearance-none'
        ]
        };
    },
    trigger: {
        class: ['flex items-center justify-center', 'shrink-0', 'bg-transparent', 'text-grey-500', 'w-10', 'rounded-tr-md', 'rounded-br-md']
    },
    panel: {
        class: [
            'absolute top-0 left-0 !z-[1103]', 
            '!mt-1 p-1', 
            'border-2 border-red-50', 
            'rounded-[5px]', 
            'shadow-[0px_15px_23.6px_0px_rgba(102,30,30,0.05)]', 
            'bg-white', 
            'text-grey-800'
        ]
    },
    wrapper: {
        class: ['max-h-[200px]', 'overflow-auto']
    },
    list: {
        class: 'list-none m-0'
    },
    item: ({ context }) => ({
        class: [
        // Font
        'font-normal',
        'leading-none',
        // Position
        'relative',
        // Shape
        'border-0',
        'rounded-none',
        // Spacing
        'm-0',
        'py-3 px-5',
        // Colors
        {
            'text-grey-700': !context.focused && !context.selected,
            'bg-grey-50': context.focused && !context.selected,
            'text-grey-800': context.focused && !context.selected,
            'text-primary-900': context.selected,
            'bg-primary-50': context.selected
        },
        //States
        { 'hover:bg-grey-100': !context.focused && !context.selected },
        { 'hover:bg-primary-highlight-hover': context.selected },
        'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring focus-visible:ring-inset focus-visible:ring-primary-400/50',
        // Transitions
        'transition-shadow',
        'duration-200',
        // Misc
        { 'pointer-events-none cursor-default': context.disabled },
        { 'cursor-pointer': !context.disabled },
        'overflow-hidden',
        'whitespace-nowrap'
        ]
    }),
    itemgroup: {
        class: ['font-bold m-0 py-3 px-5 text-grey-400 bg-grey-25 cursor-auto pointer-events-none']
    },
    itemlabel: {
        class: ['font-normal text-base']
    },
    emptymessage: {
        class: ['leading-none', 'py-3 px-5', 'text-grey-800', 'bg-transparent']
    },
    header: {
        class: ['py-3 px-5', 'm-0', 'border-b', 'rounded-tl-md', 'rounded-tr-md', 'text-grey-700', 'bg-grey-100', 'border-grey-300']
    },
    filtercontainer: {
        class: 'relative'
    },
    filterinput: {
        class: ['leading-[normal]', 'pr-7 py-3 px-3', '-mr-7', 'w-full', 'text-grey-700', 'appearance-none']
    },
    filtericon: {
        class: ['absolute', 'top-1/2 right-3', '-mt-2']
    },
    clearicon: {
        class: ['text-grey-500', 'absolute', 'top-1/2', 'right-12', '-mt-2']
    },
    loadingicon: {
        class: 'text-grey-400 animate-spin'
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
}