export default {
    content: {
        class: 'p-6'
    },
    transition: {
        enterActiveClass: "transition duration-200 ease-in-out",
        enterFromClass: "translate-y-1 opacity-0",
        enterToClass: "translate-y-0 opacity-100",
        leaveActiveClass: "transition duration-150 ease-in-out",
        leaveFromClass: "translate-y-0 opacity-100",
        leaveToClass: "translate-y-1 opacity-0"
    }
};
