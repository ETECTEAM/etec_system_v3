<template>
    <DashboardLayout>
        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Categories</h1>
                    <p class="text-sm text-slate-500">Manage course categories</p>
                </div>
                <Link
                    href="/dashboard/course/categories/create"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 whitespace-nowrap w-full sm:w-auto justify-center"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Category
                </Link>
            </div>

            <!-- Search and Results Count -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                <div class="w-full sm:max-w-sm">
                    <div class="relative">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search categories..."
                            class="w-full rounded-lg border border-slate-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @input="resetPagination"
                        />
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <span class="text-sm text-slate-500 whitespace-nowrap">
                    Showing <strong class="text-slate-700">{{ paginatedCategories.length }}</strong> of 
                    <strong class="text-slate-700">{{ filteredCategories.length }}</strong> categories
                </span>
            </div>

            <!-- Categories Table - Full Width -->
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-3 sm:px-4">#</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-3 sm:px-4">Name</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-3 sm:px-4">Slug</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-3 sm:px-4">Sub Categories</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-3 sm:px-4">Status</th>
                                <th class="text-right text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-3 sm:px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="category in paginatedCategories" :key="category.id" class="hover:bg-slate-50 transition">
                                <td class="px-3 py-3 text-sm text-slate-600 sm:px-4">#{{ category.id }}</td>
                                <td class="px-3 py-3 sm:px-4">
                                    <span class="text-sm font-medium text-slate-900">{{ category.name }}</span>
                                </td>
                                <td class="px-3 py-3 sm:px-4">
                                    <code class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">{{ category.slug }}</code>
                                </td>
                                <td class="px-3 py-3 sm:px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ category.sub_categories?.length || 0 }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 sm:px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            category.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="category.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></span>
                                        {{ category.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right sm:px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="`/dashboard/course/categories/${category.id}/edit`"
                                            class="text-blue-600 hover:text-blue-800 transition p-1.5 rounded-lg hover:bg-blue-50"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <button 
                                            @click="confirmDelete(category)" 
                                            class="text-red-600 hover:text-red-800 transition p-1.5 rounded-lg hover:bg-red-50" 
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paginatedCategories.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <p>No categories found</p>
                                    <p class="text-xs mt-1">Try adjusting your search or add a new category</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredCategories.length > 0" class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-slate-500 text-center sm:text-left">
                    Showing 
                    <span class="font-medium text-slate-700">{{ (currentPage - 1) * perPage + 1 }}</span> 
                    to 
                    <span class="font-medium text-slate-700">{{ Math.min(currentPage * perPage, filteredCategories.length) }}</span> 
                    of 
                    <span class="font-medium text-slate-700">{{ filteredCategories.length }}</span> 
                    results
                </div>
                
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <button
                        @click="prevPage"
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
                        <button
                            v-for="page in visiblePages"
                            :key="page"
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
                    </div>
                    
                    <button
                        @click="nextPage"
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
                        v-model="perPage"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white ml-1"
                        @change="resetPagination"
                    >
                        <option :value="5">5</option>
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showDeleteModal = false">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Delete Category</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            Are you sure you want to delete "<span class="font-medium text-slate-900">{{ deleteItem?.name }}</span>"? 
                            This action cannot be undone.
                        </p>
                        <p v-if="deleteItem?.sub_categories?.length > 0" class="text-sm text-amber-600 mt-2">
                            ⚠️ This category has {{ deleteItem.sub_categories.length }} sub-category(ies) that will also be deleted.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition">
                        Cancel
                    </button>
                    <button @click="deleteCategory" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                        Delete Category
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

// Search and filters
const search = ref('');

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Delete modal
const showDeleteModal = ref(false);
const deleteItem = ref(null);

// Filtered categories
const filteredCategories = computed(() => {
    if (!search.value) return props.categories;
    return props.categories.filter(cat =>
        cat.name.toLowerCase().includes(search.value.toLowerCase()) ||
        cat.slug.toLowerCase().includes(search.value.toLowerCase())
    );
});

// Total pages
const totalPages = computed(() => {
    return Math.ceil(filteredCategories.value.length / perPage.value) || 1;
});

// Paginated categories
const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredCategories.value.slice(start, end);
});

// Visible page numbers
const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;
    const range = [];
    
    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            Math.abs(i - current) <= delta
        ) {
            range.push(i);
        }
    }
    
    // Add ellipsis
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

// Navigation methods
const goToPage = (page) => {
    if (page === '...') return;
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const resetPagination = () => {
    currentPage.value = 1;
};

// Reset to page 1 when search changes
watch(search, () => {
    resetPagination();
});

// Confirm delete
const confirmDelete = (category) => {
    deleteItem.value = category;
    showDeleteModal.value = true;
};

const deleteCategory = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/course/categories/${deleteItem.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
            onError: (errors) => {
                alert(errors.message || 'Failed to delete category');
            }
        });
    }
};
</script>