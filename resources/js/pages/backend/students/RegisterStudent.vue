<script setup>
import { computed, nextTick, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, UserPlus } from "@lucide/vue";
import { SelectSearch } from "@/components/ui/select-search";
import { latinNameError } from "@/composables/useLatinNameValidation";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import ReceiptPrint from "./components/ReceiptPrint.vue";

const props = defineProps({
  // Each course carries its own `class_schedules` (only the Enroll Config slots
  // toggled ON) - see EnrollmentClassController::createRegisteredStudent.
  courses: {
    type: Array,
    default: () => [],
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/enroll" },
  { label: "Register Student", current: true },
];

const selectClass =
  "flex w-full items-center justify-between rounded-xl border border-slate-300 px-4 py-3 text-left text-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20 dark:disabled:bg-gray-900 dark:disabled:text-gray-500";

const selectedScheduleType = ref("");
const selectedTerm = ref("");
const selectedTime = ref("");

const form = useForm({
  name: "",
  gender: "",
  phone: "",
  course_id: "",
  term_id: "",
  time_id: "",
  price: "",
  unit_price: "",
  document_price: "",
});

const genderOptions = [
  { label: "Male", value: "male" },
  { label: "Female", value: "female" },
];

const courseOptions = computed(() =>
  props.courses.map((course) => ({ label: course.title, value: String(course.id) }))
);

const selectedCourse = computed(() =>
  props.courses.find((course) => String(course.id) === String(form.course_id))
);

const totalFee = computed(() => Math.round((Number(form.price || 0) + Number(form.document_price || 0)) * 100) / 100);
const nameLiveError = computed(() => latinNameError(form.name));

function normalizePhoneInput(event) {
  const value = event.target.value ?? "";
  form.phone = String(value).replace(/\D+/g, "").slice(0, 12);
}

// Class Type -> Term -> Time, all driven by the picked course's own enabled
// Enroll Config slots.
const courseSchedules = computed(() => selectedCourse.value?.class_schedules ?? []);

const scheduleTypeOptions = computed(() =>
  courseSchedules.value.map((group) => ({
    label: group.class_type_name,
    value: String(group.class_type_id),
  }))
);

const selectedScheduleGroup = computed(() =>
  courseSchedules.value.find((group) => String(group.class_type_id) === String(selectedScheduleType.value))
);

const scheduleTermOptions = computed(() =>
  (selectedScheduleGroup.value?.terms ?? []).map((term) => ({
    label: term.term_name,
    value: String(term.term_id),
  }))
);

const selectedSchedule = computed(() =>
  (selectedScheduleGroup.value?.terms ?? []).find((term) => String(term.term_id) === String(selectedTerm.value))
);

const scheduleTimeOptions = computed(() =>
  (selectedSchedule.value?.times ?? []).map((time) => ({
    label: time.time_name,
    value: String(time.time_id),
  }))
);

// Changing the course invalidates the schedule cascade and refills the
// config-driven price fields.
watch(() => form.course_id, () => {
  selectedScheduleType.value = "";
  selectedTerm.value = "";
  selectedTime.value = "";
  form.price = selectedCourse.value ? String(selectedCourse.value.price ?? 0) : "";
  form.unit_price = selectedCourse.value ? String(selectedCourse.value.unit_price ?? selectedCourse.value.price ?? 0) : "";
  form.document_price = selectedCourse.value ? String(selectedCourse.value.document_price ?? 0) : "";
});

function resetTermAndTime() {
  selectedTerm.value = "";
  selectedTime.value = "";
}

const receiptStudent = ref(null);
const receiptClassData = ref(null);
const today = new Date().toLocaleDateString("en-CA");

function submit() {
  if (nameLiveError.value) {
    return;
  }

  form.term_id = selectedTerm.value;
  form.time_id = selectedTime.value;

  form.post("/dashboard/enroll/students", {
    preserveScroll: true,
    onSuccess: async () => {
      receiptClassData.value = {
        course: selectedCourse.value?.title,
        // `price` is the charged fee (Course Price); unit_price rides along for
        // the receipt's reference breakdown.
        price: Number(form.price || 0),
        unit_price: selectedCourse.value?.unit_price ?? null,
        course_price: selectedCourse.value?.course_price ?? Number(form.price || 0),
        document_price: Number(form.document_price || 0),
        term: selectedSchedule.value?.term_name,
        time: (selectedSchedule.value?.times ?? [])
          .find((time) => String(time.time_id) === String(selectedTime.value))?.time_name,
      };
      receiptStudent.value = {
        name: form.name,
        gender: form.gender,
        payment_date: today,
        amount_paid: 0,
        fee_amount: Number(form.price || 0),
        unit_price: Number(form.unit_price || 0),
        document_fee_amount: Number(form.document_price || 0),
      };

      form.reset("name", "gender", "phone");

      await nextTick();
      window.print();
    },
  });
}

function classList() {
  router.get("/dashboard/enroll");
}
</script>

<template>
  <DashboardLayout>
  <div class="w-full">
    <div class="space-y-4 sm:space-y-5">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero :title="$t('Register Student')" />

      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-500/10">
            <UserPlus class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
          </div>
          <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-gray-100">
              {{ $t('Register New Student') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
              {{ $t('No class assigned yet — enroll them into a class later.') }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            @click="classList"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
          >
            <ArrowLeft class="h-4 w-4" />
            {{ $t('Class List') }}
          </button>
        </div>
      </div>

      <form
        @submit.prevent="submit"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
      >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Student Name') }}
            </label>
            <input
              v-model="form.name"
              type="text"
              :class="[
                'w-full rounded-xl border px-4 py-3 text-sm outline-none focus:ring-2 dark:bg-gray-800 dark:text-gray-200',
                nameLiveError ? 'border-red-300 focus:border-red-500 focus:ring-red-100 dark:border-red-500/60' : 'border-slate-300 focus:border-indigo-400 focus:ring-indigo-100 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20',
              ]"
              :placeholder="$t('Enter student name')"
            />
            <p v-if="nameLiveError || form.errors.name" class="mt-1 text-xs text-red-600">
              {{ nameLiveError || form.errors.name }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Gender') }}
            </label>
            <SelectSearch v-model="form.gender" :options="genderOptions" :placeholder="$t('Select gender')" :button-class="selectClass" />
            <p v-if="form.errors.gender" class="mt-1 text-xs text-red-600">
              {{ form.errors.gender }}
            </p>
          </div>

          <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                Phone
              </label>
              <input
                v-model="form.phone"
                type="text"
                inputmode="numeric"
                autocomplete="tel"
                pattern="[0-9]*"
                maxlength="12"
                @input="normalizePhoneInput"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                :placeholder="$t('Enter phone number')"
              />
            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
              {{ form.errors.phone }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Course') }}
            </label>
            <SelectSearch v-model="form.course_id" :options="courseOptions" :placeholder="$t('Select Course')" :button-class="selectClass" />
            <p v-if="form.errors.course_id" class="mt-1 text-xs text-red-600">
              {{ form.errors.course_id }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Class Type') }}
            </label>
            <SelectSearch
              v-model="selectedScheduleType"
              :options="scheduleTypeOptions"
              :disabled="!form.course_id"
              :placeholder="$t(form.course_id ? 'Select Class Type' : 'Select Course first')"
              :button-class="selectClass"
              @update:model-value="resetTermAndTime"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Term') }}
            </label>
            <SelectSearch
              v-model="selectedTerm"
              :options="scheduleTermOptions"
              :disabled="!selectedScheduleType"
              :placeholder="$t(selectedScheduleType ? 'Select Term' : 'Select Class Type first')"
              :button-class="selectClass"
              @update:model-value="selectedTime = ''"
            />
            <p v-if="form.errors.term_id" class="mt-1 text-xs text-red-600">
              {{ form.errors.term_id }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Time') }}
            </label>
            <SelectSearch
              v-model="selectedTime"
              :options="scheduleTimeOptions"
              :disabled="!selectedTerm"
              :placeholder="$t(selectedTerm ? 'Select Time' : 'Select Term first')"
              :button-class="selectClass"
            />
            <p v-if="form.errors.time_id" class="mt-1 text-xs text-red-600">
              {{ form.errors.time_id }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Unit Price') }}
              <span class="font-normal text-slate-400 dark:text-gray-500">· {{ $t('from Enroll Config') }}</span>
            </label>
            <input
              :value="form.unit_price"
              type="number"
              readonly
              class="w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600 outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400"
              :placeholder="$t('Select a course')"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Course Price') }}
              <span class="font-normal text-slate-400 dark:text-gray-500">· {{ $t('from Enroll Config') }}</span>
            </label>
            <input
              :value="form.price"
              type="number"
              readonly
              class="w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600 outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400"
              :placeholder="$t('Select a course')"
            />
            <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">
              {{ form.errors.price }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Document Price') }}
              <span class="font-normal text-slate-400 dark:text-gray-500">· {{ $t('from Enroll Config') }}</span>
            </label>
            <input
              :value="form.document_price"
              type="number"
              readonly
              class="w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600 outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400"
              :placeholder="$t('Select a course')"
            />
            <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
              {{ $t('Total') }} = {{ totalFee.toFixed(2) }}$
            </p>
            <p v-if="form.errors.document_price" class="mt-1 text-xs text-red-600">
              {{ form.errors.document_price }}
            </p>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <button
              type="submit"
              :disabled="form.processing || !!nameLiveError"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <Save class="h-4 w-4" />
            {{ form.processing ? $t("Saving...") : $t("Save & Print Receipt") }}
          </button>
        </div>
      </form>
    </div>

    <ReceiptPrint :classData="receiptClassData" :student="receiptStudent" />
  </div>
  </DashboardLayout>
</template>
