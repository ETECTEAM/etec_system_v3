export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) return null;

  return {
    label: "Pre Attendance",
    labelKey: "Pre Attendance",
    href: "/dashboard/instructor/pre-attendance",
    match: ["/dashboard/instructor/pre-attendance"],
    exact: false,
    icon: "pre_attendance",
    isActive: (path) => path.startsWith("/dashboard/instructor/pre-attendance"),
  };
}
