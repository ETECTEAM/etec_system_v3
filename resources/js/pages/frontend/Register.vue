<script setup>
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
  classes: Array,
  success: String,
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
  form.post('/register', {
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
  <Head title="Student Registration" />
  <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg">
      <h1 class="text-2xl font-bold mb-6 text-center text-blue-600">Student Registration</h1>

      <div v-if="success" class="mb-4 p-4 text-green-700 bg-green-100 rounded">
        {{ success }}
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Full Name</label>
          <input v-model="form.full_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Gender</label>
            <select v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
            <input v-model="form.date_of_birth" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Phone</label>
          <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <textarea v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Select Class & Time</label>
          <select v-model="form.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="" disabled>Choose a class...</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.class_name }} - {{ cls.time?.time_name }} ({{ cls.registered_count }}/{{ cls.capacity }})
            </option>
          </select>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition" :disabled="form.processing">
          Register
        </button>
      </form>
    </div>
  </div>
</template>
