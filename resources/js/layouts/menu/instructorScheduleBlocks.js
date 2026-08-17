export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) return null;

  return {
    label: "Schedule Blocks",
    labelKey: "navigation.instructorScheduleBlocks",
    href: "/dashboard/instructor-schedule-blocks",
    match: ["/dashboard/instructor-schedule-blocks"],
    exact: false,
    icon: "schedule",
    isActive: (path) => path.startsWith("/dashboard/instructor-schedule-blocks"),
  };
}
