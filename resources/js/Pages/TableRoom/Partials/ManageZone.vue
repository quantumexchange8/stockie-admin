<script setup>
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { useForm } from "@inertiajs/vue3";
import { nextTick, reactive, ref, watch } from "vue";
import { DeleteIcon, Menu2Icon, PlusIcon } from '@/Components/Icons/solid.jsx';
import Modal from '@/Components/Modal.vue';
import { useCustomToast } from "@/Composables";

const props = defineProps({
    zonesArr: {
        type: Array,
        default: [],
    },
});

const emit = defineEmits(["close", 'getZoneDetails', 'isDirty']);
const { showMessage } = useCustomToast();

const deleteProductFormIsOpen = ref(false);
const selectedZone = ref(null);
const isEditing = ref(false);
const currentZoneId = ref(null);
const initialZoneName = ref(null);
const inputRefs = reactive({});
const isEdited = ref(false);
const zones = ref();
const newZoneCounter = ref(0);

const form = useForm({
    name: "",
    edit_name: "",
    id: "",
});

const newZones = useForm({
    zones: [],
})

const addZone = () => {
    isEditing.value = false;
    newZoneCounter.value++;
    newZones.zones.push({name: '', index: newZoneCounter.value})
    
    //when clicked, auto focus the latest added input
    nextTick(() => {
        const inputRefName = `zones${newZoneCounter.value}`;
        if (inputRefs[inputRefName]) {
            inputRefs[inputRefName].focus();
        }
    });
}

const removeAddZone = (id) => { 
    newZones.zones = newZones.zones.filter(zone => zone.index !== id); 
}

const discardChanges = () => {
    isEditing.value = false;
    form.reset();
}

const submit = () => {
    //only submit if its edited, else just return default state
    if(isEdited.value){
        if(isEditing.value){
            form.post(route('tableroom.edit-zone'), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    isEditing.value = false;
                    form.reset();
                    setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: 'Zone name has been edited successfully.'
                    });
                }, 200);
                    emit('getZoneDetails');
                }
            })
        }
        else{
            newZones.post(route("tableroom.add-zone"), {
                preserveScroll: true,
                preserveState: 'errors',
                onSuccess: () => {
                    form.reset();
                    emit('close', 'leave');
                    showMessage({
                        severity: 'success',
                        summary: 'Zone has been added successfully.'
                    });
                }
            });
        }
    } else {
        isEditing.value = false;
        form.reset();
    }
};

const handleDefaultClick = (event) => {
    event.stopPropagation();  
    event.preventDefault();   
};

const showDeleteGroupForm = (event, id) => {
    handleDefaultClick(event);
    selectedZone.value = id;
    deleteProductFormIsOpen.value = true;
}

const hideDeleteProductForm = () => {
    deleteProductFormIsOpen.value = false;
}


const startEditing = (zonesArr) => {
    isEditing.value = true;
    form.edit_name = zonesArr.text;
    form.id = zonesArr.value;
    currentZoneId.value = zonesArr.value;

    nextTick(() => {
        const inputRefName = `zones${zonesArr.value}`;
        if (inputRefs[inputRefName]) {
            inputRefs[inputRefName].focus();
        }
    });

    initialZoneName.value = zonesArr.text;
}

watch(() => props.zonesArr, (newValue) => {
    zones.value = newValue ? newValue : {};
}, { immediate: true });

watch(
    () => form.edit_name, 
    (newVal) => {
        isEdited.value = newVal !== initialZoneName.value; 
    },
    { immediate: true } 
);

watch([newZones, isEdited], ([newFormValue, newIsEdited]) => {
    emit('isDirty', newFormValue.isDirty || newIsEdited);
});

</script>

<template>
    <!-- <form novalidate spellcheck="false" @submit.prevent="submit"> -->
        <div class="max-h-[calc(100dvh-18rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <div class="w-full flex flex-col ">
                <div class="flex flex-col gap-[16px]">
                    <!-- existing zones -->
                    <div v-for="zonesArr in zones" :key="zonesArr.id" class="w-full flex flex-row text-grey-600 h-auto p-3 gap-4">
                        <div v-if="!isEditing || currentZoneId !== zonesArr.value" class="w-full flex flex-row justify-between">
                            <div class="flex flex-row gap-[12px]">
                                <svg 
                                    width="25" 
                                    height="25" 
                                    viewBox="0 0 25 25" 
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    >
                                    <path 
                                        d="M3.0918 12.5H21.0918M3.0918 6.5H21.0918M3.0918 18.5H21.0918"
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <span
                                    @click="startEditing(zonesArr)"
                                    >
                                    {{ zonesArr.text }}
                                </span>
                            </div>
                            <div>
                            <DeleteIcon class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="showDeleteGroupForm($event, zonesArr.value)" /></div>
                        </div>
                        <template v-if="isEditing && currentZoneId === zonesArr.value">
                            <TextInput
                                :zoneId="currentZoneId"
                                :errorMessage="form.errors.edit_name"
                                :id="'zone'"
                                input-name="edit_zone_name"
                                inputId="edit_name"
                                v-model="form.edit_name"
                                :ref="el => (inputRefs[`zones${zonesArr.value}`] = el)"
                                @keydown.enter.prevent.stop="submit()"
                            />

                            <Button
                                :type="'submit'"
                                :variant="'tertiary'" 
                                :size="'lg'" 
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                                v-if="isEditing"
                                @click="discardChanges()"
                                class="!w-fit"
                            >
                                Cancel
                            </Button>

                            <Button
                                :type="'submit'"
                                :variant="'primary'" 
                                :size="'lg'" 
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                                v-if="isEditing"
                                @click="submit()"
                                class="!w-fit"
                            >
                                Save
                            </Button>
                        </template>
                    </div>

                    <!-- new zones -->
                    <div v-if="newZones.zones.length" v-for="zones in newZones.zones" class="flex w-full px-3 gap-3 justify-center items-center">
                        <TextInput 
                            :placeholder="'eg: Main Area'" 
                            inputId="name" 
                            type="'text'" 
                            v-model="zones.name"
                            :ref="el => (inputRefs[`zones${zones.index}`] = el)" 
                            :errorMessage="newZones.errors ? newZones.errors['zones.' + zones.index + '.name'] : null"
                        />
                        <!-- <div class="size-6">
                            <DeleteIcon 
                                class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer" 
                                @click="removeAddZone(zones.index)" 
                            />
                        </div> -->

                        <Button
                            :type="'submit'"
                            :variant="'tertiary'" 
                            :size="'lg'" 
                            :class="{ 'opacity-25': newZones.processing }"
                            :disabled="newZones.processing"
                            @click="removeAddZone(zones.index)"
                            class="!w-fit"
                        >
                            Cancel
                        </Button>

                        <Button
                            :type="'submit'"
                            :variant="'primary'" 
                            :size="'lg'" 
                            :class="{ 'opacity-25': newZones.processing }"
                            :disabled="newZones.processing"
                            @click="submit()"
                            class="!w-fit"
                        >
                            Add
                        </Button>
                    </div>

                    <!-- buttons -->
                    <div class="flex flex-col gap-4 p-2">
                        <Button
                            :type="'button'"
                            :variant="'secondary'" 
                            :size="'lg'" 
                            :iconPosition="'left'"
                            :class="{ 'opacity-25': newZones.processing }"
                            :disabled="newZones.processing"
                            @click="addZone"
                        >
                            <template #icon>
                                <PlusIcon
                                    class="w-[20px] h-[20px]"
                                />
                            </template>
                            New Zone
                        </Button>

                        <!-- <Button
                            :type="'submit'"
                            :variant="'primary'" 
                            :size="'lg'" 
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            v-if="isEditing"
                            @click="submit()"
                        >
                            {{ isEdited ? 'Save changes' : 'Discard' }}
                        </Button> -->

                        <!-- <Button
                            :type="'button'"
                            :variant="'primary'" 
                            :size="'lg'" 
                            :class="{ 'opacity-25': newZones.processing }"
                            :disabled="newZones.processing"
                            v-if="newZones.zones.length"
                            @click="submit()"
                        >
                            Add Zone
                        </Button> -->
                    </div>
                </div>
            </div>
        </div>
    <!-- </form> -->
    
    <Modal 
        :show="deleteProductFormIsOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/table-room/table-room/deleteZone/${selectedZone}`"
        :confirmationTitle="'Delete this zone?'"
        :confirmationMessage="'Table and room in this zone will be deleted altogether. Are you sure you want to delete this zone?'"
        @close="hideDeleteProductForm" 
        v-if="selectedZone" 
    />
</template>
