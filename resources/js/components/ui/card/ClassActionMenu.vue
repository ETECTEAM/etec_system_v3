<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { QrcodeCanvas } from "qrcode.vue";
import { useI18n } from "@/i18n";
import { useConfirm } from "@/composables/useConfirm";
import {
  MoreVertical,
  SquarePen,
  Copy,
  UserPlus,
  UserCheck,
  QrCode,
  UserCog,
  CirclePause,
  CircleX,
  X,
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

const emit = defineEmits(["register-student", "assign-registration"]);

const open = ref(false);
const showQr = ref(false);
const dropdownRef = ref(null);
const { t } = useI18n();
const { confirm } = useConfirm();
const lifecycleStatus = computed(() => String(props.classData?.class_status ?? "").toLowerCase());
const normalizedLifecycleStatus = computed(() => {
  switch (lifecycleStatus.value) {
    case "inactive":
      return "pre_end";
    case "completed":
      return "ended";
    default:
      return lifecycleStatus.value;
  }
});
const lockedStudentActions = computed(() => ['pre_end', 'ended', 'cancelled'].includes(normalizedLifecycleStatus.value));
const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);
const isAdminUser = computed(() => roles.value.includes("super_admin") || roles.value.includes("admin"));
const isInstructor = computed(() => roles.value.includes("instructor") && !isAdminUser.value);
const qrUrl = computed(() => `${window.location.origin}/join-class/${props.classData.slug ?? props.classData.id}`);
const qrCopied = ref(false);

function copyQrUrl() {
  navigator.clipboard?.writeText(qrUrl.value).then(() => {
    qrCopied.value = true;
    setTimeout(() => { qrCopied.value = false; }, 1500);
  });
}

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

function updateStatus(status, onSuccess = null) {
  router.post(`/dashboard/enroll/${props.classData.id}/status`, { status }, {
    preserveScroll: true,
    onSuccess: () => {
      onSuccess?.();
    },
    onFinish: () => {
      open.value = false;
    },
  });
}

function onMenuItemClick(item) {
  if (item.disabled) {
    return;
  }

  item.action?.();
}

const menus = computed(() => [
  {
    label: "Edit Class",
    icon: SquarePen,
    action: () => router.get(`/dashboard/enroll/edit/${props.classData.id}`),
  },
  {
    label: "Register Student",
    icon: UserPlus,
    action: () => { emit("register-student"); open.value = false; },
    disabled: lockedStudentActions.value,
  },
  {
    label: "Add Existing Student",
    icon: UserCheck,
    action: () => { emit("assign-registration"); open.value = false; },
    disabled: lockedStudentActions.value,
  },
  {
    label: "Generate QR",
    icon: QrCode,
    action: () => { showQr.value = true; open.value = false; },
    disabled: lockedStudentActions.value,
  },
  {
    label: "Copy Class",
    icon: Copy,
    action: () => router.get(`/dashboard/enroll/copy/${props.classData.id}`),
  },
  { label: "Switch Teacher", icon: UserCog, action: () => window.alert("Switch teacher is not available yet.") },
]
  .filter((item) => !props.hiddenItems.includes(item.label))
  .filter((item) => !isAdminUser.value || item.label !== "Generate QR")
  .concat(props.extraItems.filter((item) => !isAdminUser.value || item.label !== "Collapse Class")));

const actions = computed(() => [
  { label: "Pre-End", icon: CirclePause, textClass: "text-amber-700 dark:text-amber-400", iconClass: "text-amber-500 dark:text-amber-400", action: async () => {
    const ok = await confirm({
      title: t("Pre-End Class?"),
      message: t("This will lock attendance tracking and prevent new students from joining. Are you sure you want to pre-end this class?"),
      confirmText: t("Pre-End"),
      cancelText: t("Cancel"),
      danger: true,
    });
    if (!ok) return;
    updateStatus("inactive");
  }},
  { label: "End", icon: CircleX, textClass: "text-red-600 dark:text-red-400", iconClass: "text-red-500 dark:text-red-400", action: async () => {
    const ok = await confirm({
      title: t("End Class?"),
      message: t("This will permanently end the class and lock all activity. Are you sure you want to end this class?"),
      confirmText: t("End"),
      cancelText: t("Cancel"),
      danger: true,
    });
    if (!ok) return;
    updateStatus("completed", () => {
      if (isInstructor.value) {
        window.location.href = `/dashboard/instructor/classes/${props.classData.id}/result?download=1`;
      }
    });
  }},
].filter((item) => {
  if (props.hiddenItems.includes(item.label)) {
    return false;
  }

  if (!isInstructor.value && ["Pre-End", "End"].includes(item.label)) {
    return false;
  }

  if (item.label === "Pre-End") {
    return normalizedLifecycleStatus.value !== "pre_end" && normalizedLifecycleStatus.value !== "ended";
  }

  if (item.label === "End") {
    return normalizedLifecycleStatus.value !== "ended";
  }

  return true;
}));
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <button
      @click="open = !open"
      class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
    >
      <MoreVertical class="h-5 w-5" />
    </button>

    <div v-if="open" class="absolute right-0 z-[60] mt-1 w-52 rounded-xl border border-slate-200 bg-white py-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-800">
      <!-- Normal actions -->
      <button
        v-for="item in menus"
        :key="item.label"
        @click="onMenuItemClick(item)"
        :disabled="item.disabled"
        :class="[
          'flex w-full items-center gap-3 px-3 py-2 text-left text-sm transition-colors',
          item.disabled
            ? 'cursor-not-allowed text-slate-400 dark:text-gray-500'
            : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700',
        ]"
      >
        <span class="flex w-5 shrink-0 justify-center">
          <component :is="item.icon" class="h-4 w-4" />
        </span>
        {{ t(item.label) }}
      </button>

      <div v-if="actions.length" class="mx-3 my-1.5 border-t border-slate-200 dark:border-gray-700"></div>

      <!-- Lifecycle actions: Pre-End (warning) and End (destructive) -->
      <button
        v-for="item in actions"
        :key="item.label"
        @click="item.action?.()"
        class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm transition-colors hover:bg-slate-50 dark:hover:bg-gray-700"
      >
        <span class="flex w-5 shrink-0 justify-center">
          <component :is="item.icon" :class="['h-4 w-4', item.iconClass]" />
        </span>
        <span :class="['font-medium', item.textClass]">{{ t(`${item.label} Class`) }}</span>
      </button>
    </div>

    <Teleport to="body">
      <div v-if="showQr" class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4" @click.self="showQr = false">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900/90">
          <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-white px-5 py-4 dark:border-gray-800 dark:bg-gray-900/90">
            <div class="min-w-0">
              <p class="text-[11px] font-medium uppercase tracking-widest text-slate-400 dark:text-gray-500">{{ t('Scan to join') }}</p>
              <p class="truncate text-base font-semibold text-slate-900 dark:text-gray-100">{{ classData?.title }}</p>
            </div>
            <button type="button" :aria-label="t('Close')" class="-mr-1.5 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200" @click="showQr = false">
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="flex flex-col items-center px-6 py-6">
            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-gray-700">
              <QrcodeCanvas
                :value="qrUrl"
                :size="360"
                level="M"
                :margin="0"
                foreground="#1e3a8a"
                class="block h-[360px] w-[360px] max-w-full"
              />
            </div>
            <div class="mt-4 flex w-full flex-col items-center gap-2">
              <a :href="qrUrl" target="_blank" rel="noopener" class="block max-w-full truncate text-[11px] text-blue-600 hover:underline dark:text-blue-400">
                {{ qrUrl }}
              </a>
              <button type="button" @click="copyQrUrl" class="rounded-lg border border-slate-200 px-3 py-1 text-[11px] font-medium text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                {{ qrCopied ? t('Copied') : t('Copy') }}
              </button>
            </div>
          </div>

          <!-- <div class="border-t border-slate-100 p-4 dark:border-gray-800">
            <button type="button" @click="showQr = false" class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
              {{ t('Close') }}
            </button>
          </div> -->
        </div>
      </div>
    </Teleport>
  </div>
</template>
