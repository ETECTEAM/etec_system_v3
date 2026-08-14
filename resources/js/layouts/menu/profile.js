export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) return null;

  return {
    label: "My Profile",
    labelKey: "navigation.myProfile",
    href: "/dashboard/instructor",
    match: ["/dashboard/instructor", "/dashboard/instructor/profile"],
    exact: false,
    icon: "profile",
    // Profile screens only — /dashboard/instructor/classes/* is reached from the dashboard,
    // so it must not light this up.
    isActive: (path) =>
      path === "/dashboard/instructor" || path.startsWith("/dashboard/instructor/profile"),
  };
}
