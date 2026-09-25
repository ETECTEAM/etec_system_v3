export function build(ctx) {
  if (!ctx.isSuperAdmin) return null;

  return {
    label: "Attendance Report",
    labelKey: "navigation.attendanceReport",
    href: "/dashboard/attendance-report",
    match: ["/dashboard/attendance-report"],
    exact: false,
    icon: "pre_attendance",
  };
}
