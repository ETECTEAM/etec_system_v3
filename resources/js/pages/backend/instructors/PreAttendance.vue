<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ArrowRight, ClipboardCheck, Clock3, Send, Users } from "@lucide/vue";
import { useToast } from "vue-toastification";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import { getEcho } from "../../../echo";

const props = defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
});

const requestTarget = ref(null);
const requestReason = ref("");
const requestError = ref("");
const classRows = ref(props.classes.map((classData) => ({ ...classData })));
const toast = useToast();
let attendanceChannels = [];

watch(
  () => props.classes,
  (classes) => {
    classRows.value = classes.map((classData) => ({ ...classData }));
  },
);

// Sum of still-untracked students across every listed class — powers the
// "N unresolved" badge in the table header.
const totalUnresolvedStudents = computed(() =>
  classRows.value.reduce(
    (sum, classData) => sum + Math.max(0, (classData.students ?? 0) - (classData.tracked_count ?? 0)),
    0,
  ),
);

function handleRequestUpdated(payload) {
  if (payload?.status !== "approved") {
    return;
  }

  classRows.value = classRows.value.map((classData) => {
    if (Number(classData.id) !== Number(payload.study_class_id)) {
      return classData;
    }

    return {
      ...classData,
      request_status: payload.status,
      request_status_label: payload.status_label ?? "Approved",
      can_request_retrack: false,
      can_retrack: true,
    };
  });

  toast.success("Pre-attendance approved. You can re-track now.");
}

function openRequest(classData) {
  requestTarget.value = classData;
  requestReason.value = "";
  requestError.value = "";
}

function closeRequest() {
  requestTarget.value = null;
  requestReason.value = "";
  requestError.value = "";
}

function submitRequest() {
  const reason = requestReason.value.trim();

  if (reason.length < 3) {
    requestError.value = "Please input reason why.";
    return;
  }

  router.post(`/dashboard/instructor/pre-attendance/classes/${requestTarget.value.id}/request`, { note: reason }, {
    preserveScroll: true,
    onSuccess: closeRequest,
  });
}

onMounted(() => {
  const echo = getEcho();

  if (!echo) {
    return;
  }

  attendanceChannels = classRows.value.map((classData) =>
    echo
      .private(`attendance.class.${classData.id}`)
      .listen(".pre-attendance.request-updated", handleRequestUpdated),
  );
});

onBeforeUnmount(() => {
  attendanceChannels.forEach((channel) => {
    channel?.stopListening(".pre-attendance.request-updated", handleRequestUpdated);
  });
  attendanceChannels = [];
});
</script>

<template>
  <Head :title="$t('Pre Att')" />

  <DashboardLayout>
    <section class="space-y-5 sm:space-y-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">
            {{ $t("Attendance Recovery") }}
          </p>
          <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">
            {{ $t("Pre-attendance classes") }}
          </h1>
          <p class="mt-1 max-w-3xl text-sm font-medium text-slate-500 dark:text-gray-400">
            {{ $t("Review classes that still have pre-attendance students and re-track attendance for the missing students.") }}
          </p>
        </div>

      </div>

      <div v-if="classRows.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Classes needing re-track") }}</h2>
              <p class="mt-1 text-sm font-medium text-slate-500 dark:text-gray-400">
                {{ $t("Each row shows the class, unresolved students, and the action to continue attendance recovery.") }}
              </p>
            </div>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
              {{ $t(":count unresolved", { count: totalUnresolvedStudents }) }}
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[1100px] w-full border-collapse text-sm">
            <thead>
              <tr class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">#</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Class") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Time") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Schedule") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Students") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Action") }}</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(classData, index) in classRows"
                :key="classData.id"
                class="align-top transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50"
              >
                <td class="border-b border-slate-100 px-5 py-5 font-black text-slate-500 dark:border-gray-800 dark:text-gray-400">
                  {{ index + 1 }}
                </td>
                <td class="border-b border-slate-100 px-5 py-5 dark:border-gray-800">
                  <h3 class="mt-3 text-base font-black text-slate-950 dark:text-gray-100">
                    {{ classData.title }}
                  </h3>
                </td>
                <td class="border-b border-slate-100 px-5 py-5 font-bold text-slate-700 dark:border-gray-800 dark:text-gray-200">
                  <div class="flex items-center gap-2">
                    <Clock3 class="h-4 w-4 text-blue-600 dark:text-blue-300" />
                    {{ classData.time }}
                  </div>
                </td>
                <td class="border-b border-slate-100 px-5 py-5 font-bold text-slate-700 dark:border-gray-800 dark:text-gray-200">
                  {{ classData.term }}
                </td>
                <td class="border-b border-slate-100 px-5 py-5 dark:border-gray-800">
                  <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-gray-100">
                    <Users class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                    {{ $t(":tracked tracked / :total total", { tracked: classData.tracked_count, total: classData.students }) }}
                  </div>
                </td>
                <td class="border-b border-slate-100 px-5 py-5 dark:border-gray-800">
                  <div class="flex flex-col items-start gap-3">
                    <Link
                      v-if="classData.can_retrack"
                      :href="`/dashboard/instructor/classes/${classData.id}/attendance/track?from=pre-attendance`"
                      class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-900 px-4 text-sm font-bold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                      {{ $t("Re-Track") }}
                      <ArrowRight class="h-4 w-4" />
                    </Link>
                    <button
                      v-else-if="classData.can_request_retrack"
                      type="button"
                      class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-600 px-4 text-sm font-bold text-white transition hover:bg-amber-500"
                      @click="openRequest(classData)"
                    >
                      {{ $t("Request Admin") }}
                      <Send class="h-4 w-4" />
                    </button>
                    <span
                      v-else
                      class="inline-flex h-10 items-center rounded-lg bg-slate-100 px-4 text-sm font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                    >
                      {{ $t(classData.request_status_label ?? "Closed") }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 dark:bg-gray-800">
          <ClipboardCheck class="h-6 w-6 text-slate-400 dark:text-gray-500" />
        </div>
        <h2 class="mt-4 text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("No pre-attendance classes") }}</h2>
        <p class="mt-2 text-sm font-medium text-slate-500 dark:text-gray-400">
          {{ $t("Classes with unresolved pre-attendance students will appear here after the grace period ends.") }}
        </p>
      </div>

      <div v-if="requestTarget" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 px-4">
        <div class="w-full max-w-lg rounded-lg border border-slate-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-gray-900">
          <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Request pre-att approval") }}</h2>
          <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">{{ requestTarget.title }}</p>

          <label class="mt-5 block text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-gray-400">{{ $t("Reason") }}</label>
          <textarea
            v-model="requestReason"
            rows="4"
            class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-amber-500/20"
            :placeholder="$t('Input reason why you need to re-track')"
          />
          <p v-if="requestError" class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-300">{{ $t(requestError) }}</p>

          <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="h-10 rounded-lg border border-slate-200 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeRequest">
              {{ $t("Cancel") }}
            </button>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-600 px-4 text-sm font-bold text-white hover:bg-amber-500" @click="submitRequest">
              {{ $t("Submit Request") }}
              <Send class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
