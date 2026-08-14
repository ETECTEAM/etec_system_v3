<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { Trash2, Users, X } from "@lucide/vue";
import { SelectSearch } from "@/components/ui/select-search";
import { useI18n } from "@/i18n";

const props = defineProps({
  show: Boolean,
  classData: Object,
});

const emit = defineEmits(["close"]);

const { t } = useI18n();

const loading = ref(false);
const saving = ref(false);
const error = ref("");
const options = ref(null);

const form = ref({
  instructor_id: "",
  instructor_term_id: "",
  instructor_subject: "",
  owner_term_id: "",
  owner_subject: "",
});

// Sharing only splits the days; both instructors keep the class's own time slot.
const classTimeLabel = computed(() => props.classData?.time ?? "-");

// Terms and their time slots come from Schedule Management — the server picks the class
// type whose config carries the day-splits a shared class is divided by.
const schedules = computed(() => options.value?.schedules ?? []);

const termOptions = computed(() =>
  schedules.value.map((schedule) => ({ label: schedule.term_name, value: String(schedule.term_id) })),
);

// The two halves of a shared class can't fall on the same days, so whatever one side
// takes disappears from the other's list.
const ownerTermOptions = computed(() =>
  termOptions.value.filter((option) => option.value !== form.value.instructor_term_id),
);

const instructorTermOptions = computed(() =>
  termOptions.value.filter((option) => option.value !== form.value.owner_term_id),
);

const teacherOptions = computed(() =>
  (options.value?.teachers ?? []).map((teacher) => ({ label: teacher.name, value: String(teacher.id) })),
);

const sharedInstructors = computed(() =>
  (options.value?.shared ?? []).filter((item) => !item.is_owner),
);

const ownerName = computed(() => options.value?.owner?.name ?? "-");

watch(
  () => props.show,
  (open) => {
    if (open) load();
  },
);

async function load() {
  loading.value = true;
  error.value = "";

  try {
    const response = await axios.get(`/dashboard/enroll/${props.classData.id}/instructors`);
    options.value = response.data;

    const owner = (response.data.shared ?? []).find((item) => item.is_owner);

    form.value = {
      instructor_id: "",
      instructor_term_id: "",
      instructor_subject: "",
      owner_term_id: String(owner?.term_id ?? response.data.termId ?? ""),
      owner_subject: owner?.subject ?? "",
    };
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? t("Could not load the class instructors.");
  } finally {
    loading.value = false;
  }
}

function submit() {
  saving.value = true;
  error.value = "";

  router.post(`/dashboard/enroll/${props.classData.id}/instructors`, form.value, {
    preserveScroll: true,
    onSuccess: () => emit("close"),
    onError: (errors) => {
      error.value = Object.values(errors)[0] ?? t("Please fix the errors and try again.");
    },
    onFinish: () => {
      saving.value = false;
    },
  });
}

function removeInstructor(instructorId) {
  router.delete(`/dashboard/enroll/${props.classData.id}/instructors/${instructorId}`, {
    preserveScroll: true,
    onSuccess: () => load(),
  });
}

const selectClass =
  "w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200";
const labelClass = "mb-1.5 block text-xs font-semibold text-slate-600 dark:text-gray-300";
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[110] flex justify-center overflow-y-auto bg-slate-950/50 px-4 py-6"
      @click.self="emit('close')"
    >
      <!-- The overlay scrolls, not the panel: a clipping panel would cut off the search
           dropdowns when a field sits near the bottom. my-auto centres the panel without
           items-center, which would make the top unreachable once the dialog is taller
           than the viewport. -->
      <div class="my-auto w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
              <Users class="h-5 w-5" />
            </span>
            <div>
              <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">
                {{ t('Collapse Class') }}
              </h3>
              <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                {{ t('Share this class with a second instructor, each teaching their own days.') }}
              </p>
              <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-gray-300">
                {{ t('Class time') }}: <span class="text-blue-700 dark:text-blue-400">{{ classTimeLabel }}</span>
              </p>
            </div>
          </div>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800"
            @click="emit('close')"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="space-y-5 px-5 py-5">
          <template>
            <p v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
              {{ error }}
            </p>

            <!-- Already sharing -->
            <div v-if="sharedInstructors.length" class="rounded-xl border border-slate-200 dark:border-gray-800">
              <p class="border-b border-slate-200 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-800 dark:text-gray-400">
                {{ t('Sharing this class') }}
              </p>
              <div
                v-for="item in sharedInstructors"
                :key="item.id"
                class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-gray-800"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-800 dark:text-gray-200">{{ item.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-gray-400">
                    {{ item.subject || t('No subject') }}
                  </p>
                </div>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                  @click="removeInstructor(item.id)"
                >
                  <Trash2 class="h-4 w-4" />
                  {{ t('Remove') }}
                </button>
              </div>
            </div>

            <!-- Owner's half -->
            <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
              <p class="mb-3 text-sm font-semibold text-slate-800 dark:text-gray-200">
                {{ ownerName }} <span class="text-xs font-medium text-slate-500 dark:text-gray-400">({{ t('class owner') }})</span>
              </p>
              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">{{ t('Days') }}</label>
                  <SelectSearch
                    v-model="form.owner_term_id"
                    :options="ownerTermOptions"
                    placeholder="Select days"
                    search-placeholder="Search days..."
                    empty-text="No days configured"
                  />
                </div>
                <div>
                  <label :class="labelClass">{{ t('Teaches') }}</label>
                  <input
                    v-model="form.owner_subject"
                    type="text"
                    :placeholder="t('e.g. Code')"
                    :class="selectClass"
                  />
                </div>
              </div>
            </div>

            <!-- Second instructor's half -->
            <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
              <div class="mb-3">
                <label :class="labelClass">{{ t('Second instructor') }}</label>
                <SelectSearch
                  v-model="form.instructor_id"
                  :options="teacherOptions"
                  placeholder="Select instructor"
                  search-placeholder="Search instructor by name..."
                  empty-text="No other instructors found"
                />
              </div>
              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">{{ t('Days') }}</label>
                  <SelectSearch
                    v-model="form.instructor_term_id"
                    :options="instructorTermOptions"
                    placeholder="Select days"
                    search-placeholder="Search days..."
                    empty-text="No days configured"
                  />
                </div>
                <div>
                  <label :class="labelClass">{{ t('Teaches') }}</label>
                  <input
                    v-model="form.instructor_subject"
                    type="text"
                    :placeholder="t('e.g. Network')"
                    :class="selectClass"
                  />
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-gray-800">
          <button
            type="button"
            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
            @click="emit('close')"
          >
            {{ t('Cancel') }}
          </button>
          <button
            type="button"
            class="rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
            :disabled="loading || saving || !form.instructor_id"
            @click="submit"
          >
            {{ saving ? t('Saving...') : t('Save') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
