<script setup>
import Dropdown from '@/Components/Dropdown.vue'
import Toggle from '@/Components/Toggle.vue'
import { ref } from 'vue'

const tempArr = ref(['First', 'Second', 'Third']);
</script>

<template>
    <div class="flex flex-col gap-16 items-center">
        <!--  -------------------------Toggle----------------------------- -->
        <h2>Toggle</h2>
        <div class="flex flex-col gap-2 w-full">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <Toggle
                    class="col-span-full xl:col-span-2"
                    :checked="false"
                    :inputName="'toggle1'"
                />
                <Toggle
                    class="xl:col-span-2"
                    :checked="true"
                    :inputName="'toggle2'"
                />
            </div>
            <!-- ---------------State: Disabled-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <Toggle
                    class="xl:col-span-2"
                    :checked="false"
                    :inputName="'toggle3'"
                    :disabled="true"
                />
                <Toggle
                    class="xl:col-span-2"
                    :checked="true"
                    :inputName="'toggle2'"
                    :disabled="true"
                />
            </div>
        </div>
    </div>
</template>
