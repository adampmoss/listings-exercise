<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';

const props = defineProps({
    savedSearches: { type: Array, required: true },
    regions: { type: Array, required: true },
    propertyTypes: { type: Array, required: true },
});

const form = ref({
    max_price: '',
    min_bedrooms: '',
    property_type: '',
    region: '',
});

const errors = ref({});

function store() {
    router.post('/saved-searches', form.value, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { max_price: '', min_bedrooms: '', property_type: '', region: '' };
            errors.value = {};
        },
        onError: (err) => (errors.value = err),
    });
}

function destroy(id) {
    router.delete(`/saved-searches/${id}`, { preserveScroll: true });
}

const fieldClasses =
    'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900';
</script>

<template>
    <Head title="Saved Searches" />

    <AppLayout heading="Saved Searches" subheading="Get alerted when new listings match your criteria.">
        <!-- Create form -->
        <form class="mb-8 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="store">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">New saved search</h2>

            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <label for="property_type" class="text-xs font-medium text-slate-600">Type</label>
                    <select id="property_type" v-model="form.property_type" :class="fieldClasses">
                        <option value="">Any type</option>
                        <option v-for="type in propertyTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="region" class="text-xs font-medium text-slate-600">Area</label>
                    <select id="region" v-model="form.region" :class="fieldClasses">
                        <option value="">Any area</option>
                        <option v-for="region in regions" :key="region" :value="region">
                            {{ region }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="min_bedrooms" class="text-xs font-medium text-slate-600">Min beds</label>
                    <input
                        id="min_bedrooms"
                        v-model="form.min_bedrooms"
                        type="number"
                        min="0"
                        max="20"
                        placeholder="Any"
                        :class="[fieldClasses, 'w-28']"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label for="max_price" class="text-xs font-medium text-slate-600">Max price (£)</label>
                    <input
                        id="max_price"
                        v-model="form.max_price"
                        type="number"
                        min="0"
                        step="10000"
                        placeholder="Any"
                        :class="[fieldClasses, 'w-36']"
                    />
                </div>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
                >
                    Save search
                </button>
            </div>

            <p v-if="errors.criteria" class="mt-2 text-sm text-red-600">{{ errors.criteria }}</p>
        </form>

        <!-- Saved search list -->
        <div v-if="savedSearches.length === 0"
            class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500"
        >
            No saved searches yet. Create one above to get alerted about new listings.
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="search in savedSearches"
                :key="search.id"
                class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4"
            >
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-700">
                    <span v-if="search.region" class="font-medium">{{ search.region }}</span>
                    <span v-if="search.max_price">Up to £{{ search.max_price.toLocaleString() }}</span>
                    <span v-if="search.min_bedrooms">{{ search.min_bedrooms }}+ beds</span>
                    <span v-if="search.property_type_label">{{ search.property_type_label }}</span>
                </div>
                <button
                    class="ml-4 shrink-0 rounded-lg px-3 py-1.5 text-sm text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                    @click="destroy(search.id)"
                >
                    Delete
                </button>
            </li>
        </ul>
    </AppLayout>
</template>
