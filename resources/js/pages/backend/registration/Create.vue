<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useForm, Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  classes: Array,
});

const form = useForm({
  full_name: '',
  gender: 'male',
  date_of_birth: '',
  phone: '',
  address: '',
  class_id: '',
});

const submit = () => {
  form.post('/dashboard/admin/registrations');
};
</script>

<template>
  <Head :title="$t('Admin Register Student')" />
  <DashboardLayout>
  <div class="p-8">
    <div class="flex items-center mb-6 space-x-4">
      <Link href="/dashboard/admin/registrations" class="text-blue-600 hover:underline dark:text-blue-400">{{ $t('&larr; Back to Registrations') }}</Link>
      <h1 class="text-2xl font-bold dark:text-gray-100">{{ $t('Register Student Manually') }}</h1>
    </div>

    <div class="bg-white p-2 rounded-lg shadow-md max-w-2xl dark:bg-gray-900">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Full Name') }}</label>
          <input v-model="form.full_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Gender') }}</label>
            <select v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
              <option value="male">{{ $t('Male') }}</option>
              <option value="female">{{ $t('Female') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Date of Birth') }}</label>
            <input v-model="form.date_of_birth" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Phone') }}</label>
          <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Address') }}</label>
          <textarea v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Select Class & Time') }}</label>
          <select v-model="form.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
            <option value="" disabled>{{ $t('Choose a class...') }}</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.class_name }} - {{ cls.time?.time_name }} ({{ cls.registered_count }}/{{ cls.capacity }})
            </option>
          </select>
        </div>

        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700" :disabled="form.processing">
          Register Student
        </button>
      </form>
    </div>
  </div>
  </DashboardLayout>
</template>
