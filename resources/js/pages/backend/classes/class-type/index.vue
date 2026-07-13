<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import { Breadcrumbs } from "@/components/ui/breadcrumbs";
import { PageHero } from "@/components/ui/page-hero";
import { Card } from "@/components/ui/card";
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from "@/components/ui/table";
import { ActionMenu } from "@/components/ui/menu";
import { Pagination } from "@/components/ui/pagination";

const props = defineProps({
    classTypes: Object,
});

const search = ref("");
const statusFilter = ref("all");

const filteredData = computed(() => {
    const data = (props.classTypes?.data || []).slice().sort((a, b) => {
        return Number(a.class_type_id || 0) - Number(b.class_type_id || 0);
    });

    return data.filter((item) => {
        const matchesSearch = item.type_name
            .toLowerCase()
            .includes(search.value.toLowerCase());
        const matchesStatus =
            statusFilter.value === "all"
                ? true
                : statusFilter.value === "active"
                  ? item.is_active
                  : !item.is_active;

        return matchesSearch && matchesStatus;
    });
});

const handleAction = (action, item) => {
    switch (action) {
        case "view":
            router.visit(`/dashboard/class-types/${item.class_type_id}`);
            break;
        case "edit":
            router.visit(`/dashboard/class-types/${item.class_type_id}/edit`);
            break;
        case "delete":
            if (confirm("Are you sure you want to delete this class type?")) {
                router.delete(`/dashboard/class-types/${item.class_type_id}`, {
                    preserveScroll: true,
                    onSuccess: () => {},
                    onError: (errors) => {
                        console.error("Delete failed", errors);
                        alert(
                            "Could not delete the class type. It may be in use.",
                        );
                    },
                });
            }
            break;
    }
};

const breadcrumbItems = [
    { label: "Dashboard", href: "/dashboard" },
    { label: "Class Types", current: true },
];

const handlePageChange = (page) => {
    router.visit(`/dashboard/class-types?page=${page}`, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Class Types" />
    <DashboardLayout>
        <section class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />

            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <PageHero
                    eyebrow="Management"
                    title="Class Types"
                    description="Manage your class categories."
                />

                <Link
                    href="/dashboard/class-types/create"
                    class="shrink-0 rounded-xl bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    + Add New Class Type
                </Link>
            </div>

            <div
                class="flex gap-4 items-center bg-white p-4 rounded-lg border border-slate-200 dark:bg-gray-900 dark:border-gray-800"
            >
                <input
                    v-model="search"
                    placeholder="Search by name..."
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                />
                <select
                    v-model="statusFilter"
                    class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                >
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <Card class="p-0 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Type Name</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="item in filteredData"
                            :key="item.class_type_id"
                        >
                            <TableCell>#{{ item.class_type_id }}</TableCell>
                            <TableCell class="font-semibold">{{
                                item.type_name
                            }}</TableCell>
                            <TableCell>
                                <span
                                    :class="
                                        item.is_active
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                                            : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300'
                                    "
                                    class="px-2 py-0.5 rounded text-xs font-semibold"
                                >
                                    {{ item.is_active ? "Active" : "Inactive" }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <ActionMenu
                                    :items="[
                                        { key: 'view', label: 'View' },
                                        { key: 'edit', label: 'Edit' },
                                        { key: 'delete', label: 'Delete' },
                                    ]"
                                    @select="
                                        (action) =>
                                            handleAction(action.key, item)
                                    "
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredData.length === 0">
                            <TableCell
                                colspan="4"
                                class="text-center py-8 text-slate-400 dark:text-gray-500"
                                >No class types found.</TableCell
                            >
                        </TableRow>
                    </TableBody>
                </Table>

                <div
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40"
                >
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Showing {{ classTypes.from ?? 0 }}–{{
                            classTypes.to ?? 0
                        }}
                        of {{ classTypes.total ?? 0 }} entries
                    </p>

                    <Pagination
                        v-if="classTypes?.links?.length"
                        :current-page="classTypes.current_page"
                        :last-page="classTypes.last_page"
                        :disabled="false"
                        @page-change="handlePageChange"
                    />
                </div>
            </Card>
        </section>
    </DashboardLayout>
</template>
