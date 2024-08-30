import { reactive, watchEffect, watch } from 'vue'
import { useToast } from 'primevue/usetoast';
import { usePage } from '@inertiajs/vue3';

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

export function transactionFormat() {
    function formatDateTime(date, includeTime = true) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const formattedDate = new Date(date);

        const day = formattedDate.getDate().toString().padStart(2, '0');
        const month = months[formattedDate.getMonth()];
        const year = formattedDate.getFullYear();
        const hours = formattedDate.getHours().toString().padStart(2, '0');
        const minutes = formattedDate.getMinutes().toString().padStart(2, '0');
        const seconds = formattedDate.getSeconds().toString().padStart(2, '0');

        if (includeTime) {
            return `${day} ${month} ${year} ${hours}:${minutes}:${seconds}`;
        } else {
            return `${day} ${month} ${year}`;
        }
    }

    function getChannelName(name) {
        if (name === 'bank') {
            return 'Bank Transfer';
        } else if (name === 'crypto') {
            return 'Cryptocurrency';
        }else if (name === 'fpx') {
            return 'FPX';
        }
    }

    function formatDate(date) {
        const formattedDate = new Date(date).toLocaleDateString('en-CA', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: 'Asia/Kuala_Lumpur'
        });
        return formattedDate.split('-').join('/');
    }

    function formatTime(date) {
        const options = {
            hour12: false, // Disable AM/PM indicator
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: 'Asia/Kuala_Lumpur'
        };

        return new Date(date).toLocaleTimeString('en-CA', options);
    }

    function getStatusClass(status) {
        if (status === 'Successful') {
            return 'success';
        } else if (status === 'Submitted') {
            return 'warning';
        } else if (status === 'Processing') {
            return 'primary';
        } else if (status === 'Rejected') {
            return 'danger';
        } else {
            return ''; // Default case or handle other statuses
        }
    }

    function formatAmount(amount, decimalPlaces = 2) {
        const formattedAmount = parseFloat(amount).toFixed(decimalPlaces);
        const integerPart = formattedAmount.split('.')[0];
        const decimalPart = formattedAmount.split('.')[1];
        const integerWithCommas = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return decimalPlaces > 0 ? `${integerWithCommas}.${decimalPart}` : integerWithCommas;
    }

    const formatType = (type) => {
        const formattedType = type.replace(/([a-z])([A-Z])/g, '$1 $2');
        return formattedType.charAt(0).toUpperCase() + formattedType.slice(1);
    };

    return {
        formatDateTime,
        getChannelName,
        formatDate,
        getStatusClass,
        formatAmount,
        formatType,
        formatTime
    };
}

export function useCustomToast() {
    const toast = useToast();
    const { props } = usePage();

    const flashMessage = (options = {}) => {
        const message = Object.keys(props.message).length !== 0 ? props.message : null;

        if (message) {
            const { 
                severity = message.severity ?? 'warn',
                summary = message.summary ?? '',
                detail = message.detail ?? '',
                life = 3000,
                closable = false,
            } = options;

            toast.add({
                severity: severity,
                summary: summary,
                detail: detail,
                life: life,
                closable: closable,
            });
        }
    };

    return {
        flashMessage,
    };
}
