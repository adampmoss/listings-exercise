<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';
import ListingCard from '../../components/ListingCard.vue';
import Pagination from '../../components/Pagination.vue';
import { formatDate } from '../../format.js';

defineProps({
    alerts: { type: Object, required: true },
});
</script>

<template>
    <Head title="Alerts" />

    <AppLayout heading="Alerts" subheading="New listings matching your saved searches.">
        <div
            v-if="alerts.data.length === 0"
            class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500"
        >
            No alerts yet. Alerts appear here when a new listing matches one of your
            <Link href="/saved-searches" class="underline hover:text-slate-900">saved searches</Link>.
        </div>

        <div v-else class="space-y-4">
            <article
                v-for="alert in alerts.data"
                :key="alert.id"
                class="rounded-xl border border-slate-200 bg-white p-4"
            >
                <div class="mb-3 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ formatDate(alert.created_at) }}</span>
                    <span v-if="alert.saved_search" class="text-right">
                        Matched:
                        <template v-if="alert.saved_search.region">{{ alert.saved_search.region }}</template>
                        <template v-if="alert.saved_search.max_price">
                            · £{{ alert.saved_search.max_price.toLocaleString() }} max
                        </template>
                        <template v-if="alert.saved_search.min_bedrooms">
                            · {{ alert.saved_search.min_bedrooms }}+ beds
                        </template>
                        <template v-if="alert.saved_search.property_type_label">
                            · {{ alert.saved_search.property_type_label }}
                        </template>
                    </span>
                </div>
                <ListingCard :listing="alert.listing" />
            </article>
        </div>

        <Pagination :meta="alerts.meta" :links="alerts.links" />
    </AppLayout>
</template>
