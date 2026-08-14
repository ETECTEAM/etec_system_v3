<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { QrcodeCanvas } from "qrcode.vue";
import { useI18n } from "@/i18n";
import {
  MoreVertical,
  Eye,
  SquarePen,
  Copy,
  UserPlus,
  QrCode,
  UserCog,
  CirclePause,
  CircleX,
} from "@lucide/vue";

const props = defineProps({
  classData: Object,
  // "View Class" target. Instructors get their own class page — the enrollment one is admin only.
  viewUrl: {
    type: String,
    default: null,
  },
  // Extra { label, icon, action } entries appended to the menu, e.g. the instructor's Attendance page.
  extraItems: {
    type: Array,
    default: () => [],
  },
  // Labels to leave out, e.g. "Copy Class" on the instructor dashboard.
  hiddenItems: {
    type: Array,
    default: () => [],
  },
});

const open = ref(false);
const showQr = ref(false);
const dropdownRef = ref(null);
const { t } = useI18n();
const qrUrl = computed(() => `${window.location.origin}/dashboard/enroll/${props.classData.id}/students/create`);

function closeDropdown() {
  open.value = false;
}

function handleClickOutside(event) {
  if (open.value && dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown();
  }
}

function handleKeydown(event) {
  if (event.key === "Escape") {
    closeDropdown();
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside, true);
  document.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside, true);
  document.removeEventListener("keydown", handleKeydown);
});

function updateStatus(status) {
  router.post(`/dashboard/enroll/${props.classData.id}/status`, { status }, {
    preserveScroll: true,
    onFinish: () => {
      open.value = false;
    },
  });
}

const menus = computed(() => [
  {
    label: "View Class",
    icon: Eye,
    action: () => router.get(props.viewUrl ?? `/dashboard/enroll/view/${props.classData.id}`),
  },
  {
    label: "Edit Class",
    icon: SquarePen,
    action: () => router.get(`/dashboard/enroll/edit/${props.classData.id}`),
  },
  {
    label: "Copy Class",
    icon: Copy,
    action: () => router.get(`/dashboard/enroll/copy/${props.classData.id}`),
  },
  {
    label: "Add Student",
    icon: UserPlus,
    action: () => router.get(`/dashboard/enroll/${props.classData.id}/students/create`),
  },
  { label: "Generate QR", icon: QrCode, action: () => { showQr.value = true; open.value = false; } },
  { label: "Switch Teacher", icon: UserCog, action: () => window.alert("Switch teacher is not available yet.") },
]
  .filter((item) => !props.hiddenItems.includes(item.label))
  .concat(props.extraItems));

const actions = computed(() => [
  { label: "Pre-End", icon: CirclePause, class: "text-yellow-600", action: () => updateStatus("inactive") },
  { label: "End", icon: CircleX, class: "text-red-600", action: () => updateStatus("completed") },
].filter((item) => !props.hiddenItems.includes(item.label)));
</script>

<template>
  <div ref="dropdownRef" class="relative">
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
        {{ t(item.label) }}
      </button>

      <div v-if="actions.length" class="my-2 border-t dark:border-gray-700"></div>

      <!-- Pre-End & End -->
      <div v-if="actions.length" class="grid grid-cols-2 gap-2 px-3">
        <button
          v-for="item in actions"
          :key="item.label"
          @click="item.action?.()"
          :class="[
            'flex items-center justify-center gap-1 rounded-lg py-2 hover:bg-slate-100 dark:hover:bg-gray-700',
            item.class,
          ]"
        >
          <component :is="item.icon" class="h-4 w-4" />
          {{ t(item.label) }}
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showQr" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/50 px-4" @click.self="showQr = false">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-gray-900">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
            {{ t('Generate QR') }}
          </h3>
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
            {{ classData?.title }}
          </p>

          <div class="mt-5 inline-flex rounded-2xl bg-white p-4 shadow-inner">
            <QrcodeCanvas :value="qrUrl" :size="220" level="H" />
          </div>

          <a :href="qrUrl" target="_blank" class="mt-4 block break-all text-xs text-blue-700 hover:underline dark:text-blue-400">
            {{ qrUrl }}
          </a>

          <button type="button" @click="showQr = false" class="mt-5 w-full rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
            {{ t('Close') }}
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
