export function build(ctx) {
  if (!ctx.isSuperAdmin || !ctx.canAccessNotifications) return null;

  return {
    label: "Enrollment Management",
    labelKey: "navigation.enrollManagement",
    key: "enroll",
    icon: "notebook-pen",
    href: "/dashboard/enroll",
    match: ["/dashboard/enroll"],
    exact: false,
    isActive: (path) => path.startsWith("/dashboard/enroll") && !path.startsWith("/dashboard/enroll/students/create") && !path.startsWith("/dashboard/enroll/config"),
  };
}
