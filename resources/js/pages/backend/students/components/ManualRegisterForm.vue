<script setup>
import { computed, nextTick, reactive, ref } from "vue";
import { CreditCard, Printer, Save, X } from "@lucide/vue";
import { latinNameError } from "@/composables/useLatinNameValidation";
import { useToast } from "@/composables/useToast";
import { useI18n } from "@/i18n";
import ReceiptPrint from "./ReceiptPrint.vue";
import SelectSearch from "@/components/ui/select-search/SelectSearch.vue";

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
  // Started classes (GetClassList::presentClass) — only used to fill the
  // Course / Instructor / Room / Term / Time pickers with real values.
  classes: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["cancel"]);

function toOptions(values) {
  return [...new Set(values.filter(Boolean))]
    .sort((a, b) => String(a).localeCompare(String(b)))
    .map((value) => ({ value, label: value }));
}

const courseOptions = computed(() => toOptions(props.classes.map((c) => c.course)));
const instructorOptions = computed(() => toOptions(props.classes.map((c) => c.teacher)));
const roomOptions = computed(() =>
  toOptions(props.classes.map((c) => [c.floor, c.room].filter(Boolean).join(" ").trim())),
);
const termOptions = computed(() => toOptions(props.classes.map((c) => c.term)));
const timeOptions = computed(() => toOptions(props.classes.map((c) => c.time)));

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

function blankForm() {
  return {
    full_name: "",
    khmer_name: "",
    phone: "",
    gender: "",
    course: "",
    class_group: "",
    instructor: "",
    room: "",
    term: "",
    time: "",
    course_price: "",
    amount_paid: "",
    discount: "",
    document_price: "",
    payment_method: "Cash",
    payment_date: new Date().toISOString().slice(0, 10),
    note: "",
  };
}

const nameLiveError = computed(() => latinNameError(form.full_name));

const remainingBalance = computed(() => {
  const price = Number(form.course_price || 0);
  const documentPrice = Number(form.document_price || 0);
  const discount = Number(form.discount || 0);
  const paid = Number(form.amount_paid || 0);
  return Math.max(price + documentPrice - discount - paid, 0);
});

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
  if (form.course_price === "" || Number(form.course_price) < 0) errors.course_price = t("Course price is required.");
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
    course: form.course || form.class_group,
    price: form.course_price,
    unit_price: null,
    document_price: form.document_price,
    term: form.term || "-",
    time: form.time || "-",
    teacher: form.instructor || "-",
    building: "",
    floor: "",
    room: form.room || "-",
    enroll_start_date: form.payment_date,
  };
  receiptStudent.value = {
    name: form.full_name,
    gender: form.gender,
    payment_date: form.payment_date,
    amount_paid: form.amount_paid,
    fee_amount: form.course_price,
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
  const payload = { ...form, remaining_balance: remainingBalance.value };

  try {
    await window.axios.post("/dashboard/enroll/manual-registrations", payload);
    toast.success(t("Registration saved successfully."));
    if (print) {
      buildReceipt();
      await nextTick();
      window.print();
    } else {
      resetForm();
    }
  } catch (error) {
    const status = error?.response?.status;
    if (status === 422) {
      Object.assign(errors, error.response.data.errors ?? {});
      toast.error(t("Please fix the highlighted fields."));
    } else if (status === 404 || status === 405) {
      // Endpoint not wired yet — still let staff print the receipt from what
      // they entered, and keep the captured payload out of the console void.
      console.info("Manual registration payload (endpoint not connected):", payload);
      if (print) {
        buildReceipt();
        await nextTick();
        window.print();
        toast.info(t("Receipt printed. The save endpoint is not connected yet."));
      } else {
        toast.info(t("Captured. The save endpoint is not connected yet."));
      }
    } else {
      toast.error(error?.response?.data?.message ?? t("Failed to save registration."));
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
            <label :class="labelClass">{{ $t('Class / Group') }}</label>
            <input v-model="form.class_group" type="text" :class="controlClass" :placeholder="$t('Optional')" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Instructor') }}</label>
            <SelectSearch v-model="form.instructor" :options="instructorOptions" placeholder="Select an instructor" empty-text="No instructors" :button-class="triggerClass(false)" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Room') }}</label>
            <SelectSearch v-model="form.room" :options="roomOptions" placeholder="Select a room" empty-text="No rooms" :button-class="triggerClass(false)" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Term / Days') }}</label>
            <SelectSearch v-model="form.term" :options="termOptions" placeholder="Select term / days" empty-text="No terms" :button-class="triggerClass(false)" />
          </div>
          <div>
            <label :class="labelClass">{{ $t('Time') }}</label>
            <SelectSearch v-model="form.time" :options="timeOptions" placeholder="Select time" empty-text="No times" :button-class="triggerClass(false)" />
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
            <label :class="labelClass">{{ $t('Course Price') }} <span class="text-red-500">*</span></label>
            <input v-model="form.course_price" type="number" min="0" step="0.01" :class="[controlClass, errors.course_price && errorClass]" placeholder="0.00" />
            <p v-if="errors.course_price" class="mt-1 text-xs text-red-600">{{ errors.course_price }}</p>
          </div>
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

        <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-gray-800/60">
          <span class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('Remaining Balance') }}</span>
          <span
            :class="[
              'text-base font-bold tabular-nums',
              remainingBalance > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400',
            ]"
          >${{ remainingBalance.toFixed(2) }}</span>
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

  <ReceiptPrint :class-data="receiptClassData" :student="receiptStudent" />
</template>
