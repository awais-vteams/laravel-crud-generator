<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div 
                    v-if="link.url === null" 
                    class="mr-1 mb-1 px-4 py-2 text-sm text-gray-500 border rounded"
                    :class="{ 'opacity-50': link.url === null }"
                    v-html="link.label"
                />
                <inertia-link
                    v-else
                    class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-indigo-100"
                    :class="{
                        'bg-indigo-500 text-white': link.active,
                        'text-gray-700': !link.active
                    }"
                    :href="link.url"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        links: Array
    }
}
</script>