<script setup>
import Toggle from '@/Components/Toggle.vue'
import Checkbox from '@/Components/Checkbox.vue'
import RadioButton from '@/Components/RadioButton.vue'
import Tooltip from '@/Components/Tooltip.vue'
import { ref } from 'vue'

const tempArr = ref(['First', 'Second', 'Third']);

const redioTemp = ref();
</script>

<template>
    <div class="flex flex-col gap-16 items-center">
        <!--  -------------------------Toggle----------------------------- -->
        <div class="flex flex-col gap-2 w-full">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <h2 class="xl:col-span-4">Toggle</h2>
                <h2 class="xl:col-span-4">Checkbox</h2>
                <h2 class="xl:col-span-4">Radio Button</h2>
            </div>
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
                <Checkbox
                    class="xl:col-span-2"
                    :checked="false"
                />
                <Checkbox
                    class="xl:col-span-2"
                    :checked="true"
                />
                <RadioButton
                    class="xl:col-span-2"
                    :checked="false"
                />
                <RadioButton
                    class="xl:col-span-2"
                    :checked="true"
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
                <Checkbox
                    class="xl:col-span-2"
                    :checked="false"
                    :disabled="true"
                />
                <Checkbox
                    class="xl:col-span-2"
                    :checked="true"
                    :disabled="true"
                />
                <RadioButton
                    class="xl:col-span-2"
                    :checked="false"
                    :disabled="true"
                />
                <RadioButton
                    class="xl:col-span-2"
                    :checked="true"
                    :disabled="true"
                />
            </div>
        </div>
        <h2>Tooltip (Hover over the texts)</h2>
        <div class="flex flex-col gap-2 w-full">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <Tooltip 
                    class="!col-start-3 xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'top-left'"
                >
                    <p>Top left</p>
                </Tooltip>
                <Tooltip 
                    class="xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'top-right'"
                >
                    <p>Top right</p>
                </Tooltip>
                <Tooltip 
                    class="xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'bottom-left'"
                >
                    <p>Bottom left</p>
                </Tooltip>
                <Tooltip 
                    class="xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'bottom-right'"
                >
                    <p>Bottom right</p>
                </Tooltip>
            </div>
            <!-- ---------------State: Disabled-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <Tooltip 
                    class="!col-start-3 xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'top-center'"
                >
                    <p>Top center</p>
                </Tooltip>
                <Tooltip 
                    class="xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'bottom-center'"
                >
                    <p>Bottom center</p>
                </Tooltip>
                <Tooltip 
                    class="xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'left'"
                >
                    <p>Left</p>
                </Tooltip>
                <Tooltip 
                    class="xl:col-span-2"
                    :message="'Tooltip Tooltip'"
                    :position="'right'"
                >
                    <p>Right</p>
                </Tooltip>
            </div>
        </div>
    </div>
</template>
