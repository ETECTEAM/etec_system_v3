export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Room Availability",
    labelKey: "navigation.roomAvailability",
    href: "/dashboard/room-availability",
    match: ["/dashboard/room-availability"],
    exact: false,
    icon: "room_availability",
    isActive: (path) => path.startsWith("/dashboard/room-availability"),
  };
}
