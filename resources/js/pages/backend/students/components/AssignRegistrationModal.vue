<script setup>
import { onBeforeUnmount, ref, watch } from "vue";
import axios from "axios";
import { X, Search, UserRound, UserPlus } from "@lucide/vue";
import { useToast } from "@/composables/useToast";
import { useI18n } from "@/i18n";

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
  show: { type: Boolean, default: false },
  classId: { type: [Number, String], default: null },
  classTitle: { type: String, default: "" },
  seatsLeft: { type: Number, default: null },
});

const emit = defineEmits(["close", "assigned"]);

const query = ref("");
const rows = ref([]);
const loading = ref(false);
const searched = ref(false);
const assigningId = ref(null);

let timer = null;
let reqId = 0;

const TYPE_BADGE = {
  manual: "bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400",
  vip: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400",
  normal: "bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300",
};

async function fetchRows(term) {
  if (!props.classId) return;
  const current = ++reqId;
  loading.value = true;

  try {
    const response = await axios.get(`/dashboard/enroll/${props.classId}/assignable-registrations`, {
      params: { search: term || null },
    });
    if (current !== reqId) return;
    rows.value = response.data?.data ?? [];
    searched.value = true;
  } catch (error) {
    if (current !== reqId) return;
    console.error("Failed to load assignable registrations", error);
    toast.error(error.response?.data?.message ?? t("Failed to load registrations."));
  } finally {
    if (current === reqId) loading.value = false;
  }
}

watch(
  () => props.show,
  (open) => {
    if (open) {
      query.value = "";
      rows.value = [];
      searched.value = false;
      fetchRows("");
    }
  },
);

watch(query, (value) => {
  if (timer) clearTimeout(timer);
  timer = setTimeout(() => fetchRows(value.trim()), 350);
});

onBeforeUnmount(() => timer && clearTimeout(timer));

async function assign(row) {
  if (assigningId.value) return;
  assigningId.value = row.enrollment_id;

  try {
    await axios.post(`/dashboard/enroll/${props.classId}/assign-registration`, {
      enrollment_id: row.enrollment_id,
    });
    toast.success(t(":name added to :class.", { name: row.name, class: props.classTitle }));
    rows.value = rows.value.filter((r) => r.enrollment_id !== row.enrollment_id);
    emit("assigned", row);
  } catch (error) {
    toast.error(error.response?.data?.message ?? t("Failed to add this student."));
  } finally {
    assigningId.value = null;
  }
}

function close() {
  emit("close");
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/50 px-4" @click.self="close">
    <div class="flex max-h-[85vh] w-full max-w-lg flex-col rounded-2xl bg-white shadow-xl dark:bg-gray-900">
      <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 dark:border-gray-800">
        <div class="flex items-center gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-500/10">
            <UserPlus class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
          </span>
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Add Existing Student') }}</h3>
            <p class="text-xs text-slate-500 dark:text-gray-400">
              {{ classTitle }}
              <span v-if="seatsLeft !== null"> &middot; {{ seatsLeft }} {{ $t('seats left') }}</span>
            </p>
          </div>
        </div>
        <button type="button" :aria-label="$t('Close')" class="-mr-2 -mt-1 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200" @click="close">
          <X class="h-5 w-5" />
        </button>
      </div>

      <div class="p-5">
        <div class="relative">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="query"
            type="text"
            :placeholder="$t('Search by name, phone or course')"
            class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
          />
        </div>
        <p class="mt-1.5 text-[11px] text-slate-400 dark:text-gray-500">
          {{ $t('Students who registered but are not in a class yet.') }}
        </p>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5">
        <div v-if="loading" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-16 animate-pulse rounded-xl bg-slate-100 dark:bg-gray-800" />
        </div>

        <p v-else-if="searched && rows.length === 0" class="rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 dark:bg-gray-800 dark:text-gray-400">
          {{ query ? $t('No matching unassigned registrations.') : $t('No unassigned registrations right now.') }}
        </p>

        <ul v-else class="space-y-2">
          <li
            v-for="row in rows"
            :key="row.enrollment_id"
            class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-gray-800"
          >
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400">
              <UserRound class="h-4 w-4" />
            </span>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5">
                <span class="truncate text-sm font-semibold text-slate-900 dark:text-gray-100">{{ row.name }}</span>
                <span :class="['shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold uppercase', TYPE_BADGE[row.registration_type] || TYPE_BADGE.normal]">
                  {{ $t(row.registration_type) }}
                </span>
              </div>
              <p class="truncate text-xs text-slate-500 dark:text-gray-400">
                {{ row.phone }}<span v-if="row.course_title"> &middot; {{ row.course_title }}</span>
              </p>
              <p class="text-[11px] text-slate-400 dark:text-gray-500">
                <span v-if="row.term_name">{{ row.term_name }} &middot; </span>{{ row.payment_status }} · ${{ row.amount_paid.toFixed(2) }}
              </p>
            </div>
            <button
              type="button"
              :disabled="assigningId === row.enrollment_id || (seatsLeft !== null && seatsLeft <= 0)"
              class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-blue-900 px-3 text-xs font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
              @click="assign(row)"
            >
              {{ assigningId === row.enrollment_id ? $t('Adding...') : $t('Add') }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
