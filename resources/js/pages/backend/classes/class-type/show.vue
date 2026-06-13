<script setup>
import { Card } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';

defineProps({
    show: Boolean,
    classType: Object
});

defineEmits(['close']);

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};
</script>

<template>
    <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 bg-slate-900/15 backdrop-blur-[1px] flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">

                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <button @click="$emit('close')" class="text-sm font-semibold text-slate-500 hover:text-slate-900 transition">← Back</button>
                    <h2 class="font-bold text-slate-800">Class Type Details</h2>
                    <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6">

                    <!-- Overview + Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card class="md:col-span-2 space-y-4">
                            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Overview</h3>
                            <div>
                                <p class="text-xs text-slate-400 font-bold uppercase">Type Name</p>
                                <p class="text-lg font-bold text-slate-900">{{ classType?.type_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-bold uppercase">Description</p>
                                <p class="text-sm text-slate-600 bg-slate-50 p-3 rounded-lg">{{ classType?.description || 'No description provided.' }}</p>
                            </div>
                        </Card>

                        <Card class="space-y-4">
                            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Status</h3>
                            <span
                                :class="classType?.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                class="inline-block px-3 py-1 rounded-full text-xs font-bold"
                            >
                                {{ classType?.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="text-sm text-slate-600 space-y-2">
                                <p><span class="font-bold text-slate-400">Created:</span> {{ formatDate(classType?.created_at) }}</p>
                                <p><span class="font-bold text-slate-400">Updated:</span> {{ formatDate(classType?.updated_at) }}</p>
                            </div>
                        </Card>
                    </div>

                    <!-- Connected Categories -->
                    <Card class="p-0 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-900">Connected Categories</h3>
                        </div>
                        <Table v-if="classType?.class_categories?.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Category Name</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="cat in classType.class_categories"
                                    :key="cat.class_category_id"
                                >
                                    <TableCell class="font-bold">{{ cat.category_name }}</TableCell>
                                    <TableCell class="font-mono text-slate-500">{{ cat.category_code }}</TableCell>
                                    <TableCell>
                                        <span
                                            class="text-xs font-bold"
                                            :class="cat.is_active ? 'text-emerald-600' : 'text-rose-600'"
                                        >
                                            {{ cat.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <div v-else class="p-8 text-center text-slate-400 text-sm">No linked categories found.</div>
                    </Card>

                </div>
            </div>
        </div>
    </Transition>
</template>