<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineOptions({ layout: DashboardLayout })

const props = defineProps({
  classes: Array,
  times: Array,
  success: String,
});

const form = useForm({
  class_name: '',
  time_id: '',
  capacity: '',
});

const submit = () => {
  form.post('/dashboard/admin/classes', {
    onSuccess: () => form.reset(),
    preserveScroll: true,
  });
};

const searchQuery = ref('');

const filteredClasses = computed(() => {
    if (!searchQuery.value) return props.classes;
    return props.classes.filter(cls => {
        const className = cls.class_name?.toLowerCase() || '';
        const timeName = cls.time?.time_name?.toLowerCase() || '';
        const query = searchQuery.value.toLowerCase();
        return className.includes(query) || timeName.includes(query);
    });
});
</script>

<template>
  <Head title="Manage Classes" />
  <div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Class Management</h1>
        <div class="space-x-4">
            <Link href="/dashboard/admin/registrations" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition shadow-sm font-medium dark:bg-gray-700 dark:hover:bg-gray-600">Manage Registrations</Link>
        </div>
    </div>

    <div v-if="success" class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg shadow-sm border border-green-200 flex items-center dark:text-emerald-400 dark:bg-emerald-500/10 dark:border-emerald-500/20">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      {{ success }}
    </div>

    <div class="flex flex-col xl:flex-row gap-6">
      <!-- Create Form (Left Column) -->
      <div class="w-full xl:w-1/3">
          <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-indigo-600 sticky top-6 dark:bg-gray-900">
              <h2 class="text-lg font-bold mb-5 flex items-center text-gray-800 dark:text-gray-100">
                <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Create New Class
              </h2>

              <form @submit.prevent="submit" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Course / Class Name *</label>
                  <input v-model="form.class_name" type="text" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" required placeholder="e.g. Graphic Design - Batch 10">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Select Time Slot *</label>
                  <select v-model="form.time_id" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" required>
                    <option value="" disabled>Choose a time...</option>
                    <option v-for="time in times" :key="time.id" :value="time.id">
                      {{ time.time_name }} ({{ time.term?.term_name }})
                    </option>
                  </select>
                </div>

                <div>
                   <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Capacity (Max Students) *</label>
                   <input v-model="form.capacity" type="number" min="1" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" required placeholder="Ex: 30">
                </div>

                <div class="pt-2">
                  <button type="submit" class="w-full bg-indigo-600 text-white font-medium py-2.5 px-4 rounded-md hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 transition shadow-sm flex justify-center items-center dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-indigo-500/30" :disabled="form.processing">
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? 'Saving...' : 'Complete Setup' }}
                  </button>
                </div>
              </form>
          </div>
      </div>

      <!-- Class List (Right Column) -->
      <div class="w-full xl:w-2/3">
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 dark:bg-gray-900 dark:border-gray-800">

          <div class="p-5 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-50/50 gap-4 dark:border-gray-800 dark:bg-gray-800/40">
            <h2 class="text-lg font-bold text-gray-800 flex items-center dark:text-gray-100">
              <svg class="w-5 h-5 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
              Classes
              <span class="ml-2 bg-indigo-100 text-indigo-800 text-xs py-0.5 px-2 rounded-full dark:bg-indigo-500/10 dark:text-indigo-400">{{ filteredClasses.length }}</span>
            </h2>
            <div class="w-full sm:w-64 relative">
               <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                 <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
               </div>
               <input v-model="searchQuery" type="text" placeholder="Search by name or time..." class="w-full pl-9 border-gray-300 rounded-md shadow-sm p-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 border bg-white dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
            </div>
          </div>

          <div class="overflow-x-auto overflow-y-auto max-h-[520px]">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
              <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm dark:bg-gray-800">
                <tr>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-300">Course Info</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-300">Schedule</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-300">Enrollment Stats</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-300">Created At</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-800">
                <tr v-for="cls in filteredClasses" :key="cls.id" class="hover:bg-indigo-50/30 transition-colors dark:hover:bg-gray-800">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ cls.class_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5 dark:text-gray-400">ID: {{ cls.id }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 flex items-center dark:text-gray-100">
                      <svg class="w-4 h-4 mr-1.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      {{ cls.time?.time_name }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5 ml-5 dark:text-gray-400">{{ cls.time?.term?.term_name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2 max-w-[100px] dark:bg-gray-700">
                        <div class="bg-indigo-600 h-2.5 rounded-full dark:bg-indigo-500" :style="{ width: Math.min((cls.registered_count / cls.capacity) * 100, 100) + '%' }"></div>
                      </div>
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ cls.registered_count }} / {{ cls.capacity }}</span>
                    </div>
                    <div class="text-xs mt-1" :class="cls.registered_count >= cls.capacity ? 'text-red-500 font-semibold dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                      {{ cls.registered_count >= cls.capacity ? 'Full Capacity' : (cls.capacity - cls.registered_count) + ' seats available' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col">
                      <div class="flex items-center text-gray-800 dark:text-gray-200">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ new Date(cls.created_at).toLocaleDateString() }}
                      </div>
                      <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ new Date(cls.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                      </div>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredClasses?.length">
                  <td colspan="4" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No classes found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      {{ searchQuery ? 'Try adjusting your search query.' : 'Get started by creating a new class.' }}
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
