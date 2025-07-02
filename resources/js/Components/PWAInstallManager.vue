<script setup>
import { useToast } from 'primevue/usetoast';
import { ref, onMounted } from 'vue';

const toast = useToast();
const deferredPrompt = ref(null);
const isAppInstalled = ref(false);

// Check if app is already installed
const checkInstallStatus = () => {
  isAppInstalled.value = window.matchMedia('(display-mode: standalone)').matches;
};

// Handle install prompt
onMounted(() => {
  checkInstallStatus();
  
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    if (!isAppInstalled.value) {
      showInstallPrompt();
    }
  });

  window.addEventListener('appinstalled', () => {
    isAppInstalled.value = true;
    toast.add({
      severity: 'success',
      summary: 'App Installed',
      detail: 'Thank you for installing our app!',
      life: 3000
    });
  });
});

const showInstallPrompt = () => {
  toast.add({
    severity: 'info',
    summary: 'Install App',
    detail: 'Get the best experience by installing our app',
    life: 15000,
    position: 'bottom-center',
    content: () => h('div', { class: 'flex gap-2' }, [
      h('Button', {
        label: 'Install',
        icon: 'pi pi-download',
        onClick: installApp,
        class: 'p-button-sm'
      }),
      h('Button', {
        label: 'Later',
        icon: 'pi pi-times',
        onClick: () => toast.remove(),
        class: 'p-button-sm p-button-text'
      })
    ])
  });
};

const installApp = async () => {
  if (deferredPrompt.value) {
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
      isAppInstalled.value = true;
    }
    deferredPrompt.value = null;
    toast.remove();
  }
};
</script>

<template>
  <!-- Component is toast-based, no template needed -->
</template>