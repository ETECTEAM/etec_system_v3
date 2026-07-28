export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Shift Templates",
    labelKey: "navigation.shiftTemplates",
    href: "/dashboard/shift-templates",
    match: ["/dashboard/shift-templates"],
    exact: false,
    icon: "schdule",
    isActive: (path) => path.startsWith("/dashboard/shift-templates"),
  };
}
