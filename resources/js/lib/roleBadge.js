export function roleBadgeClass(role) {
  switch (role) {
    case 'super_admin':
      return 'bg-red-100 text-red-600 ring-1 ring-red-200'
    case 'admin':
      return 'bg-blue-100 text-blue-600 ring-1 ring-blue-200'
    case 'instructor':
      return 'bg-green-100 text-green-600 ring-1 ring-green-200'
    case 'student':
      return 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'
    default:
      return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'
  }
}

export function formatRole(role) {
  return role.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase())
}
