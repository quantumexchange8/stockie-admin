<script setup>
import { ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue'
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
// import Dropdown from 'primevue/dropdown';

const props = defineProps({
    errors: Object,
});

const categoryArr = ref(['Beer', 'Whiskey', 'Wine', 'Wine', 'Wine', 'Wine', 'Wine', 'Wine', 'Wine']);

const emit = defineEmits(['close'])

const form = useForm({
    multiple: false,
    item: '',
    product_name: '',
    qty: 1,
    price: '',
    point: '',
    category_id: '',
    keep: null,
});

const formSubmit = () => { 
    form.keep = form.keep ? 'Active': 'Inactive';

    form.post(route('products.store'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            emit('close');
            
        },
    })
};

const cancelForm = () => {
    form.reset();
    emit('close');
}

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="flex items-start gap-6 max-h-[650px] pl-1 pr-2 py-1 overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <div 
                class="flex flex-col justify-center gap-[10px] p-3 w-[373px] h-[373px] 
                        rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"
            >
            </div>
            <div class="flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <div class="flex items-start gap-6 self-stretch">
                    <p class="text-grey-900 font-normal text-base">This product comes in a set (Bucket Product)</p>
                    <Checkbox 
                        v-model:checked="form.multiple"
                    />
                </div>
                <div class="flex flex-col items-start gap-4 self-stretch">
                    <TextInput
                        :inputId="'item'"
                        :labelText="'Select an item'"
                        :placeholder="'Select'"
                        :errorMessage="form.errors?.item || ''"
                        v-model="form.item"
                    />
                    <TextInput
                        :inputId="'product_name'"
                        :labelText="'Product Name'"
                        :errorMessage="form.errors?.product_name || ''"
                        v-model="form.product_name"
                        class="col-span-full xl:col-span-4"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <TextInput
                            :inputId="'price'"
                            :labelText="'Price'"
                            :errorMessage="form.errors?.price || ''"
                            v-model="form.price"
                            class="col-span-full xl:col-span-4"
                        />
                        <TextInput
                            :inputId="'point'"
                            :labelText="'Points can be earned'"
                            :errorMessage="form.errors?.point || ''"
                            v-model="form.point"
                            class="col-span-full xl:col-span-4"
                        />
                        <Dropdown
                            :inputName="'category_id'"
                            :labelText="'Select category'"
                            :inputArray="categoryArr"
                            :errorMessage="form.errors?.category_id || ''"
                            v-model="form.category_id"
                            class="col-span-full xl:col-span-4"
                        />
                        <!-- <Dropdown 
                            v-model="form.category_id" 
                            :options="categoryArr" 
                            optionLabel="name" 
                            placeholder="Select a City" 
                            class="w-full md:w-14rem" 
                            :pt="{
                                root: ({ props, state, parent }) => ({
                                    class: [
                                    // Display and Position
                                    'inline-flex w-full',
                                    'relative',
                                    // Shape
                                    { 'rounded-md': parent.instance.$name !== 'InputGroup' },
                                    { 'first:rounded-l-md rounded-none last:rounded-r-md': parent.instance.$name == 'InputGroup' },
                                    { 'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup' },
                                    { 'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons },
                                    // Color and Background
                                    'bg-primary-0 dark:bg-primary-900',
                                    'border border-primary-300',
                                    { 'dark:border-primary-700': parent.instance.$name != 'InputGroup' },
                                    { 'dark:border-primary-600': parent.instance.$name == 'InputGroup' },
                                    { 'border-primary-300 dark:border-primary-600': !props.invalid },
                                    // Invalid State
                                    { 'border-red-500 dark:border-red-400': props.invalid },
                                    // Transitions
                                    'transition-all',
                                    'duration-200',
                                    // States
                                    { 'hover:border-primary': !props.invalid },
                                    { 'outline-none outline-offset-0 ring ring-primary-400/50 dark:ring-primary-300/50': state.focused },
                                    // Misc
                                    'cursor-pointer',
                                    'select-none',
                                    { 'opacity-60': props.disabled, 'pointer-events-none': props.disabled, 'cursor-default': props.disabled }
                                    ]
                                }),
                                input: ({ props, parent }) => {
                                    var _a;
                                    return {
                                    class: [
                                        //Font
                                        'leading-[normal]',
                                        // Display
                                        'block',
                                        'flex-auto',
                                        // Color and Background
                                        'bg-transparent',
                                        'border-0',
                                        { 'text-white dark:text-white/80': props.modelValue != null, 'text-white dark:text-white': props.modelValue == null },
                                        'placeholder:text-white dark:placeholder:text-white',
                                        // Sizing and Spacing
                                        'w-full',
                                        'p-3',
                                        { 'pr-7': props.showClear },
                                        //Shape
                                        'rounded-none',
                                        // Transitions
                                        'transition',
                                        'duration-200',
                                        // States
                                        'focus:outline-none focus:shadow-none',
                                        // Filled State *for FloatLabel
                                        { filled: ((_a = parent.instance) == null ? void 0 : _a.$name) == 'FloatLabel' && props.modelValue !== null },
                                        // Misc
                                        'relative',
                                        'cursor-pointer',
                                        'overflow-hidden overflow-ellipsis',
                                        'whitespace-nowrap',
                                        'appearance-none'
                                    ]
                                    };
                                },
                                trigger: {
                                    class: ['flex items-center justify-center', 'shrink-0', 'bg-transparent', 'text-white', 'w-12', 'rounded-tr-md', 'rounded-br-md']
                                },
                                panel: {
                                    class: ['absolute top-0 left-0', 'border-0 dark:border', 'rounded-md', 'shadow-md', 'bg-primary-0 dark:bg-primary-800', 'text-white dark:text-white/80', 'dark:border-primary-700']
                                },
                                wrapper: {
                                    class: ['max-h-[200px]', 'overflow-auto']
                                },
                                list: {
                                    class: 'py-3 list-none m-0'
                                },
                                item: ({ context }) => ({
                                    class: [
                                    // Font
                                    'font-normal',
                                    'leading-none',
                                    // Position
                                    'relative',
                                    // Shape
                                    'border-0',
                                    'rounded-none',
                                    // Spacing
                                    'm-0',
                                    'py-3 px-5',
                                    // Colors
                                    {
                                        'text-white dark:text-white/80': !context.focused && !context.selected,
                                        'bg-primary-200 dark:bg-primary-600/60': context.focused && !context.selected,
                                        'text-white dark:text-white/80': context.focused && !context.selected,
                                        'text-primary-highlight-inverse': context.selected,
                                        'bg-primary-highlight': context.selected
                                    },
                                    //States
                                    { 'hover:bg-primary-100 dark:hover:bg-primary-600/80': !context.focused && !context.selected },
                                    { 'hover:bg-primary-highlight-hover': context.selected },
                                    'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring focus-visible:ring-inset focus-visible:ring-primary-400/50 dark:focus-visible:ring-primary-300/50',
                                    // Transitions
                                    'transition-shadow',
                                    'duration-200',
                                    // Misc
                                    { 'pointer-events-none cursor-default': context.disabled },
                                    { 'cursor-pointer': !context.disabled },
                                    '',
                                    'whitespace-nowrap'
                                    ]
                                }),
                                itemgroup: {
                                    class: ['font-bold', 'm-0', 'py-3 px-5', 'text-white dark:text-white/80', 'bg-primary-0 dark:bg-primary-600/80', 'cursor-auto']
                                },
                                emptymessage: {
                                    class: ['leading-none', 'py-3 px-5', 'text-white dark:text-white/80', 'bg-transparent']
                                },
                                header: {
                                    class: ['py-3 px-5', 'm-0', 'border-b', 'rounded-tl-md', 'rounded-tr-md', 'text-white dark:text-white/80', 'bg-primary-100 dark:bg-primary-800', 'border-primary-300 dark:border-primary-700']
                                },
                                filtercontainer: {
                                    class: 'relative'
                                },
                                filterinput: {
                                    class: ['leading-[normal]', 'pr-7 py-3 px-3', '-mr-7', 'w-full', 'text-white dark:text-white/80', 'bg-primary-0 dark:bg-primary-900', 'border-primary-200 dark:border-primary-700', 'border', 'rounded-lg', 'appearance-none', 'transition', 'duration-200', 'hover:border-primary', 'focus:ring focus:outline-none focus:outline-offset-0', 'focus:ring-primary-400/50 dark:focus:ring-primary-300/50', 'appearance-none']
                                },
                                filtericon: {
                                    class: ['absolute', 'top-1/2 right-3', '-mt-2']
                                },
                                clearicon: {
                                    class: ['text-white', 'absolute', 'top-1/2', 'right-12', '-mt-2']
                                },
                                loadingicon: {
                                    class: 'text-white dark:text-white animate-spin'
                                },
                                transition: {
                                    enterFromClass: 'opacity-0 scale-y-[0.8]',
                                    enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
                                    leaveActiveClass: 'transition-opacity duration-100 ease-linear',
                                    leaveToClass: 'opacity-0'
                                }
                            }"
                        /> -->
                    </div>
                    <div class="flex items-start gap-10">
                        <div class="flex items-center gap-3">
                            <input
                                :type="'radio'"
                                :name="'keepYes'"
                                :value="true"
                                v-model="form.keep"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <p class="text-grey-900 font-normal text-base">Keep is allowed</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input
                                :type="'radio'"
                                :name="'keepNo'"
                                :value="false"
                                v-model="form.keep"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <p class="text-grey-900 font-normal text-base">Keep is not allowed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="cancelForm"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
            >
                Add
            </Button>
        </div>
    </form>
</template>
