export default {
    root: ({ props }) => ({
        class: [
        // Display and Position
        'inline-flex',
        'w-full',
        'relative',
        // Misc
        { 'select-none pointer-events-none cursor-default': props.disabled }
        ]
    }),
    inputicon: ({ state }) => ({
        class: [
            'absolute top-1/2 -mt-2 w-4 h-4 fill-primary-50 right-4',
            {
                'text-primary-200': state.focused,
                'text-primary-900': !state.focused,
            }
        ]
    }),
    dropdownbutton: {
        root: {
            class: ['relative', 'items-center inline-flex text-center align-bottom', 'rounded-r-md', 'px-4 py-3 leading-none', 'text-primary-inverse', 'bg-primary', 'border border-primary', 'focus:outline-none focus:outline-offset-0 focus:ring', 'hover:bg-primary-hover hover:border-primary-hover', 'focus:ring-primary-400/50']
        }
    },
    datepickerMask: {
        class: ['fixed top-0 left-0 w-full h-full', 'flex items-center justify-center', 'bg-black bg-opacity-90']
    },
    header: {
        class: ['font-semibold', 'flex items-center justify-between', 'pb-1', 'm-0', 'border-b', 'rounded-t-md', 'bg-white']
    },
    previousbutton: {
        class: ['relative', 'inline-flex items-center justify-center', 'w-8 h-8', 'p-0 m-0', 'rounded-full', 'border-0', 'bg-transparent', 'transition-colors duration-200 ease-in-out', 'cursor-pointer overflow-hidden']
    },
    title: {
        class: ['leading-8', 'mx-auto my-0']
    },
    monthTitle: {
        class: ['text-sm leading-normal', 'font-medium', 'text-grey-900', 'transition duration-200', 'p-2', 'm-0 mr-2', 'hover:text-grey-500', 'cursor-pointer']
    },
    yearTitle: {
        class: ['text-sm leading-normal', 'font-medium', 'text-grey-900', 'transition duration-200', 'p-2', 'm-0', 'hover:text-grey-500', 'cursor-pointer']
    },
    decadeTitle: {
        class: ['text-sm leading-normal', 'font-medium', 'text-grey-900', 'transition duration-200', 'p-2', 'm-0', 'hover:text-grey-500', 'cursor-pointer']
    },
    nextbutton: {
        class: ['relative', 'inline-flex items-center justify-center', 'w-8 h-8', 'p-0 m-0', 'rounded-full', 'border-0', 'bg-transparent', 'transition-colors duration-200 ease-in-out', 'cursor-pointer overflow-hidden']
    },
    table: {
        class: ['text-base leading-none', 'border-collapse', 'w-full', 'm-0 my-2']
    },
    tableheadercell: {
        class: ['p-0 text-sm text-grey-400 font-normal']
    },
    weekheader: {
        class: ['leading-normal', 'opacity-60 cursor-default']
    },
    weeknumber: {
        class: ['opacity-60 cursor-default']
    },
    weekday: {
        class: []
    },
    day: {
        class: ['p-1']
    },
    weeklabelcontainer: ({ context }) => ({
        class: [
        // Flexbox and Alignment
        'flex items-center justify-center',
        'mx-auto',
        // Shape & Size
        'w-10 h-10',
        'rounded-full',
        'border-transparent border',
        // Colors
        {
            'bg-transparent': !context.selected && !context.disabled,
            'text-primary-highlight-inverse bg-primary-highlight': context.selected && !context.disabled
        },
        // States
        'focus:outline-none focus:outline-offset-0 focus:ring focus:ring-primary-400/50',
        {
            '': !context.selected && !context.disabled,
            'hover:bg-primary-highlight-hover': context.selected && !context.disabled
        },
        {
            'opacity-60 cursor-default': context.disabled,
            'cursor-pointer': !context.disabled
        }
        ]
    }),
    monthpicker: {
        class: ['my-2']
    },
    month: ({ context }) => ({
        class: [
        // Flexbox and Alignment
        'inline-flex items-center justify-center',
        // Size
        'w-1/3',
        'p-2',
        'text-sm',
        // Shape
        'rounded-md',
        // Colors
        {
            'text-grey-900 bg-transparent': !context.selected && !context.disabled,
            'text-primary-900 bg-primary-50': context.selected && !context.disabled
        },
        // States
        {
            'hover:bg-primary-50': !context.selected && !context.disabled,
        },
        // Misc
        'cursor-pointer'
        ]
    }),
    yearpicker: {
        class: ['my-2']
    },
    year: ({ context }) => ({
        class: [
        // Flexbox and Alignment
        'inline-flex items-center justify-center',
        // Size
        'w-1/3',
        'p-2',
        'text-sm',
        // Shape
        'rounded-md',
        // Colors
        {
            'text-grey-900 bg-transparent': !context.selected && !context.disabled,
            'text-primary-900 bg-primary-50': context.selected && !context.disabled
        },
        // States
        {
            'hover:bg-primary-50': !context.selected && !context.disabled,
        },
        // Misc
        'cursor-pointer'
        ]
    }),
    timepicker: {
        class: ['flex', 'justify-center items-center', 'border-t-[0.5px] border-grey-200', 'text-sm font-normal text-grey-900', 'pt-2']
    },
    separatorcontainer: {
        class: ['flex', 'items-center', 'flex-col', 'px-2']
    },
    separator: {
        class: ['text-base pb-1']
    },
    hourpicker: {
        class: ['flex', 'items-center', 'flex-col', 'px-2']
    },
    minutepicker: {
        class: ['flex', 'items-center', 'flex-col', 'px-2']
    },
    secondPicker: {
        class: ['flex', 'items-center', 'flex-col', 'px-2']
    },
    ampmpicker: {
        class: ['flex', 'items-center', 'flex-col', 'px-2']
    },
    incrementbutton: {
        class: ['relative', 'inline-flex items-center justify-center', 'w-8 h-8', 'p-0 m-0', 'rounded-full', 'border-0', 'bg-transparent', 'transition-colors duration-200 ease-in-out', 'cursor-pointer overflow-hidden']
    },
    decrementbutton: {
        class: ['relative', 'inline-flex items-center justify-center', 'w-8 h-8', 'p-0 m-0', 'rounded-full', 'border-0', 'bg-transparent', 'transition-colors duration-200 ease-in-out', 'cursor-pointer overflow-hidden']
    },
    groupcontainer: {
        class: ['flex']
    },
    group: {
        class: ['flex-1', 'pr-0.5', 'pl-0.5', 'pt-0', 'pb-0', 'first:pl-0']
    },
    buttonbar: {
        class: ['flex justify-between items-center', 'py-3 px-0', 'border-t']
    },
    todaybutton: {
        root: {
            class: ['inline-flex items-center justify-center', 'px-4 py-3 leading-none', 'rounded-md', 'bg-transparent border-transparent', 'text-primary', 'transition-colors duration-200 ease-in-out', 'focus:outline-none focus:outline-offset-0 focus:ring', 'hover:bg-primary-300/20', 'cursor-pointer']
        }
    },
    clearbutton: {
        root: {
            class: ['inline-flex items-center justify-center', 'px-4 py-3 leading-none', 'rounded-md', 'bg-transparent border-transparent', 'text-primary', 'transition-colors duration-200 ease-in-out', 'focus:outline-none focus:outline-offset-0 focus:ring', 'hover:bg-primary-300/20', 'cursor-pointer']
        }
    },
    transition: {
        enterFromClass: 'opacity-0 -scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};
