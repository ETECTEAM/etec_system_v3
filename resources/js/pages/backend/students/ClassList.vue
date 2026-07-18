<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Search, RotateCcw, Plus, LayoutGrid, Table2 } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import ClassCrad from "../../../components/ui/card/ClassCrad.vue";
import ClassTable from "./components/ClassTable.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

const props = defineProps({
    classes: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const viewMode = ref("card");

const breadcrumbItems = [
    { label: "Dashboard", href: "/dashboard" },
    { label: "Class List", current: true },
];

function refresh() {
    search.value = "";
    router.visit(route("students.index"), { preserveState: true });
}

function goCreateClass() {
    router.visit(route("students.create"));
}

function onSearch() {
    router.visit(route("students.index", { search: search.value || null }), {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <DashboardLayout>
        <div class="w-full">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                <div>
                    <Breadcrumbs :items="breadcrumbItems" />
                    <PageHero eyebrow="EnRoll Management" title="Class List" />
                    <p class="text-gray-500">Manage all classes</p>
                </div>

                <button
                    @click="goCreateClass"
                    class="bg-indigo-600 text-white px-5 py-3 rounded-lg flex items-center gap-2 cursor-pointer self-start sm:self-auto whitespace-nowrap"
                >
                    <Plus class="w-4 h-4" /> Add Class
                </button>
            </div>

            <div class="bg-white rounded-xl shadow p-3 sm:p-4 flex flex-col sm:flex-row gap-3 mb-6">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        class="w-full border rounded-lg py-3 pl-4 pr-12"
                        placeholder="Search classes..."
                        @keyup.enter="onSearch"
                    />
                    <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-5" />
                </div>

                <div class="flex gap-2 sm:gap-3">
                    <button
                        @click="refresh"
                        class="bg-gray-700 text-white px-4 sm:px-5 rounded-lg flex items-center gap-2 whitespace-nowrap"
                    >
                        <RotateCcw class="w-4 h-4" /> Reset
                    </button>

                    <button
                        @click="viewMode = 'card'"
                        :class="[
                            'px-3 sm:px-4 py-3 rounded-lg border flex items-center gap-2 whitespace-nowrap',
                            viewMode === 'card' ? 'bg-indigo-600 text-white' : 'bg-white',
                        ]"
                    >
                        <LayoutGrid class="w-4 h-4" /> Card
                    </button>

                    <button
                        @click="viewMode = 'table'"
                        :class="[
                            'px-3 sm:px-4 py-3 rounded-lg border flex items-center gap-2 whitespace-nowrap',
                            viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-white',
                        ]"
                    >
                        <Table2 class="w-4 h-4" /> Table
                    </button>
                </div>
            </div>

            <div v-if="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-5">
                <ClassCrad
                    v-for="item in classes.data"
                    :key="item.id"
                    :classData="item"
                    :count="0"
                />
            </div>

            <div v-else class="w-full overflow-x-auto">
                <ClassTable :items="classes.data" />
            </div>

            <div v-if="classes.total > classes.per_page" class="mt-6 flex justify-center">
                <component
                    :is="'div'"
                    v-for="(link, i) in classes.links"
                    :key="i"
                    class="inline-flex"
                >
                    <button
                        v-if="link.url"
                        @click="router.visit(link.url, { preserveState: true, preserveScroll: true })"
                        :class="[
                            'px-3 py-2 text-sm border',
                            link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50',
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="px-3 py-2 text-sm border border-slate-200 text-slate-400 bg-slate-50"
                        v-html="link.label"
                    />
                </component>
            </div>
        </div>
    </DashboardLayout>
</template>
