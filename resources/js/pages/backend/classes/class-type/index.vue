<script setup>
import { ref, computed } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { PageHero } from '@/components/ui/page-hero';
import { Card } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { ActionMenu } from '@/components/ui/menu';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';

import CreateModal from './create.vue';
import EditModal from './edit.vue';
import ShowModal from './show.vue';
import DeleteModal from './delete.vue';

const props = defineProps({
    classTypes: Object
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isShowOpen = ref(false);
const isDeleteOpen = ref(false);
const activeItem = ref(null);

// Search & filter state
const search = ref('');
const statusFilter = ref('all'); // 'all' | 'active' | 'inactive'

const filteredTypes = computed(() => {
    if (!props.classTypes?.data) return [];

    return props.classTypes.data.filter(item => {
        const matchesSearch =
            search.value.trim() === '' ||
            item.type_name?.toLowerCase().includes(search.value.toLowerCase()) ||
            item.description?.toLowerCase().includes(search.value.toLowerCase());

        const matchesStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'active' && item.is_active) ||
            (statusFilter.value === 'inactive' && !item.is_active);

        return matchesSearch && matchesStatus;
    });
});

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
};

const hasActiveFilters = computed(() =>
    search.value.trim() !== '' || statusFilter.value !== 'all'
);

const handleAction = (action, item) => {
    activeItem.value = item;
    if (action === 'view') isShowOpen.value = true;
    if (action === 'edit') isEditOpen.value = true;
    if (action === 'delete') isDeleteOpen.value = true;
};

const getActions = () => [
    { key: 'view', label: 'View Details' },
    { key: 'edit', label: 'Edit' },
    { key: 'delete', label: 'Delete' }
];
</script>

<template>
    <DashboardLayout>
        <div class="p-8 space-y-6 max-w-6xl mx-auto">

            <Breadcrumbs :items="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Class Types' }]" />

            <div class="flex justify-between items-end">
                <PageHero
                    title="Class Types"
                    description="Manage and structure your educational class categories."
                />
                <button
                    @click="isCreateOpen = true"
                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition"
                >
                    Add New Type
                </button>
            </div>

            <!-- Search & Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">

                <!-- Search input -->
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or description…"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                        aria-label="Clear search"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Status filter -->
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 shrink-0">
                    <button
                        v-for="opt in [{ value: 'all', label: 'All' }, { value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }]"
                        :key="opt.value"
                        @click="statusFilter = opt.value"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-md transition',
                            statusFilter === opt.value
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        {{ opt.label }}
                    </button>
                </div>

                <!-- Clear filters -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition shrink-0"
                >
                    Clear filters
                </button>
            </div>

            <!-- Result count -->
            <p v-if="hasActiveFilters" class="text-xs text-slate-400 -mt-2">
                Showing {{ filteredTypes.length }} of {{ classTypes?.data?.length ?? 0 }} results
            </p>

            <Card padding="p-0 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Type Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="item in filteredTypes"
                            :key="item.class_type_id"
                        >
                            <TableCell class="text-slate-500 font-mono text-xs">#{{ item.class_type_id }}</TableCell>
                            <TableCell class="font-semibold text-slate-900">{{ item.type_name }}</TableCell>
                            <TableCell class="text-slate-500 max-w-xs truncate">{{ item.description || '-' }}</TableCell>
                            <TableCell>
                                <span
                                    :class="item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold"
                                >
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <ActionMenu
                                    :items="getActions()"
                                    @select="(action) => handleAction(action.key, item)"
                                />
                            </TableCell>
                        </TableRow>

                        <!-- Empty state -->
                        <TableRow v-if="filteredTypes.length === 0">
                            <TableCell colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                <span v-if="hasActiveFilters">
                                    No results for <span class="font-semibold text-slate-600">"{{ search }}"</span>.
                                    <button @click="clearFilters" class="ml-1 text-indigo-600 hover:underline font-semibold">Clear filters</button>
                                </span>
                                <span v-else>No class types found.</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <CreateModal :show="isCreateOpen" @close="isCreateOpen = false" />
            <EditModal :show="isEditOpen" :classType="activeItem" @close="isEditOpen = false" />
            <ShowModal :show="isShowOpen" :classType="activeItem" @close="isShowOpen = false" />
            <DeleteModal :show="isDeleteOpen" :classType="activeItem" @close="isDeleteOpen = false" />
        </div>
    </DashboardLayout>
</template>