export const key = "certificate";

export function isRoute(path) {
  const p = path.split("?")[0];
  return p.startsWith("/dashboard/certificates");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  function isType(type) {
    return (_pathOnly, fullPath) => {
      const [pathname, query = ""] = fullPath.split("?");
      return pathname === "/dashboard/certificates" && query.includes(`type=${type}`);
    };
  }

  return {
    label: "Certificate",
    labelKey: "navigation.certificate",
    key,
    icon: "certificate",
    match: ["/dashboard/certificates"],
    children: [
      {
        label: "Free Certificate",
        labelKey: "navigation.certificateFree",
        href: "/dashboard/certificates?type=free",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("free"),
      },
      {
        label: "Normal Certificate",
        labelKey: "navigation.certificateNormal",
        href: "/dashboard/certificates?type=normal",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("normal"),
      },
      {
        label: "Meal Certificate",
        labelKey: "navigation.certificateMeal",
        href: "/dashboard/certificates?type=meal",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("meal"),
      },
    ],
  };
}
