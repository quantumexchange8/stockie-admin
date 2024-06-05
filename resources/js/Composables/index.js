import { reactive, watchEffect } from 'vue'

export const sidebarState = reactive({
    isOpen: window.innerWidth > 1440,
    isHovered: false,
    // handleHover(value) {
    //     if (window.innerWidth < 1440) {
    //         return
    //     }
    //     sidebarState.isHovered = value
    // },
    handleWindowResize() {
        if (window.innerWidth <= 1440) {
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
    // document.body.style.background = 'linear-gradient(0deg, rgba(0, 0, 0, 0.20) 0%, rgba(0, 0, 0, 0.20) 100%), lightgray';
    // document.body.style.backgroundSize = 'cover';
  } else {
    document.body.style.overflow = null
    // document.body.style.background = '';
    // document.body.style.backgroundSize = '';
    // document.body.style.backgroundRepeat = '';
  }
});
