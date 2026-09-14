export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Instructor Busy Time",
    labelKey: "navigation.instructorAvailability",
    href: "/dashboard/instructor-availability",
    match: ["/dashboard/instructor-availability"],
    exact: false,
    icon: "schedule",
    isActive: (path) => path.startsWith("/dashboard/instructor-availability"),
  };
}
