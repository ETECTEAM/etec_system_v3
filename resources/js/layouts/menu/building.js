export const key = "building_management";

export function isRoute(path) {
  const p = path.split("?")[0];
  return (
    p.startsWith("/dashboard/buildings") ||
    p.startsWith("/dashboard/floors") ||
    p.startsWith("/dashboard/rooms")
  );
}

export function build(ctx) {
  if (!ctx.canAccessFloors) return null;

  return {
    label: "Building Management",
    key,
    match: ["/dashboard/buildings", "/dashboard/floors", "/dashboard/rooms"],
    icon: "building_management",
    children: [
      {
        label: "Buildings",
        href: "/dashboard/buildings",
        match: ["/dashboard/buildings"],
        exact: false,
      },
      {
        label: "Floors",
        href: "/dashboard/floors",
        match: ["/dashboard/floors"],
        exact: false,
      },
      {
        label: "Rooms",
        href: "/dashboard/rooms",
        match: ["/dashboard/rooms"],
        exact: false,
      },
    ],
  };
}
