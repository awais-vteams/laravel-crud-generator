<template>
    <div class="relative flex items-center">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input
            type="text"
            class="py-2 pl-10 pr-4 w-full text-sm text-gray-700 placeholder-gray-400 bg-white border border-gray-300 rounded focus:outline-none focus:border-indigo-500"
            placeholder="Search..."
            :value="modelValue"
            @input="updateValue"
        />
    </div>
</template>

<script>
export default {
    props: {
        modelValue: String
    },
    emits: ['update:modelValue'],
    data() {
        return {
            timeout: null
        }
    },
    methods: {
        updateValue(e) {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.$emit('update:modelValue', e.target.value);
            }, 300); // 300ms debounce
        }
    }
}
</script>