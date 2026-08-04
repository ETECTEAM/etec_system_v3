export function build(ctx) {
  if (!ctx.isSuperAdmin || !ctx.canAccessNotifications) return null;

  return {
    label: "Register Student",
    labelKey: "navigation.registerStudent",
    icon: "user-plus",
    href: "/dashboard/enroll/students/create",
    match: ["/dashboard/enroll/students/create"],
    exact: false,
    isActive: (path) => path.startsWith("/dashboard/enroll/students/create"),
  };
}
