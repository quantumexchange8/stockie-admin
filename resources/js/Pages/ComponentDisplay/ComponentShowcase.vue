<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ButtonComponents from './ButtonComponents.vue'
import InputComponents from './InputComponents.vue'
import DropdownComponents from './DropdownComponents.vue'
import ToggleComponents from './ToggleComponents.vue'

const openTab = ref(1);
</script>

<template>
    <Head title="Components" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Components</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-center">
                        <div class="w-[100%] m-auto">
                            <div class="mb-4 flex space-x-4 p-2 bg-white rounded-lg shadow-md">
                                <button 
                                    @click="openTab = 1" 
                                    :class="{ 'bg-blue-600 text-white': openTab === 1 }" 
                                    type="button" class="flex-1 py-2 px-4 rounded-md focus:outline-none focus:shadow-outline-blue transition-all duration-300"
                                >
                                    Buttons
                                </button>
                                <button 
                                    @click="openTab = 2" 
                                    :class="{ 'bg-blue-600 text-white': openTab === 2 }" 
                                    type="button" class="flex-1 py-2 px-4 rounded-md focus:outline-none focus:shadow-outline-blue transition-all duration-300"
                                >
                                    Input Fields
                                </button>
                                <button 
                                    @click="openTab = 3" 
                                    :class="{ 'bg-blue-600 text-white': openTab === 3 }" 
                                    type="button" class="flex-1 py-2 px-4 rounded-md focus:outline-none focus:shadow-outline-blue transition-all duration-300"
                                >
                                    Dropdowns
                                </button>
                                <button 
                                    @click="openTab = 4" 
                                    :class="{ 'bg-blue-600 text-white': openTab === 4 }" 
                                    type="button" class="flex-1 py-2 px-4 rounded-md focus:outline-none focus:shadow-outline-blue transition-all duration-300"
                                >
                                    Others
                                </button>
                            </div>

                            <div 
                                v-if="openTab === 1" 
                                class="transition-all duration-300 bg-white px-4 py-8 rounded-lg shadow-md border-l-4"
                            >
                                <ButtonComponents/>
                            </div>

                            <div 
                                v-if="openTab === 2" 
                                class="transition-all duration-300 bg-white px-4 py-8 rounded-lg shadow-md border-l-4"
                            >
                                <InputComponents/>
                            </div>

                            <div 
                                v-if="openTab === 3" 
                                class="transition-all duration-300 bg-white px-4 py-8 rounded-lg shadow-md border-l-4"
                            >
                                <DropdownComponents/>
                            </div>
                            <div 
                                v-if="openTab === 4" 
                                class="transition-all duration-300 bg-white px-4 py-8 rounded-lg shadow-md border-l-4"
                            >
                                <ToggleComponents/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
