export const key = "holiday";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/holidays");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Holiday",
    labelKey: "navigation.holiday",
    key,
    href: "/dashboard/holidays",
    match: ["/dashboard/holidays"],
    icon: "holiday",
    isActive: (path) => path.startsWith("/dashboard/holidays"),
  };
}
