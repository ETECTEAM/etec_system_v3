<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

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

const emit = defineEmits(["close"]);

const form = useForm({
  deposit_amount: "",
});

watch(
  () => props.show,
  (show) => {
    if (show) {
      form.reset();
      form.clearErrors();
    }
  }
);

function close() {
  form.reset();
  form.clearErrors();
  emit("close");
}

function submit() {
  if (!props.enrollment?.enrollment_id) return;

  form.post(`/dashboard/enroll/enrollments/${props.enrollment.enrollment_id}/deposit`, {
    preserveScroll: true,
    onSuccess: close,
  });
}
</script>

<template>
  <div v-if="show && enrollment" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Record Deposit') }}</h3>

      <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700 dark:bg-gray-800 dark:text-gray-300">
        <p class="font-semibold">{{ enrollment.name }}</p>
        <p>Remaining: ${{ Number(enrollment.remaining_balance ?? 0).toFixed(2) }}</p>
      </div>

      <div class="mt-4">
        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Deposit Amount') }}</label>
        <input type="number" min="0.01" step="0.01" v-model="form.deposit_amount" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.deposit_amount" class="mt-1 text-xs text-red-600">{{ form.errors.deposit_amount }}</p>
      </div>

      <div class="mt-6 flex justify-end gap-3">
        <button type="button" @click="close" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
          {{ $t('Cancel') }}
        </button>
        <button type="button" @click="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
          {{ form.processing ? $t("Saving...") : $t("Record Deposit") }}
        </button>
      </div>
    </div>
  </div>
</template>
