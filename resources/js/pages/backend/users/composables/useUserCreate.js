import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { formatRole } from '../../../../lib/roleBadge'
import { useSaveForm } from '../../../../composables/useSaveForm'

const STATUS_OPTIONS = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

// Owns the create-user form, role/status options, and the name-lock rule tied to instructor/student roles.
export function useUserCreate() {
  const page = usePage()

  // Roles the current admin is allowed to assign, provided by the backend based on their access level.
  const roleOptions = page.props.roleOptions ?? []

  const { form, save } = useSaveForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: roleOptions[0] ?? 'admin',
    account_status: 'active',
  })

  // Map raw role slugs to the label/value pairs SelectSearch expects.
  const roleSelectOptions = computed(() => roleOptions.map((role) => ({
    label: formatRole(role),
    value: role,
  })))

  const statusSelectOptions = STATUS_OPTIONS

  // Instructors and students set their own name on first login, so admins can't set it here.
  const nameLocked = computed(() => form.role === 'instructor' || form.role === 'student')

  // Clear any name typed before switching to a locked role, so a stale value isn't submitted.
  watch(() => form.role, (role) => {
    if (role === 'instructor' || role === 'student') {
      form.name = ''
    }
  })

  function submit() {
    save('/dashboard/users', { method: 'post' })
  }

  return {
    form,
    roleSelectOptions,
    statusSelectOptions,
    nameLocked,
    submit,
  }
}
