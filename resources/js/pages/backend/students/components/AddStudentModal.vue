<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  classId: {
    type: Number,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["close"]);

const form = useForm({
  student_id: "",
});

function close() {
  form.reset();
  form.clearErrors();
  emit("close");
}

function submit() {
  form.post(`/dashboard/students/${props.classId}/enrollments`, {
    preserveScroll: true,
    onSuccess: close,
  });
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">Add Student</h3>

      <div class="mt-4">
        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Student</label>
        <select v-model="form.student_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">Select Student</option>
          <option v-for="student in students" :key="student.id" :value="student.id">
            {{ student.phone ? `${student.name} - ${student.phone}` : student.name }}
          </option>
        </select>
        <p v-if="form.errors.student_id" class="mt-1 text-xs text-red-600">{{ form.errors.student_id }}</p>
      </div>

      <div class="mt-6 flex justify-end gap-3">
        <button type="button" @click="close" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
          Cancel
        </button>
        <button type="button" @click="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
          {{ form.processing ? "Adding..." : "Add Student" }}
        </button>
      </div>
    </div>
  </div>
</template>
