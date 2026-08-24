<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { SelectSearch } from "@/components/ui/select-search";
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
const selectedClassId = ref("");
const force = ref(false);
const saving = ref(false);
const errorMessage = ref("");

// Don't offer the class the student is already in.
const classOptions = computed(() =>
  classes.value
    .filter((item) => item.id !== props.enrollment?.class_id)
    .map((item) => ({ value: String(item.id), label: item.label }))
);

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

watch(() => props.show, (value) => {
  if (value) loadClasses();
});

function close() {
  selectedClassId.value = "";
  force.value = false;
  errorMessage.value = "";
  emit("close");
}

async function submit() {
  if (!props.enrollment || !selectedClassId.value) return;

  saving.value = true;
  errorMessage.value = "";

  try {
    await axios.put(`/dashboard/enroll/registrations/${props.enrollment.enrollment_id}/move`, {
      study_class_id: Number(selectedClassId.value),
      force: force.value,
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

// Shown only after the capacity check rejects a normal move, so staff can
// deliberately place the student anyway (e.g. joining a friend's full class).
function submitAnyway() {
  force.value = true;
  submit();
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ isAssign ? $t('Assign to Class') : $t('Move to Another Class') }}</h3>
      <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
        <template v-if="isAssign">{{ enrollment?.name }}</template>
        <template v-else>{{ enrollment?.name }} — {{ $t('currently in') }} "{{ enrollment?.class_title }}"</template>
      </p>

      <div class="mt-4">
        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ isAssign ? $t('Assign to') : $t('Move to') }}</label>
        <SelectSearch
          v-model="selectedClassId"
          :options="classOptions"
          :placeholder="$t('Select class')"
          :search-placeholder="$t('Search class...')"
        />
        <p v-if="errorMessage" class="mt-1 text-xs font-semibold text-red-600">{{ errorMessage }}</p>
        <button
          v-if="errorMessage === 'This class is full.' && canForceCapacity"
          type="button"
          @click="submitAnyway"
          class="mt-2 text-xs font-semibold text-amber-700 underline hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
        >
          {{ isAssign ? $t('Class is full — assign anyway') : $t('Class is full — move the student anyway') }}
        </button>
      </div>

      <div class="mt-6 flex justify-end gap-3">
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
</template>
