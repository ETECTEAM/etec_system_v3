<script setup>
import { computed, nextTick, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, UserPlus } from "@lucide/vue";
import { SelectSearch } from "@/components/ui/select-search";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import ReceiptPrint from "./components/ReceiptPrint.vue";

const props = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
  scheduleGroups: {
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

const scheduleTypeOptions = computed(() =>
  props.scheduleGroups.map((group) => ({
    label: group.class_type_name,
    value: String(group.class_type_id),
  }))
);

const selectedScheduleGroup = computed(() =>
  props.scheduleGroups.find((group) => String(group.class_type_id) === String(selectedScheduleType.value))
);

// Basic class schedules mix the generic Mon & Thu / Sat & Sun receipt term with specific
// day-splits (Mon & Tue, Wed & Thu, ...) the instructor picks later once the class actually
// runs. Registration only needs the generic term, same rule as the Create Class form.
const RECEIPT_ONLY_TERM_NAMES = ["Mon & Thu", "Sat & Sun"];

const scheduleTermOptions = computed(() => {
  const schedules = selectedScheduleGroup.value?.schedules ?? [];
  const restrictToReceiptTerms = selectedScheduleGroup.value?.class_type_name === "Basic";
  const visibleSchedules = restrictToReceiptTerms
    ? schedules.filter((schedule) => RECEIPT_ONLY_TERM_NAMES.includes(schedule.term_name))
    : schedules;

  return visibleSchedules.map((schedule) => ({
    label: schedule.term_name,
    value: String(schedule.term_id),
  }));
});

const selectedSchedule = computed(() =>
  selectedScheduleGroup.value?.schedules.find((schedule) => String(schedule.term_id) === String(selectedTerm.value))
);

const scheduleTimeOptions = computed(() =>
  (selectedSchedule.value?.times ?? []).map((time) => ({
    label: time.time_name,
    value: String(time.id),
  }))
);

function resetTermAndTime() {
  selectedTerm.value = "";
  selectedTime.value = "";
}

const receiptStudent = ref(null);
const receiptClassData = ref(null);
const today = new Date().toLocaleDateString("en-CA");

function submit() {
  form.term_id = selectedTerm.value;
  form.time_id = selectedTime.value;

  form.post("/dashboard/enroll/students", {
    preserveScroll: true,
    onSuccess: async () => {
      receiptClassData.value = {
        course: selectedCourse.value?.title,
        course_price: Number(form.price || 0),
        document_price: Number(form.document_price || 0),
        term: selectedSchedule.value?.term_name,
        time: props.scheduleGroups
          .flatMap((group) => group.schedules)
          .flatMap((schedule) => schedule.times)
          .find((time) => String(time.id) === String(selectedTime.value))?.time_name,
        price: Number(form.price || 0),
      };
      receiptStudent.value = {
        name: form.name,
        gender: form.gender,
        payment_date: today,
        amount_paid: 0,
        fee_amount: Number(form.price || 0),
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
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
              :placeholder="$t('Enter student name')"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
              {{ form.errors.name }}
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
              :placeholder="$t('Select Class Type')"
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
              {{ $t('Price') }}
            </label>
            <input
              v-model="form.price"
              type="number"
              min="0"
              step="0.01"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
              :placeholder="$t('Enter price')"
            />
            <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">
              {{ form.errors.price }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Document Price') }}
            </label>
            <input
              v-model="form.document_price"
              type="number"
              min="0"
              step="0.01"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
              :placeholder="$t('Enter document price')"
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
            :disabled="form.processing"
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
