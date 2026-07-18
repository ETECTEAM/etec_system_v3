<script setup>
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Eye, Pencil, Trash2 } from "@lucide/vue";
// import { router } from "@inertiajs/vue3";

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

const viewClass = (id) => {
  router.get(`/dashboard/students/view/${id}`);
};

const editClass = (id) => {
  router.get(`/dashboard/students/edit/${id}`);
};

const deleteClass = (id) => {
  if (confirm("Are you sure you want to delete this class?")) {
    router.delete(`/dashboard/students/${id}`);
  }
};
</script>

<template>
  <div class="bg-white rounded-xl shadow overflow-hidden dark:bg-gray-900">
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
        <TableRow v-for="item in props.items" :key="item.id">
          <TableCell>#{{ item.id }}</TableCell>

          <TableCell class="whitespace-nowrap">
            <div>
              <p class="font-semibold">
                {{ item.title }}
              </p>

              <p class="text-xs text-slate-500 dark:text-gray-400">
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
              class="inline-flex whitespace-nowrap rounded-full bg-indigo-100 text-indigo-700 px-3 py-1 text-xs dark:bg-indigo-500/10 dark:text-indigo-400"
            >
              {{ item.status }}
            </span>
          </TableCell>

          <TableCell> {{ item.students }}/{{ item.capacity }} </TableCell>

          <TableCell class="whitespace-nowrap">
            {{ item.time }}
          </TableCell>

          <TableCell>
            <div class="flex justify-center gap-2">
              <button
                @click="viewClass(item.id)"
                class="rounded-lg bg-blue-100 p-2 text-blue-600 transition hover:bg-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
              >
                <Eye class="h-4 w-4" />
              </button>

              <button
                @click="editClass(item.id)"
                class="rounded-lg bg-yellow-100 p-2 text-yellow-600 transition hover:bg-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20"
              >
                <Pencil class="h-4 w-4" />
              </button>

              <button
                @click="deleteClass(item.id)"
                class="rounded-lg bg-red-100 p-2 text-red-600 transition hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>
          </TableCell>
        </TableRow>

        <TableRow v-if="props.items.length === 0">
          <TableCell colspan="9">
            <div class="py-10 text-center text-slate-500 dark:text-gray-400">
              No classes found.
            </div>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
