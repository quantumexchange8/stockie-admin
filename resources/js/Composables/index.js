import { reactive, watchEffect } from 'vue'

export const sidebarState = reactive({
    isOpen: window.innerWidth > 1024,
    isHovered: false,
    // handleHover(value) {
    //     if (window.innerWidth < 1440) {
    //         return
    //     }
    //     sidebarState.isHovered = value
    // },
    handleWindowResize() {
        if (window.innerWidth <= 1024) {
            sidebarState.isOpen = false
        } else {
            sidebarState.isOpen = true
        }
    },
})

export const rightSidebarState = reactive({
    isOpen: window.innerWidth > 1440,
    isHovered: false,
    handleWindowResize() {
        if (window.innerWidth <= 1440) {
            rightSidebarState.isOpen = false
        } else {
            rightSidebarState.isOpen = true
        }
    },
})

watchEffect(() => {
  if (rightSidebarState.isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = null
  }
});
