<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineOptions({ layout: DashboardLayout })

const props = defineProps({
  enrollments: Array,
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
  form.post('/dashboard/admin/registrations', {
    onSuccess: () => form.reset(),
    preserveScroll: true,
  });
};

const searchQuery = ref('');

const filteredEnrollments = computed(() => {
    if (!searchQuery.value) return props.enrollments;
    return props.enrollments.filter(enr => {
        const studentName = enr.student?.full_name?.toLowerCase() || '';
        const phone = enr.student?.phone || '';
        const query = searchQuery.value.toLowerCase();
        return studentName.includes(query) || phone.includes(query);
    });
});
</script>

<template>
  <Head :title="$t('Registration Management')" />
  <div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $t('Registration Management') }}</h1>
        <div class="space-x-4">
            <Link href="/dashboard/admin/classes" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition shadow-sm font-medium">{{ $t('View Classes') }}</Link>
        </div>
    </div>

    <div v-if="success" class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg shadow-sm border border-green-200 flex items-center dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      {{ success }}
    </div>

    <div class="flex flex-col xl:flex-row gap-6">
      <!-- Registration Form (Left Column) -->
      <div class="w-full xl:w-1/3">
          <div class="bg-white p-8 rounded-xl shadow-md border-t-4 border-blue-600 sticky top-6 dark:bg-gray-900">
              <h2 class="text-lg font-bold mb-5 flex items-center text-gray-800 dark:text-gray-100">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                Register New Student
              </h2>
              
              <form @submit.prevent="submit" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">{{ $t('Full Name *') }}</label>
                  <input v-model="form.full_name" type="text" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-blue-500 focus:border-blue-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" required :placeholder="$t('Ex: John Doe')">
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">{{ $t('Gender *') }}</label>
                    <select v-model="form.gender" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-blue-500 focus:border-blue-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
                      <option value="male">{{ $t('Male') }}</option>
                      <option value="female">{{ $t('Female') }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">{{ $t('Date of Birth') }}</label>
                    <input v-model="form.date_of_birth" type="date" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-blue-500 focus:border-blue-500 text-sm xl:text-xs 2xl:text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">{{ $t('Phone *') }}</label>
                  <input v-model="form.phone" type="text" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-blue-500 focus:border-blue-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" required placeholder="012 345 678">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">{{ $t('Address') }}</label>
                  <textarea v-model="form.address" style="height: 112.2px;" class="block w-full rounded-md border-gray-300 shadow-sm border p-3 focus:ring-blue-500 focus:border-blue-500 text-sm resize-y dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('Student address')"></textarea>
                </div>

                <div >
                  <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">{{ $t('Select Class & Time *') }}</label>
                  <select v-model="form.class_id" class="block w-full rounded-md border-gray-300 shadow-sm border p-2.5 focus:ring-blue-500 focus:border-blue-500 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" required>
                    <option value="" disabled>{{ $t('Choose a class...') }}</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                      {{ cls.class_name }} - {{ cls.time?.time_name }} ({{ cls.registered_count }}/{{ cls.capacity }})
                    </option>
                  </select>
                </div>

                <div class="pt-3">
                  <button type="submit" class="w-full bg-blue-600 text-white font-medium py-2.5 px-4 rounded-md hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition shadow-sm flex justify-center items-center" :disabled="form.processing">
                    {{ form.processing ? $t('Registering...') : $t('Register Student') }}
                  </button>
                </div>
              </form>
          </div>
      </div>

      <!-- Registration List (Right Column) -->
      <div class="w-full xl:w-2/3">
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 dark:bg-gray-900 dark:border-gray-800">

          <div class="p-5 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-50/50 gap-4 dark:border-gray-800 dark:bg-gray-800/40">
            <h2 class="text-lg font-bold text-gray-800 flex items-center dark:text-gray-100">
              <svg class="w-5 h-5 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
              Registered Students
              <span class="ml-2 bg-blue-100 text-blue-800 text-xs py-0.5 px-2 rounded-full dark:bg-blue-500/10 dark:text-blue-400">{{ filteredEnrollments.length }}</span>
            </h2>
            <div class="w-full sm:w-64 relative">
               <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                 <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
               </div>
               <input v-model="searchQuery" type="text" :placeholder="$t('Search by name or phone...')" class="w-full pl-9 border-gray-300 rounded-md shadow-sm p-2 text-sm focus:ring-blue-500 focus:border-blue-500 border bg-white dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
            </div>
          </div>

          <div class="overflow-x-auto overflow-y-auto max-h-[573px] pr-1">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
              <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm dark:bg-gray-800">
                <tr>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-400">{{ $t('Student Info') }}</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-400">{{ $t('Contact') }}</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-400">{{ $t('Course / Class') }}</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-400">{{ $t('Enroll. Date') }}</th>
                  <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider dark:text-gray-400">{{ $t('Created At') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-800">
                <tr v-for="enr in filteredEnrollments" :key="enr.id" class="hover:bg-blue-50/30 transition-colors dark:hover:bg-gray-800/60">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 overflow-hidden dark:bg-gray-700 dark:text-gray-400">
                          <svg v-if="enr.student?.gender === 'male'" class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                          <svg v-else-if="enr.student?.gender === 'female'" class="w-6 h-6 text-pink-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                          <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-semibold text-gray-900 break-words dark:text-gray-100">{{ enr.student?.full_name }}</div>
                        <div class="text-xs text-gray-500 capitalize flex items-center mt-0.5 dark:text-gray-400">
                          <!-- Gender Badge -->
                          <span :class="{'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400': enr.student?.gender === 'male', 'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-400': enr.student?.gender === 'female'}" class="px-1.5 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-md">
                            {{ enr.student?.gender || 'Unknown' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 flex items-center dark:text-gray-200">
                      <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 flex-shrink-0 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                      {{ enr.student?.phone }}
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-blue-700 break-words dark:text-blue-400">{{ enr.schedule_class?.class_name }}</div>
                    <div class="text-xs text-gray-500 flex items-center mt-0.5 dark:text-gray-400">
                      <svg class="w-3.5 h-3.5 mr-1 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      {{ enr.schedule_class?.time?.time_name }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center text-[15px] dark:text-gray-200">
                      <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 flex-shrink-0 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                      {{ enr.enrollment_date }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col">
                      <div class="flex items-center text-gray-800 dark:text-gray-200">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ new Date(enr.created_at).toLocaleDateString() }}
                      </div>
                      <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ new Date(enr.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                      </div>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredEnrollments.length">
                  <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $t('No students found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      {{ searchQuery ? 'Try adjusting your search query.' : 'Get started by creating a new registration.' }}
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

