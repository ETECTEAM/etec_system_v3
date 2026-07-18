<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
  MoreVertical,
  Eye,
  SquarePen,
  UserPlus,
  QrCode,
  UserCog,
  CirclePause,
  CircleX,
} from "@lucide/vue";

const props = defineProps({
  classData: Object,
});

const open = ref(false);

const menus = [
  {
    label: "View Class",
    icon: Eye,
    action: () => router.get(`/dashboard/students/view/${props.classData.id}`),
  },
  {
    label: "Edit Class",
    icon: SquarePen,
    action: () => router.get(`/dashboard/students/edit/${props.classData.id}`),
  },
  {
    label: "Add Student",
    icon: UserPlus,
    action: () => router.get(`/dashboard/students/${props.classData.id}/students/create`),
  },
  { label: "Generate QR", icon: QrCode },
  { label: "Switch Teacher", icon: UserCog },
];

const actions = [
  { label: "Pre-End", icon: CirclePause, class: "text-yellow-600" },
  { label: "End", icon: CircleX, class: "text-red-600" },
];
</script>

<template>
  <div class="relative">
    <button
      @click="open = !open"
      class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
    >
      <MoreVertical class="h-5 w-5" />
    </button>

    <div v-if="open" class="absolute right-0 z-50 mt-2 w-56 rounded-xl shadow-2xl bg-white py-2 dark:bg-gray-800 dark:ring-1 dark:ring-gray-700">
      <!-- Normal menu -->
      <button
        v-for="item in menus"
        :key="item.label"
        @click="item.action?.()"
        class="flex w-full items-center gap-3 px-4 py-3 text-left text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700"
      >
        <component :is="item.icon" class="h-4 w-4" />
        {{ item.label }}
      </button>

      <div class="my-2 border-t dark:border-gray-700"></div>

      <!-- Pre-End & End -->
      <div class="grid grid-cols-2 gap-2 px-3">
        <button
          v-for="item in actions"
          :key="item.label"
          :class="[
            'flex items-center justify-center gap-1 rounded-lg py-2 hover:bg-slate-100 dark:hover:bg-gray-700',
            item.class,
          ]"
        >
          <component :is="item.icon" class="h-4 w-4" />
          {{ item.label }}
        </button>
      </div>
    </div>
  </div>
</template>
