<script setup>
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
                        <span class="inline-flex whitespace-nowrap rounded-full bg-indigo-100 text-indigo-700 px-3 py-1 text-xs">
                            {{ item.status }}
                        </span>
                    </TableCell>

                    <TableCell>
                        {{ item.students }}/{{ item.capacity }}
                    </TableCell>

                    <TableCell class="whitespace-nowrap">
                        {{ item.time }}
                    </TableCell>

                    <TableCell>
                        <div class="flex justify-center gap-2">
                            <button
                                class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200"
                            >
                                <Eye class="w-4 h-4" />
                            </button>

                            <button
                                class="p-2 rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200"
                            >
                                <Pencil class="w-4 h-4" />
                            </button>

                            <button
                                class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200"
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