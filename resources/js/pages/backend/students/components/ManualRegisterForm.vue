<script setup>
import axios from "axios";
import { computed, nextTick, reactive, ref, watch } from "vue";
import { CreditCard, Printer, Save, X } from "@lucide/vue";
import { latinNameError } from "@/composables/useLatinNameValidation";
import { useToast } from "@/composables/useToast";
import { useI18n } from "@/i18n";
import ReceiptPrint from "./ReceiptPrint.vue";
import SelectSearch from "@/components/ui/select-search/SelectSearch.vue";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

const { t } = useI18n();
const toast = useToast();
const { fetchRegistrations } = useEnrollmentRegistrations();

const props = defineProps({
  // Started classes (GetClassList::presentClass) — fallback source for the
  // Term / Time pickers when full option lists aren't provided.
  classes: {
    type: Array,
    default: () => [],
  },
  // { courses, instructors, terms, times, rooms } — full lists from the server.
  options: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["cancel"]);

function toOptions(values) {
  return [...new Set(values.filter(Boolean))]
    .sort((a, b) => String(a).localeCompare(String(b)))
    .map((value) => ({ value, label: value }));
}

// Prefer the full server list; fall back to values seen on the loaded classes.
function optionList(serverList, classValues) {
  return toOptions(serverList && serverList.length ? serverList : classValues);
}

// Fixed term / days vocabulary — the only schedules ETEC runs.
const TERM_DAYS = ["Mon & Thu", "Sat & Sun", "Sunday", "Saturday"];

const courseOptions = computed(() => optionList(props.options.courses, props.classes.map((c) => c.course)));
const termOptions = computed(() => TERM_DAYS.map((v) => ({ value: v, label: v })));

// --- Time (native <input type="time">, admin picks any time) ---
function toMinutes(value) {
  if (!value) return null;
  const [h, m] = value.split(":").map(Number);
  return h * 60 + m;
}
function to12h(value) {
  if (!value) return "";
  const [h, m] = value.split(":").map(Number);
  return `${String(((h + 11) % 12) + 1).padStart(2, "0")}:${String(m).padStart(2, "0")} ${h >= 12 ? "PM" : "AM"}`;
}
function hhmm(mins) {
  // Clamp to end-of-day — a class time never rolls past midnight.
  const total = Math.max(0, Math.min(mins, 23 * 60 + 59));
  return `${String(Math.floor(total / 60)).padStart(2, "0")}:${String(total % 60).padStart(2, "0")}`;
}
const durationLabel = computed(() => {
  const s = toMinutes(form.time_start);
  const e = toMinutes(form.time_end);
  if (s == null || e == null || e <= s) return "";
  const h = Math.floor((e - s) / 60);
  const m = (e - s) % 60;
  return [h ? `${h}h` : "", m ? `${m}m` : ""].filter(Boolean).join(" ");
});
const timeRange = computed(() =>
  form.time_start && form.time_end ? `${to12h(form.time_start)} - ${to12h(form.time_end)}` : "",
);

// SelectSearch trigger styling, red-bordered when the field has an error.
const triggerBase =
  "flex w-full items-center justify-between rounded-xl border bg-white px-4 py-2.5 text-left text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-100 dark:bg-gray-800 dark:focus:ring-blue-500/20";
function triggerClass(hasError) {
  return `${triggerBase} ${
    hasError
      ? "border-red-300 focus:border-red-500"
      : "border-slate-300 focus:border-blue-600 dark:border-gray-600 dark:focus:border-blue-500"
  }`;
}

const genderOptions = [
  { value: "male", label: "Male" },
  { value: "female", label: "Female" },
];
const methodOptions = ["Cash", "ABA", "Bank Transfer", "Wing", "Other"].map((m) => ({ value: m, label: m }));

const form = reactive(blankForm());
const errors = reactive({});
const saving = ref(false);

// Start time drives the end: default to +1h when end is blank, otherwise keep
// whatever duration the admin already picked (3h etc.) as the start moves.
watch(
  () => form.time_start,
  (start, prevStart) => {
    if (!start) return;
    const startMin = toMinutes(start);
    if (!form.time_end) {
      form.time_end = hhmm(startMin + 60);
      return;
    }
    const prevMin = toMinutes(prevStart);
    if (prevMin == null) return;
    const shifted = toMinutes(form.time_end) + (startMin - prevMin);
    if (shifted > startMin) form.time_end = hhmm(shifted);
  },
);

function blankForm() {
  return {
    full_name: "",
    khmer_name: "",
    phone: "",
    gender: "",
    course: "",
    term: "",
    time_start: "",
    time_end: "",
    amount_paid: "",
    discount: "",
    document_price: "5",
    payment_method: "Cash",
    payment_date: new Date().toISOString().slice(0, 10),
    note: "",
  };
}

const nameLiveError = computed(() => latinNameError(form.full_name));

function normalizePhone(event) {
  form.phone = String(event.target.value ?? "").replace(/\D+/g, "").slice(0, 12);
}

function validate() {
  Object.keys(errors).forEach((key) => delete errors[key]);

  if (!form.full_name.trim()) errors.full_name = t("Full name is required.");
  else if (nameLiveError.value) errors.full_name = nameLiveError.value;
  if (!form.phone.trim()) errors.phone = t("Phone number is required.");
  if (!form.gender) errors.gender = t("Gender is required.");
  if (!form.course) errors.course = t("Course is required.");
  if (form.amount_paid === "" || Number(form.amount_paid) < 0) errors.amount_paid = t("Amount paid is required.");

  return Object.keys(errors).length === 0;
}

function resetForm() {
  Object.assign(form, blankForm());
  Object.keys(errors).forEach((key) => delete errors[key]);
}

// --- Receipt (reuses ReceiptPrint.vue) ---
const receiptClassData = ref(null);
const receiptStudent = ref(null);

function buildReceipt() {
  receiptClassData.value = {
    course: form.course,
    price: form.amount_paid,
    unit_price: null,
    document_price: form.document_price,
    term: form.term || "-",
    time: timeRange.value || "-",
    teacher: "-",
    building: "",
    floor: "",
    room: "-",
    enroll_start_date: form.payment_date,
  };
  receiptStudent.value = {
    name: form.full_name,
    gender: form.gender,
    payment_date: form.payment_date,
    amount_paid: form.amount_paid,
    fee_amount: form.amount_paid,
    document_fee_amount: form.document_price,
    enrollment_id: null,
    public_token: null,
  };
}

async function submit({ print }) {
  if (saving.value) return;
  if (!validate()) {
    toast.error(t("Please fix the highlighted fields."));
    return;
  }

  saving.value = true;
  const payload = { ...form, time: timeRange.value };

  try {
    await axios.post("/dashboard/enroll/manual-registrations", payload);
    toast.success(t("Registration saved successfully."));
    fetchRegistrations(1); // reflect the new row in the Registrations tab
    if (print) {
      buildReceipt();
      await nextTick();
      window.print();
    } else {
      resetForm();
    }
  } catch (error) {
    const status = error?.response?.status;
    // Always surface the real failure for debugging — status, server body, payload.
    console.error(
      "[Manual Register] save failed:",
      status ?? "(no server response)",
      error?.response?.data ?? error?.message ?? error,
      { url: "/dashboard/enroll/manual-registrations", payload },
    );

    if (status === 422) {
      Object.assign(errors, error.response.data.errors ?? {});
      toast.error(error.response.data.message ?? t("Please fix the highlighted fields."));
    } else if (status === 404 || status === 405) {
      // Endpoint not wired yet — still let staff print the receipt from what
      // they entered.
      if (print) {
        buildReceipt();
        await nextTick();
        window.print();
        toast.info(t("Receipt printed. The save endpoint is not connected yet."));
      } else {
        toast.info(t("Captured. The save endpoint is not connected yet."));
      }
    } else {
      const detail = status ? `HTTP ${status}` : t("no server response");
      toast.error(`${error?.response?.data?.message ?? t("Failed to save registration.")} (${detail})`);
    }
  } finally {
    saving.value = false;
  }
}

const labelClass = "mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300";
const controlClass =
  "w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20";
const errorClass = "border-red-300 focus:border-red-500 focus:ring-red-100 dark:border-red-500/60";
</script>

<template>
  <form
    class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
    @submit.prevent="submit({ print: false })"
  >
    <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-gray-800">
      <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400">
        <CreditCard class="h-5 w-5" />
      </span>
      <div>
        <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Manual Register') }}</h2>
        <p class="text-xs text-slate-500 dark:text-gray-400">
          {{ $t('Manually record an existing/old student registration and print a receipt.') }}
        </p>
      </div>
    </div>

    <div class="space-y-8 p-5">
      <!-- Student Information -->
      <section>
        <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
          {{ $t('Student Information') }}
        </h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <label :class="labelClass">{{ $t('Full Name') }} <span class="text-red-500">*</span></label>
            <input v-model="form.full_name" type="text" :class="[controlClass, (errors.full_name || nameLiveError) && errorClass]" :placeholder="$t('Enter full name')" />
            <p v-if="errors.full_name" class="mt-1 text-xs text-red-600">{{ errors.full_name }}</p>
          </div>
          <!-- <div>
            <label :class="labelClass">{{ $t('Khmer Name') }}</label>
            <input v-model="form.khmer_name" type="text" :class="controlClass" :placeholder="$t('ឈ្មោះជាភាសាខ្មែរ')" />
          </div> -->
          <div>
            <label :class="labelClass">{{ $t('Phone Number') }} <span class="text-red-500">*</span></label>
            <input v-model="form.phone" type="text" inputmode="numeric" maxlength="12" :class="[controlClass, errors.phone && errorClass]" :placeholder="$t('Enter phone number')" @input="normalizePhone" />
            <p v-if="errors.phone" class="mt-1 text-xs text-red-600">{{ errors.phone }}</p>
          </div>
          <div>
            <label :class="labelClass">{{ $t('Gender') }} <span class="text-red-500">*</span></label>
            <SelectSearch v-model="form.gender" :options="genderOptions" placeholder="Select gender" :button-class="triggerClass(!!errors.gender)" />
            <p v-if="errors.gender" class="mt-1 text-xs text-red-600">{{ errors.gender }}</p>
          </div>
        </div>
      </section>

      <!-- Course Information -->
      <section>
        <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
          {{ $t('Course Information') }}
        </h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <label :class="labelClass">{{ $t('Course') }} <span class="text-red-500">*</span></label>
            <SelectSearch v-model="form.course" :options="courseOptions" placeholder="Select a course" empty-text="No courses" :button-class="triggerClass(!!errors.course)" />
            <p v-if="errors.course" class="mt-1 text-xs text-red-600">{{ errors.course }}</p>
          </div>
          <div>
            <label :class="labelClass">{{ $t('Term / Days') }}</label>
            <SelectSearch v-model="form.term" :options="termOptions" placeholder="Select term / days" empty-text="No terms" :button-class="triggerClass(false)" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Time') }} <span class="text-[11px] font-normal text-slate-400">({{ $t('start – end') }})</span></label>
            <div class="flex items-center gap-2">
              <input v-model="form.time_start" type="time" step="900" :class="[controlClass, '!px-2.5', errors.time && errorClass]" :aria-label="$t('Start Time')" />
              <span class="shrink-0 text-slate-400">→</span>
              <input v-model="form.time_end" type="time" step="900" :class="[controlClass, '!px-2.5', errors.time && errorClass]" :aria-label="$t('End Time')" />
            </div>
            <p v-if="errors.time" class="mt-1 text-xs text-red-600">{{ errors.time }}</p>
            <p v-else-if="durationLabel" class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ $t('Duration') }}: {{ durationLabel }}</p>
          </div>
        </div>
      </section>

      <!-- Payment Information -->
      <section>
        <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
          {{ $t('Payment Information') }}
        </h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <label :class="labelClass">{{ $t('Amount Paid') }} <span class="text-red-500">*</span></label>
            <input v-model="form.amount_paid" type="number" min="0" step="0.01" :class="[controlClass, errors.amount_paid && errorClass]" placeholder="0.00" />
            <p v-if="errors.amount_paid" class="mt-1 text-xs text-red-600">{{ errors.amount_paid }}</p>
          </div>
          <div>
            <label :class="labelClass">{{ $t('Discount') }}</label>
            <input v-model="form.discount" type="number" min="0" step="0.01" :class="controlClass" placeholder="0.00" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Document Price') }}</label>
            <input v-model="form.document_price" type="number" min="0" step="0.01" :class="controlClass" placeholder="0.00" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Payment Method') }}</label>
            <SelectSearch v-model="form.payment_method" :options="methodOptions" :clearable="false" placeholder="Select method" :button-class="triggerClass(false)" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Payment Date') }}</label>
            <input v-model="form.payment_date" type="date" :class="controlClass" />
          </div>
        </div>
      </section>

      <!-- Note -->
      <section>
        <label :class="labelClass">{{ $t('Note') }}</label>
        <textarea v-model="form.note" rows="3" :class="controlClass" :placeholder="$t('Optional note')" />
      </section>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:justify-end">
      <button
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
        @click="emit('cancel')"
      >
        <X class="h-4 w-4" />
        {{ $t('Cancel') }}
      </button>
      <button
        type="submit"
        :disabled="saving"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 disabled:opacity-60 dark:bg-gray-900 dark:text-blue-400 dark:hover:bg-gray-800"
      >
        <Save class="h-4 w-4" />
        {{ saving ? $t('Saving...') : $t('Save') }}
      </button>
      <button
        type="button"
        :disabled="saving"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
        @click="submit({ print: true })"
      >
        <Printer class="h-4 w-4" />
        {{ saving ? $t('Saving...') : $t('Save & Print Receipt') }}
      </button>
    </div>
  </form>

  <ReceiptPrint :class-data="receiptClassData" :student="receiptStudent" hide-class-info />
</template>
