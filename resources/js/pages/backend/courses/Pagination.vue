<!--
    Reusable Pagination component.

    Usage:
    <Pagination
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="filteredItems.length"
        v-model:per-page="perPage"
        item-label="courses"
        @update:current-page="page => currentPage = page"
    />
-->
<template>
    <div v-if="totalItems > 0" class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-sm text-slate-500 text-center sm:text-left">
            {{ $t('Showing') }}
            <span class="font-medium text-slate-700">{{ rangeStart }}</span>
            {{ $t('to') }}
            <span class="font-medium text-slate-700">{{ rangeEnd }}</span>
            {{ $t('of') }}
            <span class="font-medium text-slate-700">{{ totalItems }}</span>
            {{ $t(itemLabel) }}
        </div>

        <div class="flex flex-wrap items-center justify-center gap-2">
            <button
                type="button"
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                :class="[
                    'p-2 rounded-lg text-sm font-medium transition',
                    currentPage === 1
                        ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                        : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <!-- Page Numbers -->
            <div class="flex items-center gap-1">
                <template v-for="(page, i) in visiblePages" :key="`${page}-${i}`">
                    <span
                        v-if="page === '...'"
                        class="px-2 py-2 text-sm text-slate-400 select-none"
                    >{{ $t('&hellip;') }}</span>
                    <button
                        v-else
                        type="button"
                        @click="goToPage(page)"
                        :class="[
                            'px-3 py-2 rounded-lg text-sm font-medium transition min-w-[36px]',
                            page === currentPage
                                ? 'bg-blue-600 text-white'
                                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        {{ page }}
                    </button>
                </template>
            </div>

            <button
                type="button"
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                :class="[
                    'p-2 rounded-lg text-sm font-medium transition',
                    currentPage === totalPages
                        ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                        : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Per Page Selector -->
            <select
                v-if="showPerPage"
                :value="perPage"
                @change="onPerPageChange"
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white ml-1"
            >
                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        required: true
    },
    totalPages: {
        type: Number,
        required: true
    },
    totalItems: {
        type: Number,
        default: 0
    },
    perPage: {
        type: Number,
        default: 10
    },
    perPageOptions: {
        type: Array,
        default: () => [5, 10, 25, 50, 100]
    },
    showPerPage: {
        type: Boolean,
        default: true
    },
    itemLabel: {
        type: String,
        default: 'results'
    },
    // How many pages to show on either side of the current page
    delta: {
        type: Number,
        default: 2
    }
});

const emit = defineEmits(['update:currentPage', 'update:perPage']);

const rangeStart = computed(() => {
    if (props.totalItems === 0) return 0;
    return (props.currentPage - 1) * props.perPage + 1;
});

const rangeEnd = computed(() => {
    return Math.min(props.currentPage * props.perPage, props.totalItems);
});

const visiblePages = computed(() => {
    const total = props.totalPages;
    const current = props.currentPage;
    const delta = props.delta;
    const range = [];

    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || Math.abs(i - current) <= delta) {
            range.push(i);
        }
    }

    const result = [];
    let prev = 0;
    for (const page of range) {
        if (page - prev > 1) {
            result.push('...');
        }
        result.push(page);
        prev = page;
    }

    return result;
});

const goToPage = (page) => {
    if (page === '...') return;
    if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
        emit('update:currentPage', page);
    }
};

const onPerPageChange = (event) => {
    emit('update:perPage', Number(event.target.value));
};
</script>
