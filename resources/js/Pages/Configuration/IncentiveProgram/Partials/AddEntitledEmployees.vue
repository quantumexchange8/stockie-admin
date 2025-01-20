<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import SearchBar from '@/Components/SearchBar.vue';
import Tapbar from '@/Components/Tapbar.vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
})

const emit = defineEmits(['isDirty', 'closeModal']);

</script>

<template>
    <div class="flex flex-col items-end gap-5 rounded-[5px] bg-white">
        <!-- <SearchBar 
            placeholder="Search"
            :showFilter="false"
            v-model="searchQuery"
        /> -->

        <form @submit.prevent="submit">
            
            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click=""
                >
                    Cancel
                </Button>
                <Button
                    :type="'button'"
                    :size="'lg'"
                    @click=""
                >
                    Done
                </Button>
            </div>
        </form>
    </div>
</template>

