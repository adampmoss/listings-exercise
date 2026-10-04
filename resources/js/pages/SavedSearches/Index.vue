<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';

defineProps({
    savedSearches: { type: Array, required: true },
    regions: { type: Array, required: true },
    propertyTypes: { type: Array, required: true },
});

function destroy(id) {
    router.delete(`/saved-searches/${id}`);
}
</script>

<template>
    <Head title="Saved Searches" />
    <AppLayout>
        <h1 class="text-2xl font-bold mb-6">Saved Searches</h1>

        <div v-if="savedSearches.length === 0" class="text-gray-500">
            You have no saved searches yet.
        </div>

        <ul v-else class="space-y-4">
            <li
                v-for="search in savedSearches"
                :key="search.id"
                class="border rounded-lg p-4 flex items-center justify-between"
            >
                <div class="space-x-4 text-sm text-gray-700">
                    <span v-if="search.region">Region: {{ search.region }}</span>
                    <span v-if="search.max_price">Max price: &pound;{{ search.max_price.toLocaleString() }}</span>
                    <span v-if="search.min_bedrooms">Min bedrooms: {{ search.min_bedrooms }}</span>
                    <span v-if="search.property_type_label">Type: {{ search.property_type_label }}</span>
                </div>
                <button
                    class="text-red-600 hover:text-red-800 text-sm"
                    @click="destroy(search.id)"
                >
                    Delete
                </button>
            </li>
        </ul>
    </AppLayout>
</template>
