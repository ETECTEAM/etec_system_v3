import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { formatRole } from '../../../../lib/roleBadge'
import { useSaveForm } from '../../../../composables/useSaveForm'
import { useI18n } from '../../../../i18n'

// Owns the edit-user form (account fields + every student/instructor profile field), role/status options,
// and the name-lock rule tied to instructor/student roles.
export function useUserEdit() {
  const page = usePage()
  const { t } = useI18n()
  const user = page.props.user
  const roleOptions = page.props.roleOptions ?? []
  const s = user.student ?? {}
  const i = user.instructor_data ?? {}

  // Flat form combining core account fields with every student/instructor profile field, so switching role in the UI
  // doesn't need to swap form models. password/password_confirmation start blank: leaving them blank keeps the current password (see submit).
  const { form, save } = useSaveForm({
    name: user.name ?? '',
    email: user.email ?? '',
    password: '',
    password_confirmation: '',
    role: user.role ?? (roleOptions[0] ?? 'admin'),
    account_status: user.status ?? 'active',
    avatar: null,
    student_full_name: s.full_name ?? '',
    student_first_name: s.first_name ?? '',
    student_last_name: s.last_name ?? '',
    student_full_name_kh: s.full_name_kh ?? '',
    student_gender: s.gender ?? '',
    student_date_of_birth: s.date_of_birth ?? '',
    student_phone: s.phone ?? '',
    student_email: s.email ?? '',
    student_class_id: s.class_id ?? '',
    parent_name: s.parent_name ?? '',
    parent_phone: s.parent_phone ?? '',
    student_address: s.address ?? '',
    student_status: s.status ?? true,
    instructor_code: i.instructor_code ?? '',
    instructor_full_name: i.full_name ?? '',
    instructor_first_name: i.first_name ?? '',
    instructor_last_name: i.last_name ?? '',
    instructor_full_name_kh: i.full_name_kh ?? '',
    instructor_gender: i.gender ?? '',
    instructor_date_of_birth: i.date_of_birth ?? '',
    instructor_phone: i.phone ?? '',
    instructor_email: i.email ?? '',
    specialization: i.specialization ?? [],
    employment_type: i.employment_type ?? 'full_time',
    shift_preference: i.shift_preference ?? 'morning_evening',
    available_for_class: i.available_for_class ?? true,
    hire_date: i.hire_date ?? '',
    instructor_address: i.address ?? '',
    instructor_status: i.status ?? true,
  })

  // Map raw role slugs to the label/value pairs SelectSearch expects.
  const roleSelectOptions = computed(() => roleOptions.map((role) => ({ label: formatRole(role), value: role })))

  const statusSelectOptions = computed(() => [
    { label: t('Active'), value: 'active' },
    { label: t('Inactive'), value: 'inactive' },
  ])

  // Instructors and students set their own name on first login, so admins can't edit it here.
  const nameLocked = computed(() => form.role === 'instructor' || form.role === 'student')

  // Clear any name typed before switching to a locked role, so a stale value isn't submitted.
  watch(() => form.role, (role) => {
    if (role === 'instructor' || role === 'student') {
      form.name = ''
    }
  })

  // PUT to this user's edit endpoint; the backend keeps the existing password when the password field is blank.
  function submit() {
    save(`/dashboard/users/edit/${user.id}`)
  }

  return {
    form,
    user,
    roleSelectOptions,
    statusSelectOptions,
    nameLocked,
    submit,
  }
}
