<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { Search } from "@lucide/vue";
import { useI18n } from "@/i18n";

const page = usePage();
const toast = useToast();
const { t } = useI18n();
const canForceCapacity = computed(() => {
  const roles = page.props.auth?.roles ?? [];
  return roles.includes("super_admin") || roles.includes("admin");
});

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  enrollment: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close", "moved"]);

// A registration RegisterStudentForSchedule parked with no class at all
// (no room/instructor free at the time) reaches this same modal with
// class_id null - MoveStudentEnrollment assigns it in place instead of
// moving it, so only the copy/labels differ here.
const isAssign = computed(() => !props.enrollment?.class_id);

const classes = ref([]);
const classesLoaded = ref(false);
const classesLoading = ref(false);
const search = ref("");
const selectedClassId = ref("");
const forceOverride = ref(false);
const saving = ref(false);
const errorMessage = ref("");

// Don't offer the class the student is already in.
const selectableClasses = computed(() =>
  classes.value.filter((item) => item.id !== props.enrollment?.class_id)
);

const filteredClasses = computed(() => {
  const query = search.value.trim().toLowerCase();
  if (!query) return selectableClasses.value;

  return selectableClasses.value.filter((item) =>
    [item.course, item.title, item.term, item.time, item.teacher].some((field) =>
      String(field ?? "").toLowerCase().includes(query)
    )
  );
});

const selectedClass = computed(
  () =>
    selectableClasses.value.find((item) => String(item.id) === String(selectedClassId.value)) ?? null
);

// Admins may deliberately pick a full class; such a move is submitted with
// force so MoveStudentEnrollment skips its capacity check.
const force = computed(() => Boolean(selectedClass.value?.is_full));

async function loadClasses() {
  if (classesLoaded.value) return;

  classesLoading.value = true;

  try {
    const response = await axios.get("/dashboard/enroll/classes/select");
    classes.value = response.data?.data ?? [];
    classesLoaded.value = true;
  } catch (error) {
    console.error("Failed to fetch classes", error);
  } finally {
    classesLoading.value = false;
  }
}

watch(
  () => props.show,
  (value) => {
    if (value) loadClasses();
  }
);

watch(selectedClassId, () => {
  forceOverride.value = false;
  errorMessage.value = "";
});

function close() {
  selectedClassId.value = "";
  search.value = "";
  forceOverride.value = false;
  errorMessage.value = "";
  emit("close");
}

function isSelected(item) {
  return String(item.id) === String(selectedClassId.value);
}

function isLocked(item) {
  return Boolean(item.is_full) && !canForceCapacity.value;
}

function selectClass(item) {
  if (isLocked(item)) return;
  selectedClassId.value = isSelected(item) ? "" : String(item.id);
}

function rowClasses(item) {
  if (isSelected(item)) {
    return item.is_full
      ? "cursor-pointer bg-amber-50 dark:bg-amber-900/30"
      : "cursor-pointer bg-blue-50 dark:bg-blue-900/40";
  }

  return isLocked(item)
    ? "cursor-not-allowed bg-white opacity-60 dark:bg-gray-900"
    : "cursor-pointer bg-white hover:bg-slate-50 dark:bg-gray-900 dark:hover:bg-gray-800/60";
}

async function submit() {
  if (!props.enrollment || !selectedClassId.value) return;

  saving.value = true;
  errorMessage.value = "";

  try {
    await axios.put(`/dashboard/enroll/registrations/${props.enrollment.enrollment_id}/move`, {
      study_class_id: Number(selectedClassId.value),
      force: force.value || forceOverride.value,
    });
    toast.success(isAssign.value ? t('Student assigned to the class.') : t('Student moved successfully.'));
    emit("moved");
    close();
  } catch (error) {
    const errors = error.response?.data?.errors ?? {};
    errorMessage.value = errors.student_id?.[0] ?? errors.study_class_id?.[0] ?? "Failed to move student.";
  } finally {
    saving.value = false;
  }
}

// Shown only after the capacity check rejects a normal move (e.g. the class
// filled up after this list loaded), so staff can deliberately place the
// student anyway.
function submitAnyway() {
  forceOverride.value = true;
  submit();
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
    <div class="flex h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-900">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ isAssign ? $t('Assign to Class') : $t('Move to Another Class') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
          <template v-if="isAssign">{{ enrollment?.name }}</template>
          <template v-else>{{ enrollment?.name }} — {{ $t('currently in') }} "{{ enrollment?.class_title }}"</template>
        </p>
      </div>

      <div class="border-b border-slate-200 bg-slate-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800/60">
        <div class="relative">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Search by course, schedule, time slot or teacher...')"
            class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
          />
        </div>
        <p class="mt-2 text-xs text-slate-500 dark:text-gray-400">
          {{ filteredClasses.length }} / {{ selectableClasses.length }} {{ $t('classes') }}
        </p>
      </div>

      <div class="flex-1 overflow-y-auto">
        <table class="w-full text-left text-sm">
          <thead class="sticky top-0 z-10 bg-slate-100 text-xs uppercase tracking-wide text-slate-500 dark:bg-gray-800 dark:text-gray-400">
            <tr>
              <th class="w-16 px-4 py-3 font-semibold">{{ $t('Select') }}</th>
              <th class="px-4 py-3 font-semibold">{{ $t('Course Title') }}</th>
              <th class="px-4 py-3 font-semibold">{{ $t('Term / Schedule') }}</th>
              <th class="px-4 py-3 font-semibold">{{ $t('Time Slot') }}</th>
              <th class="px-4 py-3 font-semibold">{{ $t('Teacher') }}</th>
              <th class="px-6 py-3 font-semibold">{{ $t('Capacity') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
            <tr v-if="classesLoading">
              <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">{{ $t('Loading classes...') }}</td>
            </tr>
            <tr v-else-if="filteredClasses.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">{{ $t('No classes match your search.') }}</td>
            </tr>
            <tr
              v-for="item in filteredClasses"
              v-else
              :key="item.id"
              :class="rowClasses(item)"
              @click="selectClass(item)"
            >
              <td class="px-4 py-3">
                <input
                  type="radio"
                  name="move-class-radio"
                  class="h-4 w-4 accent-blue-900 dark:accent-blue-500"
                  :checked="isSelected(item)"
                  :disabled="isLocked(item)"
                  @click.prevent="selectClass(item)"
                />
              </td>
              <td class="px-4 py-3">
                <p class="font-semibold text-slate-900 dark:text-gray-100">{{ item.course }}</p>
                <p class="text-xs text-slate-500 dark:text-gray-400">{{ item.title }}</p>
              </td>
              <td class="px-4 py-3 text-slate-700 dark:text-gray-300">{{ item.term }}</td>
              <td class="px-4 py-3 text-slate-700 dark:text-gray-300">{{ item.time }}</td>
              <td class="px-4 py-3 text-slate-700 dark:text-gray-300">{{ item.teacher }}</td>
              <td class="whitespace-nowrap px-6 py-3">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="item.is_full
                    ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'"
                >
                  {{ item.current_students }}/{{ item.capacity }} enrolled
                </span>
                <span
                  v-if="item.is_full"
                  class="ml-1 inline-flex items-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold uppercase text-white"
                >
                  {{ $t('Full') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-end justify-between gap-3 border-t border-slate-200 px-6 py-4 dark:border-gray-700">
        <div class="min-h-5 text-xs">
          <p v-if="errorMessage" class="font-semibold text-red-600">{{ errorMessage }}</p>
          <button
            v-if="errorMessage === 'This class is full.' && canForceCapacity"
            type="button"
            @click="submitAnyway"
            class="font-semibold text-amber-700 underline hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
          >
            {{ isAssign ? $t('Class is full — assign anyway') : $t('Class is full — move the student anyway') }}
          </button>
          <p v-else-if="selectedClass?.is_full && canForceCapacity" class="font-semibold text-amber-700 dark:text-amber-400">
            {{ $t('This class is full — the move will override its capacity.') }}
          </p>
        </div>

        <div class="flex shrink-0 gap-3">
          <button type="button" @click="close" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            {{ $t('Cancel') }}
          </button>
          <button
            type="button"
            @click="submit"
            :disabled="saving || !selectedClassId"
            class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            {{ isAssign ? $t('Assign Student') : $t('Move Student') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
