<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Eye, EyeOff } from '@lucide/vue'
import { SelectSearch } from '../../../../components/ui/select-search'
import { formatRole } from '../../../../lib/roleBadge'

const props = defineProps({ form: Object, roleOptions: Array, submitLabel: String, edit: Boolean })
const emit = defineEmits(['submit'])
const roles = computed(() => (props.roleOptions ?? []).map((role) => ({ label: formatRole(role), value: role })))
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]
const student = computed(() => props.form.role === 'student')
const instructor = computed(() => props.form.role === 'instructor')
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
</script>

<template>
  <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="emit('submit')">
    <label v-if="!student && !instructor" class="block">
      <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $t('Login Display Name') }}</span>
      <input v-model="form.name" :placeholder="$t('e.g. Rak Smey')" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
      <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
    </label>
    <label class="block">
      <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $t('Login Email') }}</span>
      <input v-model="form.email" type="email" :placeholder="$t('smey@etec.com')" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
      <span v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</span>
    </label>
    <label class="block">
      <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $t('Avatar') }}</span>
      <input type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" @input="form.avatar = $event.target.files[0]" />
      <span v-if="form.errors.avatar" class="text-xs text-red-600">{{ form.errors.avatar }}</span>
    </label>
    <label class="block">
      <span class="mb-2 block text-sm font-semibold text-slate-700">{{ edit ? 'New Password' : 'Password' }}</span>
      <div class="relative">
        <input v-model="form.password" :type="showPassword ? 'text' : 'password'" :placeholder="$t('Min. 8 characters')" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-11 text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
        <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600" @click="showPassword = !showPassword">
          <EyeOff v-if="showPassword" class="h-4 w-4" />
          <Eye v-else class="h-4 w-4" />
        </button>
      </div>
    </label>
    <label class="block">
      <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $t('Confirm Password') }}</span>
      <div class="relative">
        <input v-model="form.password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" :placeholder="$t('Repeat password')" class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-11 text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
        <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600" @click="showPasswordConfirmation = !showPasswordConfirmation">
          <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
          <Eye v-else class="h-4 w-4" />
        </button>
      </div>
    </label>
    <div class="grid gap-4 md:grid-cols-2 sm:col-span-2">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $t('Role') }}</span>
        <SelectSearch v-model="form.role" :options="roles" />
        <span v-if="form.errors.role" class="text-xs text-red-600">{{ form.errors.role }}</span>
      </label>
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $t('Login Status') }}</span>
        <SelectSearch v-model="form.account_status" :options="statusOptions" :placeholder="$t('Select status')" />
      </label>
    </div>

    <template v-if="student">
      <div class="sm:col-span-2 border-t pt-4 text-base font-semibold text-slate-900">{{ $t('Student Profile') }}</div>
      <label class="block">
        <span>{{ $t('Full Name') }}</span>
        <input v-model="form.student_full_name" :placeholder="$t('Full name')" class="input" />
        <span v-if="form.errors.student_full_name" class="text-xs text-red-600">{{ form.errors.student_full_name }}</span>
      </label>
      <label class="block">
        <span>{{ $t('First Name') }}</span>
        <input v-model="form.student_first_name" :placeholder="$t('First name')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Last Name') }}</span>
        <input v-model="form.student_last_name" :placeholder="$t('Last name')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Full Name Khmer') }}</span>
        <input v-model="form.student_full_name_kh" placeholder="ឈ្មោះពេញ" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Gender') }}</span>
        <input v-model="form.student_gender" :placeholder="$t('Male / Female')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Date of Birth') }}</span>
        <input v-model="form.student_date_of_birth" type="date" :placeholder="$t('YYYY-MM-DD')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Student Phone') }}</span>
        <input v-model="form.student_phone" :placeholder="$t('e.g. 012 345 678')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Contact Email') }}</span>
        <input v-model="form.student_email" type="email" :placeholder="$t('student@gmail.com')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Class ID') }}</span>
        <input v-model="form.student_class_id" type="number" :placeholder="$t('e.g. 1')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Parent Name') }}</span>
        <input v-model="form.parent_name" :placeholder="$t('Parent name')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Parent Phone') }}</span>
        <input v-model="form.parent_phone" :placeholder="$t('e.g. 012 345 678')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Student Status') }}</span>
        <select v-model="form.student_status" class="input">
          <option value="" disabled>{{ $t('Select status') }}</option>
          <option :value="true">{{ $t('Active') }}</option>
          <option :value="false">{{ $t('Inactive') }}</option>
        </select>
      </label>
      <label class="block sm:col-span-2">
        <span>{{ $t('Address') }}</span>
        <textarea v-model="form.student_address" :placeholder="$t('Street, city, province')" class="input" />
      </label>
    </template>

    <template v-if="instructor">
      <div class="sm:col-span-2 border-t pt-4 text-base font-semibold text-slate-900">{{ $t('Instructor Profile') }}</div>
      <label class="block">
        <span>{{ $t('Instructor Code') }}</span>
        <input v-model="form.instructor_code" disabled class="input !bg-slate-100 !text-slate-500" />
        <span v-if="form.errors.instructor_code" class="text-xs text-red-600">{{ form.errors.instructor_code }}</span>
      </label>
      <label class="block">
        <span>{{ $t('Full Name') }}</span>
        <input v-model="form.instructor_full_name" :placeholder="$t('Full name')" class="input" />
        <span v-if="form.errors.instructor_full_name" class="text-xs text-red-600">{{ form.errors.instructor_full_name }}</span>
      </label>
      <label class="block">
        <span>{{ $t('First Name') }}</span>
        <input v-model="form.instructor_first_name" :placeholder="$t('First name')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Last Name') }}</span>
        <input v-model="form.instructor_last_name" :placeholder="$t('Last name')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Full Name Khmer') }}</span>
        <input v-model="form.instructor_full_name_kh" placeholder="ឈ្មោះពេញ" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Gender') }}</span>
        <input v-model="form.instructor_gender" :placeholder="$t('Male / Female')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Date of Birth') }}</span>
        <input v-model="form.instructor_date_of_birth" type="date" :placeholder="$t('YYYY-MM-DD')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Instructor Phone') }}</span>
        <input v-model="form.instructor_phone" :placeholder="$t('e.g. 012 345 678')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Contact Email') }}</span>
        <input v-model="form.instructor_email" type="email" :placeholder="$t('instructor@gmail.com')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Specialization') }}</span>
        <input v-model="form.specialization" :placeholder="$t('e.g. Mathematics')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Employment Type') }}</span>
        <select v-model="form.employment_type" class="input">
          <option value="" disabled>{{ $t('Select type') }}</option>
          <option value="full_time">{{ $t('Full-time') }}</option>
          <option value="part_time">{{ $t('Part-time') }}</option>
        </select>
      </label>
      <label class="block">
        <span>{{ $t('Shift Preference') }}</span>
        <select v-model="form.shift_preference" class="input">
          <option value="" disabled>{{ $t('Select shift') }}</option>
          <option value="morning_afternoon">{{ $t('Morning & Afternoon (Mon-Fri)') }}</option>
          <option value="morning_evening">{{ $t('Morning & Evening (Mon-Fri)') }}</option>
          <option value="afternoon_evening_11">{{ $t('Afternoon & Evening 11:00-20:30 (Mon-Fri)') }}</option>
          <option value="afternoon_evening_1230">{{ $t('Afternoon & Evening 12:30-20:30 (Mon-Fri)') }}</option>
        </select>
      </label>
      <label class="block">
        <span>{{ $t('Available For Class') }}</span>
        <select v-model="form.available_for_class" class="input">
          <option value="" disabled>{{ $t('Select') }}</option>
          <option :value="true">{{ $t('Yes') }}</option>
          <option :value="false">{{ $t('No option') }}</option>
        </select>
      </label>
      <label class="block">
        <span>{{ $t('Hire Date') }}</span>
        <input v-model="form.hire_date" type="date" :placeholder="$t('YYYY-MM-DD')" class="input" />
      </label>
      <label class="block">
        <span>{{ $t('Instructor Status') }}</span>
        <select v-model="form.instructor_status" class="input">
          <option value="" disabled>{{ $t('Select status') }}</option>
          <option :value="true">{{ $t('Active') }}</option>
          <option :value="false">{{ $t('Inactive') }}</option>
        </select>
      </label>
      <label class="block sm:col-span-2">
        <span>{{ $t('Address') }}</span>
        <textarea v-model="form.instructor_address" :placeholder="$t('Street, city, province')" class="input" />
      </label>
    </template>

    <div class="flex justify-end gap-3 sm:col-span-2">
      <Link href="/dashboard/users" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">{{ $t('Cancel') }}</Link>
      <button :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white">{{ submitLabel }}</button>
    </div>
  </form>
</template>

<style scoped>
@reference "../../../../../css/app.css";
.input { @apply mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none; }
label > span { @apply mb-2 block text-sm font-semibold text-slate-700; }
</style>
