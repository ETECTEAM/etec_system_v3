<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { SelectSearch } from '../../../../components/ui/select-search'
import { formatRole } from '../../../../lib/roleBadge'

const props = defineProps({ form: Object, roleOptions: Array, submitLabel: String, edit: Boolean })
const emit = defineEmits(['submit'])
const roles = computed(() => (props.roleOptions ?? []).map((role) => ({ label: formatRole(role), value: role })))
const student = computed(() => props.form.role === 'student')
const instructor = computed(() => props.form.role === 'instructor')
</script>

<template>
  <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="emit('submit')">
    <label v-if="!student && !instructor" class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Login Display Name</span><input v-model="form.name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" /><span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span></label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Login Email</span><input v-model="form.email" type="email" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" /><span v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</span></label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Avatar</span><input type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" @input="form.avatar = $event.target.files[0]" /><span v-if="form.errors.avatar" class="text-xs text-red-600">{{ form.errors.avatar }}</span></label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">{{ edit ? 'New Password' : 'Password' }}</span><input v-model="form.password" type="password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" /></label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</span><input v-model="form.password_confirmation" type="password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" /></label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Role</span><SelectSearch v-model="form.role" :options="roles" /><span v-if="form.errors.role" class="text-xs text-red-600">{{ form.errors.role }}</span></label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Login Status</span><select v-model="form.account_status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"><option :value="true">Active</option><option :value="false">Inactive</option></select></label>

    <template v-if="student">
      <div class="sm:col-span-2 border-t pt-4 text-base font-semibold text-slate-900">Student Profile</div>
      <label class="block"><span>Student Code</span><input v-model="form.student_code" class="input" /><span v-if="form.errors.student_code" class="text-xs text-red-600">{{ form.errors.student_code }}</span></label>
      <label class="block"><span>Full Name</span><input v-model="form.student_full_name" class="input" /><span v-if="form.errors.student_full_name" class="text-xs text-red-600">{{ form.errors.student_full_name }}</span></label>
      <label class="block"><span>First Name</span><input v-model="form.student_first_name" class="input" /></label><label class="block"><span>Last Name</span><input v-model="form.student_last_name" class="input" /></label>
      <label class="block"><span>Full Name Khmer</span><input v-model="form.student_full_name_kh" class="input" /></label><label class="block"><span>Gender</span><input v-model="form.student_gender" class="input" /></label>
      <label class="block"><span>Date of Birth</span><input v-model="form.student_date_of_birth" type="date" class="input" /></label><label class="block"><span>Student Phone</span><input v-model="form.student_phone" class="input" /></label>
      <label class="block"><span>Contact Email</span><input v-model="form.student_email" type="email" class="input" /></label><label class="block"><span>Class ID</span><input v-model="form.student_class_id" type="number" class="input" /></label>
      <label class="block"><span>Parent Name</span><input v-model="form.parent_name" class="input" /></label><label class="block"><span>Parent Phone</span><input v-model="form.parent_phone" class="input" /></label>
      <label class="block"><span>Student Status</span><select v-model="form.student_status" class="input"><option :value="true">Active</option><option :value="false">Inactive</option></select></label><label class="block sm:col-span-2"><span>Address</span><textarea v-model="form.student_address" class="input" /></label>
    </template>
    <template v-if="instructor">
      <div class="sm:col-span-2 border-t pt-4 text-base font-semibold text-slate-900">Instructor Profile</div>
      <label class="block"><span>Instructor Code</span><input v-model="form.instructor_code" class="input" /><span v-if="form.errors.instructor_code" class="text-xs text-red-600">{{ form.errors.instructor_code }}</span></label><label class="block"><span>Full Name</span><input v-model="form.instructor_full_name" class="input" /><span v-if="form.errors.instructor_full_name" class="text-xs text-red-600">{{ form.errors.instructor_full_name }}</span></label>
      <label class="block"><span>First Name</span><input v-model="form.instructor_first_name" class="input" /></label><label class="block"><span>Last Name</span><input v-model="form.instructor_last_name" class="input" /></label><label class="block"><span>Full Name Khmer</span><input v-model="form.instructor_full_name_kh" class="input" /></label><label class="block"><span>Gender</span><input v-model="form.instructor_gender" class="input" /></label>
      <label class="block"><span>Date of Birth</span><input v-model="form.instructor_date_of_birth" type="date" class="input" /></label><label class="block"><span>Instructor Phone</span><input v-model="form.instructor_phone" class="input" /></label><label class="block"><span>Contact Email</span><input v-model="form.instructor_email" type="email" class="input" /></label><label class="block"><span>Specialization</span><input v-model="form.specialization" class="input" /></label>
      <label class="block"><span>Employment Type</span><select v-model="form.employment_type" class="input"><option value="full_time">Full-time</option><option value="part_time">Part-time</option></select></label><label class="block"><span>Shift Preference</span><select v-model="form.shift_preference" class="input"><option value="morning_evening">Morning-Evening</option><option value="afternoon_evening">Afternoon-Evening</option><option value="morning_afternoon">Morning-Afternoon</option></select></label>
      <label class="block"><span>Available For Class</span><select v-model="form.available_for_class" class="input"><option :value="true">Yes</option><option :value="false">No</option></select></label><label class="block"><span>Hire Date</span><input v-model="form.hire_date" type="date" class="input" /></label><label class="block"><span>Instructor Status</span><select v-model="form.instructor_status" class="input"><option :value="true">Active</option><option :value="false">Inactive</option></select></label><label class="block sm:col-span-2"><span>Address</span><textarea v-model="form.instructor_address" class="input" /></label>
    </template>
    <div class="flex justify-end gap-3 sm:col-span-2"><Link href="/dashboard/users" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Cancel</Link><button :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white">{{ submitLabel }}</button></div>
  </form>
</template>

<style scoped>
@reference "../../../../../css/app.css";
.input { @apply mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm; }
label > span { @apply mb-2 block text-sm font-semibold text-slate-700; }
</style>
