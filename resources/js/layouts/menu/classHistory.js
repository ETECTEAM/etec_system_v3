// Instructor-only. Ended classes drop off the dashboard, so this is the way
// back to a finished class's result sheet / PDF.
export function build(ctx) {
  if (!ctx.roles.includes("instructor")) {
    return null;
  }

  return {
    label: "Class History",
    labelKey: "Class History",
    href: "/dashboard/instructor/class-history",
    match: ["/dashboard/instructor/class-history"],
    exact: false,
    icon: "class_history",
    isActive: (path) => path.startsWith("/dashboard/instructor/class-history"),
  };
}
