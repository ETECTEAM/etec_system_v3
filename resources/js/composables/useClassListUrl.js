import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Where "back to the class list" goes. /dashboard/enroll is super_admin only, so an
 * instructor editing one of their own classes goes back to their dashboard instead.
 */
export function useClassListUrl() {
  const page = usePage()

  return computed(() => {
    const roles = page.props.auth?.roles ?? []
    const isSelfManagingInstructor =
      roles.includes('instructor') && !roles.includes('admin') && !roles.includes('super_admin')

    return isSelfManagingInstructor ? '/dashboard' : '/dashboard/enroll'
  })
}
