export default {
    root: ({ props }) => ({
        class: [
        'relative flex gap-4 flex-col',
        // Flex & Alignment
        { 'flex flex-col': props.scrollable && props.scrollHeight === 'flex' },
        // Size
        { 'h-full': props.scrollable && props.scrollHeight === 'flex' }
        ]
    }),
    loadingoverlay: {
        class: ['absolute', 'top-0 left-0', 'z-20', 'flex items-center justify-center', 'w-full h-full', 'bg-primary-100/40', 'transition duration-200']
    },
    loadingicon: {
        class: 'w-8 h-8 animate-spin'
    },
    wrapper: ({ props }) => ({
        class: [
        { relative: props.scrollable, 'flex flex-col grow': props.scrollable && props.scrollHeight === 'flex' },
        // Size
        { 'h-full': props.scrollable && props.scrollHeight === 'flex' }
        ]
    }),
    headerrow: {
        class: 'w-full flex',
    },
    table: {
        class: 'w-full gap-3 flex flex-col items-start justify-center'
    },
    thead: ({ context }) => ({
        class: [
            'w-full self-stretch rounded-[5px]',
        {
            'bg-primary-50 top-0 z-40 sticky': context.scrollable
        }
        ]
    }),
    tbody: ({ instance, context }) => ({
        class: [
            'w-full self-stretch gap-1 flex flex-col',
            {
                'sticky z-20': instance.frozenRow && context.scrollable
            },
        ]
    }),
    emptymessage: {
        class: 'w-full'
    },
    emptymessagecell: {
        class: 'w-full overflow-hidden flex flex-col items-center justify-center gap-5 flex-[1_0_0] self-stretch'
    },
    tfoot: ({ context }) => ({
        class: [
        {
            'bg-primary-50 bottom-0 z-0': context.scrollable
        }
        ]
    }),
    footer: {
        class: ['font-bold', 'border-t-0 border-b border-x-0', 'p-4', 'bg-primary-50', 'border-primary-200', 'text-primary-700']
    },
    column: {
        root: {
            class: '!bg-primary-900'
        },
        headercell: ({ context, props }) => ({
            class: [
                'font-bold',
                // Position
                { 'sticky z-20 border-b': props.frozen || props.frozen === '' },
                { relative: context.resizable },
                // Alignment
                'text-left',
                // Shape
                'first:rounded-l-[5px] last:rounded-r-[5px]',
                // Spacing
                (context == null ? void 0 : context.size) === 'small' ? 'p-2' : (context == null ? void 0 : context.size) === 'large' ? 'p-5' : 'py-2 px-3',
                'w-full',
                // Color
                (props.sortable === '' || props.sortable) && context.sorted ? ' text-primary-highlight-inverse' : 'text-primary-700',
                'bg-primary-50 border-primary-200 ',
                // States
                { 'hover:bg-primary-100': (props.sortable === '' || props.sortable) && !(context != null && context.sorted) },
                'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring focus-visible:ring-inset focus-visible:ring-primary-400/50',
                // Transition
                { 'transition duration-200': props.sortable === '' || props.sortable },
                // Misc
                { 'cursor-pointer': props.sortable === '' || props.sortable },
                {
                'overflow-hidden space-nowrap border-y bg-clip-padding': context.resizable
                // Resizable
                }
            ]
        }),
        headercontent: {
            class: 'flex items-center justify-between'
        },
        // sort: ({ context }) => ({
        //     class: [context.sorted ? 'text-primary-500' : 'text-primary-700']
        // }),
        bodycell: ({ props, context, state, parent }) => {
            return {
                class: [
                    //Position
                    { 'sticky box-border border-b': parent.instance.frozenRow },
                    { 'sticky box-border border-b z-20': props.frozen || props.frozen === '' },
                    // Alignment
                    'text-left flex items-center text-sm',
                    {
                        'justify-end': props.field === "action"
                    },
                    // Shape
                    { 'first:border-l border-r border-b': context == null ? void 0 : context.showGridlines },
                    { 'bg-primary-0': parent.instance.frozenRow || props.frozen || props.frozen === '' },
                    // Spacing
                    'w-full',
                    { 'p-2': (context == null ? void 0 : context.size) === 'small' && !state.d_editing },
                    { 'p-5': (context == null ? void 0 : context.size) === 'large' && !state.d_editing },
                    { 'p-3': (context == null ? void 0 : context.size) !== 'large' && (context == null ? void 0 : context.size) !== 'small' && !state.d_editing },
                    { 'py-[0.6rem] px-2': state.d_editing },
                    // Color
                    'border-primary-200'
                ]
            }
        },
        footercell: ({ context }) => ({
            class: [
                // Font
                'font-bold',
                // Alignment
                'text-left',
                // Shape
                'border-0 border-b border-solid',
                { 'border-x border-y': context == null ? void 0 : context.showGridlines },
                // Spacing
                (context == null ? void 0 : context.size) === 'small' ? 'p-2' : (context == null ? void 0 : context.size) === 'large' ? 'p-5' : 'p-4',
                // Color
                'border-primary-200',
                'text-primary-700',
                'bg-primary-50'
            ]
        }),
        headertitle: {
            class: ['text-primary-900 hover-primary-800']
        },
        sort: ({ context }) => ({
            class: [context.sorted ? 'text-primary-800 focus:scale-75' : 'text-primary-900', 'hover-primary-800']
        }),
        sortbadge: {
            class: ['flex items-center justify-center align-middle', 'rounded-full', 'w-[1.143rem] leading-[1.143rem]', 'ml-2', 'text-primary-highlight-inverse', 'bg-primary-highlight']
        },
        rowtoggler: {
            class: ['relative', 'inline-flex items-center justify-center', 'text-left', 'm-0 p-0', 'w-8 h-8', 'border-0 rounded-full', 'text-primary-500', 'bg-transparent', 'focus-visible:outline-none focus-visible:outline-offset-0', 'focus-visible:ring focus-visible:ring-primary-400/50', 'transition duration-200', 'overflow-hidden', 'cursor-pointer select-none']
        },
        columnresizer: {
            class: ['block', 'absolute top-0 right-0', 'w-2 h-full', 'm-0 p-0', 'border border-transparent', 'cursor-col-resize']
        },
        rowreordericon: {
            class: 'cursor-move'
        },
        transition: {
            enterFromClass: 'opacity-0 scale-y-[0.8]',
            enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
            leaveActiveClass: 'transition-opacity duration-100 ease-linear',
            leaveToClass: 'opacity-0'
        },
    },
    bodyrow: ({ context, props }) => ({
        class: [
            // Color
            'w-full flex',
            'odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50',
            { 'font-bold bg-primary-0 z-20': props.frozenRow },
        ]
    }),
    rowexpansion: {
        class: 'bg-primary-0 text-primary-600'
    },
    rowgroupheader: {
        class: ['sticky', 'odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50 w-full']
    },
    rowgroupheadercell: {
        class: ['w-full flex items-center pr-3'],
    },
    rowgroupfooter: {
        class: ['sticky z-20']
    },
    rowgroupfootercell: {
        class: ['flex w-full items-center justify-end']
    },
    rowgrouptoggler: {
        class: ['relative', 'inline-flex items-center justify-center', 'text-left', 'm-0 p-0', 'w-8 h-8', 'border-0 rounded-full', 'text-primary-500', 'bg-transparent', 'focus-visible:outline-none focus-visible:outline-offset-0', 'focus-visible:ring focus-visible:ring-primary-400/50', 'transition duration-200', 'overflow-hidden', 'cursor-pointer select-none']
    },
    rowgrouptogglericon: {
        class: 'inline-block w-4 h-4'
    },
    resizehelper: {
        class: 'absolute hidden w-[2px] z-20 bg-primary'
    },
    paginatorwrapper: {
        class: 'p-paginator-bottom'
    },
};
