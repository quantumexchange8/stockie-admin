import { reactive, watchEffect, watch } from 'vue'
import { useToast } from 'primevue/usetoast';
import { usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';

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

    function flashMessage(options = {}) {
        const message = props.message && Object.keys(props.message).length !== 0 ? props.message : null;

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
    
    function showMessage(message) {
        if (message && message.summary) {
            toast.add({
                severity: message.severity ?? 'info',
                summary: message.summary,
                detail: message.detail ?? '',
                life: message.life ?? 3000,
                closable: message.closable ?? false,
            });
        }
    };

    return {
        flashMessage,
        showMessage,
    };
}

export function usePhoneUtils() {
    // Format the phone number value for display
    function formatPhone(phone, withHyphen = false, forEdit = false) {
        if (!phone) return phone; 

        let inbetweenChar = withHyphen ? '-' : ' ';
    
        // Remove non-digit characters to ensure clean formatting
        let cleanedPhone = phone.replace(/\D/g, '');
    
        if (forEdit) {
            cleanedPhone = cleanedPhone.slice(2);
        
            // Check if it's a mobile number starting with '1' or '11', or landline starting with '3' or '8'
            if (cleanedPhone.startsWith('11')) {
                // Format for +60 11 xxxx xxxx
                return `${cleanedPhone.slice(0, 2)}${inbetweenChar}${cleanedPhone.slice(2, 6)} ${cleanedPhone.slice(6)}`;
            } else if (cleanedPhone.startsWith('1') || cleanedPhone.startsWith('+608')) {
                // Format for +60 1x xxx xxxx
                return `${cleanedPhone.slice(0, 2)}${inbetweenChar}${cleanedPhone.slice(2, 5)} ${cleanedPhone.slice(5)}`;
            } else {
                // Format for +60 3 xxxx xxxx or +60 8x xxx xxx
                return `${cleanedPhone.slice(0, 1)}${inbetweenChar}${cleanedPhone.slice(1, 5)} ${cleanedPhone.slice(5)}`;
            }
        } else {
            // Ensure the number starts with '+60', remove the '60' if already prefixed
            if (cleanedPhone.startsWith('60')) {
                cleanedPhone = `+${cleanedPhone}`;
            } else if (!cleanedPhone.startsWith('+60')) {
                cleanedPhone = `+60${cleanedPhone}`;
            }
        
            // Check if it's a mobile number starting with '1' or '11', or landline starting with '3' or '8'
            if (cleanedPhone.startsWith('+6011')) {
                // Format for +60 11 xxxx xxxx
                return `${cleanedPhone.slice(0, 3)} ${cleanedPhone.slice(3, 5)}${inbetweenChar}${cleanedPhone.slice(5, 9)} ${cleanedPhone.slice(9)}`;
            } else if (cleanedPhone.startsWith('+601') || cleanedPhone.startsWith('+608')) {
                // Format for +60 1x xxx xxxx
                return `${cleanedPhone.slice(0, 3)} ${cleanedPhone.slice(3, 5)}${inbetweenChar}${cleanedPhone.slice(5, 8)} ${cleanedPhone.slice(8)}`;
            } else if (cleanedPhone.startsWith('+60')) {
                // Format for +60 3 xxxx xxxx or +60 8x xxx xxx
                return `${cleanedPhone.slice(0, 3)} ${cleanedPhone.slice(3, 4)}${inbetweenChar}${cleanedPhone.slice(4, 8)} ${cleanedPhone.slice(8)}`;
            }
        }
    
        return cleanedPhone;
    };

    // Transform the phone number value for storing
    function transformPhone(phone) {
        if (!phone) return phone; 

        // Remove whitespace and underscore, then add '+60' if it doesn't already exist
        let formattedPhone = phone.startsWith('+60') 
            ? phone.replace(/[\s_]+/g, '') 
            : `+60${phone.replace(/[\s_]+/g, '')}`;

        // Determine the maximum allowed length based on phone number patterns
        const maxLength = formattedPhone.startsWith('+6011') 
            ? 14 // +60 11-xxxx xxxx (mobile)
            : formattedPhone.startsWith('+601') || formattedPhone.startsWith('+603') 
                ? 13 // +60 1x-xxx xxxx (mobile) or +60 3-xxxx xxxx (landline)
                : 12; // +60 8x-xxx xxx (landline) or default

        return formattedPhone.slice(0, maxLength);
    };

    // Format the phone number input and updates the form field value
    function formatPhoneInput(event) {
        let input = event.target.value.replace(/\D/g, ''); // Remove non-digit characters

        // Handle backspace clearing without reapplying format
        if (event.inputType === 'deleteContentBackward') return;

        const phone = input.startsWith('60') ? input.slice(2) : input;

        // Define formatting patterns for different types of numbers
        const formats = {
            '1': phone.startsWith('11') ? /(\d{0,2})(\d{0,4})(\d{0,4})/ : /(\d{0,2})(\d{0,3})(\d{0,4})/,
            '3': /(\d{0,1})(\d{0,4})(\d{0,4})/,
            '8': /(\d{0,2})(\d{0,3})(\d{0,3})/,
            'default': /(\d{0,1})(\d{0,3})(\d{0,4})/
        };

        // Select the format based on the first digit of the phone number
        const matchFormat = formats[phone[0]] || formats['default'];

        // Restrict the length and format the number based on the pattern
        const match = phone.match(matchFormat);
        const formatted = `${match[1]}-${match[2]}${match[3] ? ' ' + match[3] : ''}`;

        // Update the displayed value in the input field
        event.target.value = formatted;
    };

    return {
        formatPhone,
        transformPhone,
        formatPhoneInput,
    };
}

export function useInputValidator() {
    function isValidNumberKey(e, allowDecimal = true) {
        const { key, target: { value } } = e;

        // Allow digits
        if (/^\d$/.test(key)) {
            //Check decimal places if option is allowed
            if (allowDecimal && value.includes('.')) {
                const [,decimalPart] = value.split('.');

                if (decimalPart.length >= 2) {
                    e.preventDefault();
                }
            }
            return;
        };

        // Allow decimal point if not already in the value
        if (allowDecimal && key === '.' && /\d/.test(value) && !value.includes('.')) return;

        // Prevent invalid characters
        e.preventDefault();
    };

    return { isValidNumberKey };
}

export function useFileExport() {
    function arrayToCsv(data) {
        const array = [Object.keys(data[0]), ...data];

        return array.map(it => Object.values(it).toString()).join('\n');
    };

    function downloadBlob(content, fileName, contentType) {
        // Create a blob
        var blob = new Blob([content], { type: contentType });
        var url = URL.createObjectURL(blob);
        // Create a link to download it
        var pom = document.createElement('a');
        pom.href = url;
        pom.setAttribute('download', fileName);
        pom.click();
        URL.revokeObjectURL(url); // Revoke the object URL after download
    };

    function exportToCSV(mappedData, fileNamePrefix) { 
        if (mappedData.length > 0) {
            const currentDateTime = dayjs().format('YYYYMMDDhhmmss');
            const fileName = `${fileNamePrefix}_${currentDateTime}.csv`;
            const contentType = 'text/csv;charset=utf-8;';
            const myLogs = arrayToCsv(mappedData);
            downloadBlob(myLogs, fileName, contentType);
        } else {
            console.log('No data available');
        }
    }

    return { exportToCSV };
}

export const vClickOutside = {
    mounted(element, binding) {
        const clickEventHandler = (event) => {
            // Check if the clicked element is outside the directive's element
            if (!element.contains(event.target) && typeof binding.value === 'function') {
                binding.value(event); // Execute the provided callback
            }
        };
        element.__clickedOutsideHandler__ = clickEventHandler;
        document.addEventListener("click", clickEventHandler);
    },
    unmounted(element) {
        document.removeEventListener("click", element.__clickedOutsideHandler__);
        delete element.__clickedOutsideHandler__; // Cleanup
    },
};
