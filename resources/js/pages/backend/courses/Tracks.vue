<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ $t('Course') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 font-medium">{{ $t('Tracks') }}</span>
            </nav>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-200 shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $t('Tracks') }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $t('Manage all course tracks in one place') }}</p>
                    </div>
                </div>

                <Link href="/dashboard/course/tracks/create"
                    class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm shadow-blue-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('Add Track') }}
                </Link>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">{{ $t('Total') }}</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ tracks.length }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">{{ $t('Active') }}</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ activeCount }}</p>
                </div>
            </div>

            <!-- Search and Results Count -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-3">
                <div class="w-full sm:max-w-sm">
                    <div class="relative">
                        <input v-model="search" type="text" :placeholder="$t('Search tracks...')"
                            class="w-full rounded-xl border border-slate-200 pl-9 pr-4 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            @input="resetPagination" />
                        <svg class="absolute left-3 top-3 w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tracks Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px]">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5 w-12">
                                    {{ $t('No') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">
                                    {{ $t('Name') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">
                                    {{ $t('Sub Category') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">
                                    {{ $t('Slug') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">
                                    {{ $t('Status') }}</th>
                                <th class="text-right text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">
                                    {{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(track, index) in paginatedTracks" :key="track.id"
                                class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3.5 text-sm text-slate-500">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-sm font-medium text-slate-900">{{ track.name }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-sm text-slate-600">{{ track.sub_category?.name || '-' }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <code class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-md">{{ track.slug }}</code>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium"
                                        :class="track.status === 'active' ? 'text-emerald-700' : 'text-rose-700'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="track.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ track.status === 'active' ? $t('Active') : $t('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/dashboard/course/tracks/${track.id}/edit`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            {{ $t('Edit') }}
                                        </Link>
                                        <button @click="confirmDelete(track)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            {{ $t('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state: no tracks exist at all -->
                            <tr v-if="paginatedTracks.length === 0 && tracks.length === 0">
                                <td colspan="6" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">{{ $t('No tracks yet') }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ $t('Create your first track to start organizing courses') }}</p>
                                    <Link href="/dashboard/course/tracks/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700">
                                        + {{ $t('Add Track') }}
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empty state: search found nothing -->
                            <tr v-else-if="paginatedTracks.length === 0">
                                <td colspan="6" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">{{ $t('No results for ":search"', { search }) }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ $t('Try a different name or clear the search') }}</p>
                                    <button @click="search = ''"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700">
                                        {{ $t('Clear search') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                <Pagination :current-page="currentPage" :total-pages="totalPages" :total-items="filteredTracks.length"
                    :per-page="perPage" item-label="tracks" @update:current-page="goToPage"
                    @update:per-page="onPerPageChange" />
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-[2px]"
            @click.self="showDeleteModal = false">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $t('Delete track') }}</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            {{ $t('Are you sure you want to delete') }} "<span class="font-medium text-slate-900">{{ deleteItem?.name }}</span>"?
                            {{ $t('This action cannot be undone.') }}
                        </p>
                        <p v-if="deleteItem?.courses?.length > 0"
                            class="text-sm text-amber-700 bg-amber-50 rounded-lg px-3 py-2 mt-3">
                            {{ $t('This track has :count course(s) that will also be affected.', { count: deleteItem.courses.length }) }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteTrack"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition">
                        {{ $t('Delete track') }}
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Pagination from './Pagination.vue'

const props = defineProps({
    tracks: { type: Array, default: () => [] }
})

const search = ref('')
const currentPage = ref(1)
const perPage = ref(10)

const showDeleteModal = ref(false)
const deleteItem = ref(null)

// Stats
const activeCount = computed(() =>
    props.tracks.filter(track => track.status === 'active').length
)

const filteredTracks = computed(() => {
    if (!search.value) return props.tracks

    return props.tracks.filter(track =>
        track.name.toLowerCase().includes(search.value.toLowerCase()) ||
        track.sub_category?.name?.toLowerCase().includes(search.value.toLowerCase())
    )
})

const totalPages = computed(() =>
    Math.ceil(filteredTracks.value.length / perPage.value) || 1
)

const paginatedTracks = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredTracks.value.slice(start, start + perPage.value)
})

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

const resetPagination = () => {
    currentPage.value = 1
}

const onPerPageChange = (value) => {
    perPage.value = value
    resetPagination()
}

watch(search, resetPagination)

const confirmDelete = (track) => {
    deleteItem.value = track
    showDeleteModal.value = true
}

const deleteTrack = () => {
    if (!deleteItem.value) return

    router.delete(`/dashboard/course/tracks/${deleteItem.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false
            deleteItem.value = null
        },
        onError: () => {
            alert('Failed to delete track')
        }
    })
}
</script>
