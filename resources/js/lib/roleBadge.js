export function roleBadgeClass(role) {
  switch (role) {
    case 'super_admin':
      return 'bg-red-100 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20'
    case 'admin':
      return 'bg-blue-100 text-blue-600 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20'
    case 'instructor':
      return 'bg-green-100 text-green-600 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20'
    case 'student':
      return 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20'
    default:
      return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20'
  }
}

export function formatRole(role) {
  return role.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase())
}
