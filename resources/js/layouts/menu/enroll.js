export function build(ctx) {
  if (!ctx.isSuperAdmin || !ctx.canAccessNotifications) return null;

  return {
    label: "Enrollment Management",
    labelKey: "navigation.enrollManagement",
    key: "enroll",
    icon: "student",
    href: "/dashboard/students",
    match: ["/dashboard/students"],
    exact: false,
    isActive: (path) => path.startsWith("/dashboard/students"),
  };
}
