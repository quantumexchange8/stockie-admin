export default {
    navContainer: ({ props }) => ({
        class: [
            // Position
            "relative",

            // Misc
            { "overflow-hidden": props.scrollable },
        ],
    }),
    navContent: {
        class: [
            // Overflow and Scrolling
            "overflow-y-hidden overscroll-contain",
            "overscroll-auto",
            "scroll-smooth",
            "[&::-webkit-scrollbar]:hidden",
        ],
    },
    previousButton: {
        class: [
            // Flexbox and Alignment
            "flex items-center justify-center",

            // Position
            "!absolute",
            "top-0 left-0",
            "z-20",

            // Size and Shape
            "h-full w-12",
            "rounded-none",

            // Colors
            "bg-grey-0 dark:bg-grey-800",
            "text-primary",
            "shadow-md",
        ],
    },
    nextButton: {
        class: [
            // Flexbox and Alignment
            "flex items-center justify-center",

            // Position
            "!absolute",
            "top-0 right-0",
            "z-20",

            // Size and Shape
            "h-full w-12",
            "rounded-none",

            // Colors
            "bg-grey-0 dark:bg-grey-800",
            "text-primary",
            "shadow-md",
        ],
    },
    nav: {
        class: [
            // Flexbox
            "flex flex-1",

            // Spacing
            "list-none",
            "p-0 m-0",

            // Colors
            "bg-grey-0 dark:bg-grey-800",
            "border-b-1 border-grey-200 dark:border-grey-700",
            "text-grey-900 dark:text-grey-0/80",
        ],
    },
    tabpanel: {
        header: ({ props }) => ({
            class: [
                // Spacing
                "mr-0",

                // Misc
                {
                    "opacity-60 cursor-default user-select-none select-none pointer-events-none":
                        props?.disabled,
                },
            ],
        }),
        headerAction: ({ parent, context }) => ({
            class: [
                "relative",

                // Font
                "font-medium",

                // Flexbox and Alignment
                "flex items-center",

                // Spacing
                "p-3",
                "-mb-[2px]",

                // Shape
                "border-b-2",
                "rounded-t-md",

                // Colors and Conditions
                {
                    "border-grey-200 dark:border-grey-700":
                        parent.state.d_activeIndex !== context.index,
                    "bg-grey-0 dark:bg-grey-800":
                        parent.state.d_activeIndex !== context.index,
                    "text-grey-200":
                        parent.state.d_activeIndex !== context.index,

                    "bg-grey-0 dark:bg-grey-800":
                        parent.state.d_activeIndex === context.index,
                    "border-primary":
                        parent.state.d_activeIndex === context.index,
                    "text-primary-900":
                        parent.state.d_activeIndex === context.index,
                },

                // States
                "focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring focus-visible:ring-inset",
                "focus-visible:ring-primary-400/50 dark:focus-visible:ring-primary-300/50",
                {
                    "hover:bg-grey-0 hover:border-grey-400 hover:text-primary-900 ":
                        parent.state.d_activeIndex !== context.index,
                },

                // Transitions
                "transition-all duration-200",

                // Misc
                "cursor-pointer select-none text-decoration-none",
                "overflow-hidden",
                "user-select-none",
            ],
        }),
        headerTitle: {
            class: [
                // Text
                "text-[14px] leading-none",
                "whitespace-nowrap",
            ],
        },
        content: {
            class: [
                // Spacing
                "py-5",

                // Shape
                "rounded-b-md",

                // Colors
                "bg-grey-0 dark:bg-grey-800",
                "text-grey-700 dark:text-grey-0/80",
                "border-0",
            ],
        },
    },
};
