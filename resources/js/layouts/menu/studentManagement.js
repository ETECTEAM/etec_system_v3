export const key = 'studentManagement';

export function isRoute(path) { return path.split('?')[0].startsWith('/dashboard/student-management'); }

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;
  return { label: 'Students', labelKey: 'navigation.students', key, icon: 'user', href: '/dashboard/student-management', match: ['/dashboard/student-management'], isActive: isRoute };
}
