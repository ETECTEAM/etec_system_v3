export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Work Schedules",
    labelKey: "navigation.workSchedules",
    href: "/dashboard/work-schedules",
    match: ["/dashboard/work-schedules"],
    exact: false,
    icon: "schedule",
    isActive: (path) => path.startsWith("/dashboard/work-schedules"),
  };
}
