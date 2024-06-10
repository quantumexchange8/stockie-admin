<script setup>
import Dropdown from '@/Components/Dropdown.vue'
import { ref } from 'vue'

const tempArr = ref(['First', 'Second', 'Third']);
</script>

<template>
    <div class="flex flex-col gap-16 items-center">
        <!--  -------------------------Labelled Text Input----------------------------- -->
        <h2>Labelled Dropdown</h2>
        <div class="flex flex-col gap-2 w-full">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No --> 
                <Dropdown
                    class="col-span-full xl:col-span-4"
                    :inputName="'dropdown1'"
                    :labelText="'Label'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                />
                <!-- Hint: Yes, Error: No -->
                <Dropdown
                    class="xl:col-span-4"
                    :inputName="'dropdown2'"
                    :labelText="'Label'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                    :hintText="'Hint text to help user'"
                />
                <!-- Hint: No, Error: Yes -->
                <Dropdown
                    class="xl:col-span-4"
                    :inputName="'dropdown3'"
                    :labelText="'Label'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                    :errorMessage="'This is an error message'"
                />
            </div>
            <!-- ---------------State: Disabled-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <Dropdown
                    class="xl:col-span-4"
                    :inputName="'dropdown4'"
                    :labelText="'Label'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                    :disabled="true"
                />
            </div>
        </div>
        
        <!--  -------------------------Text Input--------------------------------------- -->
        <h2>Text Dropdown</h2>
        <div class="flex flex-col gap-4">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No --> 
                <Dropdown
                    class="col-span-full xl:col-span-4"
                    :inputName="'dropdown5'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                />
                <!-- Hint: Yes, Error: No -->
                <Dropdown
                    class="xl:col-span-4"
                    :inputName="'dropdown6'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                    :hintText="'Hint text to help user'"
                />
                <!-- Hint: No, Error: Yes -->
                <Dropdown
                    class="xl:col-span-4"
                    :inputName="'dropdown7'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                    :errorMessage="'This is an error message'"
                />
            </div>
            <!-- ---------------State: Disabled-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <Dropdown
                    class="xl:col-span-4"
                    :inputName="'dropdown8'"
                    :inputArray="tempArr"
                    :placeholder="'Select'"
                    :disabled="true"
                />
            </div>
        </div>
    </div>
</template>
