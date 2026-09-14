export const key = "access_locations";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/access-locations");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin) return null;

  return {
    label: "Location Lock",
    labelKey: "navigation.accessLocations",
    key,
    href: "/dashboard/access-locations",
    match: ["/dashboard/access-locations"],
    icon: "access_locations",
    isActive: (path) => path.startsWith("/dashboard/access-locations"),
  };
}
