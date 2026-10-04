<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../components/AppLayout.vue";
import Pagination from "../../components/Pagination.vue";
import { formatPrice } from "../../format.js";

defineProps({
    alerts: { type: Object, required: true },
});
</script>

<template>
    <Head title="Alerts" />
    <AppLayout>
        <h1 class="text-2xl font-bold mb-6">Alerts</h1>

        <div v-if="alerts.data.length === 0" class="text-gray-500">
            No alerts yet. Alerts appear here when a new listing matches one of
            your saved searches.
        </div>

        <ul v-else class="space-y-4">
            <li
                v-for="alert in alerts.data"
                :key="alert.id"
                class="border rounded-lg p-4"
            >
                <Link
                    :href="`/listings/${alert.listing.id}`"
                    class="block hover:bg-gray-50 -m-4 p-4 rounded-lg"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold">{{
                            alert.listing.address_line_1
                        }}</span>
                        <span class="text-sm text-gray-500">{{
                            new Date(alert.created_at).toLocaleDateString()
                        }}</span>
                    </div>
                    <div class="text-sm text-gray-700 space-x-4">
                        <span>{{ formatPrice(alert.listing.price) }}</span>
                        <span>{{ alert.listing.bedrooms }} bed</span>
                        <span>{{ alert.listing.property_type_label }}</span>
                        <span>{{ alert.listing.branch.region }}</span>
                    </div>
                    <div
                        v-if="alert.saved_search"
                        class="text-xs text-gray-400 mt-1"
                    >
                        Matched saved search:
                        <span v-if="alert.saved_search.region">{{
                            alert.saved_search.region
                        }}</span>
                        <span v-if="alert.saved_search.max_price"
                            >&pound;{{
                                alert.saved_search.max_price.toLocaleString()
                            }}
                            max</span
                        >
                        <span v-if="alert.saved_search.min_bedrooms"
                            >{{ alert.saved_search.min_bedrooms }}+ beds</span
                        >
                        <span v-if="alert.saved_search.property_type_label">{{
                            alert.saved_search.property_type_label
                        }}</span>
                    </div>
                </Link>
            </li>
        </ul>

        <Pagination :meta="alerts.meta" :links="alerts.links" class="mt-6" />
    </AppLayout>
</template>
