<script setup>
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Eye, Pencil, Trash2 } from "@lucide/vue";

import Table from "../../../../components/ui/table/Table.vue";
import TableHeader from "../../../../components/ui/table/TableHeader.vue";
import TableHead from "../../../../components/ui/table/TableHead.vue";
import TableBody from "../../../../components/ui/table/TableBody.vue";
import TableRow from "../../../../components/ui/table/TableRow.vue";
import TableCell from "../../../../components/ui/table/TableCell.vue";

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

function viewClass(id) {
    router.visit(route("students.show", { class: id }));
}

function editClass(id) {
    router.visit(route("students.edit", { class: id }));
}

function deleteClass(id) {
    if (confirm("Are you sure you want to delete this class? This action cannot be undone.")) {
        router.delete(route("students.destroy", { class: id }), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Lesson</TableHead>
                    <TableHead>Building</TableHead>
                    <TableHead>Room</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Students</TableHead>
                    <TableHead>Time</TableHead>
                    <TableHead class="text-center">Action</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow
                    v-for="item in props.items"
                    :key="item.id"
                >
                    <TableCell>#{{ item.id }}</TableCell>

                    <TableCell class="whitespace-nowrap">
                        <div>
                            <p class="font-semibold">
                                {{ item.title }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ item.term }}
                            </p>
                        </div>
                    </TableCell>

                    <TableCell>
                        {{ item.lesson }}
                    </TableCell>

                    <TableCell>
                        {{ item.building }}
                    </TableCell>

                    <TableCell class="whitespace-nowrap">
                        {{ item.floor }} {{ item.room }}
                    </TableCell>

                    <TableCell>
                        <span
                            :class="[
                                'inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold',
                                item.status === 'active'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : item.status === 'inactive'
                                        ? 'bg-slate-100 text-slate-600'
                                        : 'bg-blue-100 text-blue-700',
                            ]"
                        >
                            {{ item.status }}
                        </span>
                    </TableCell>

                    <TableCell class="tabular-nums">
                        {{ item.students }}/{{ item.capacity }}
                    </TableCell>

                    <TableCell class="whitespace-nowrap">
                        {{ item.time }}
                    </TableCell>

                    <TableCell>
                        <div class="flex justify-center gap-2">
                            <button
                                @click="viewClass(item.id)"
                                class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 cursor-pointer"
                                title="View"
                            >
                                <Eye class="w-4 h-4" />
                            </button>

                            <button
                                @click="editClass(item.id)"
                                class="p-2 rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200 cursor-pointer"
                                title="Edit"
                            >
                                <Pencil class="w-4 h-4" />
                            </button>

                            <button
                                @click="deleteClass(item.id)"
                                class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 cursor-pointer"
                                title="Delete"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </TableCell>
                </TableRow>

                <TableRow v-if="props.items.length === 0">
                    <TableCell colspan="9">
                        <div class="py-10 text-center text-slate-500">
                            No classes found.
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
