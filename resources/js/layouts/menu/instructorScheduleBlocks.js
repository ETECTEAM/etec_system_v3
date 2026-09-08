export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) return null;

  return {
    label: "Busy Time",
    labelKey: "navigation.myAvailability",
    href: "/dashboard/instructor-schedule-blocks",
    match: ["/dashboard/instructor-schedule-blocks"],
    exact: false,
    icon: "schedule",
    isActive: (path) => path.startsWith("/dashboard/instructor-schedule-blocks"),
  };
}
