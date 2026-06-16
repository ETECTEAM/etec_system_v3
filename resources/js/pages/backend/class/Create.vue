<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useForm, Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  times: Array,
});

const form = useForm({
  class_name: '',
  time_id: '',
  capacity: '',
});

const submit = () => {
  form.post('/dashboard/admin/classes');
};
</script>

<template>
  <Head title="Create Class" />
  <DashboardLayout>
  <div class="p-8">
    <div class="flex items-center mb-6 space-x-4">
      <Link href="/dashboard/admin/classes" class="text-blue-600 hover:underline">&larr; Back to Classes</Link>
      <h1 class="text-2xl font-bold">Create New Class</h1>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Course / Class Name</label>
          <input v-model="form.class_name" type="text" placeholder="e.g. Web Design" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Select Time Slot</label>
          <select v-model="form.time_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" required>
            <option value="" disabled>Choose a time...</option>
            <option v-for="time in times" :key="time.id" :value="time.id">
              {{ time.time_name }} ({{ time.term?.term_name }})
            </option>
          </select>
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700">Capacity (Max Students)</label>
           <input v-model="form.capacity" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" required>
        </div>

        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700" :disabled="form.processing">
          Complete Setup
        </button>
      </form>
    </div>
  </div>
  </DashboardLayout>
</template>
