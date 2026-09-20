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
    section: "setup",
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
        label: "Regular Certificate",
        labelKey: "navigation.certificateNormal",
        href: "/dashboard/certificates?type=normal",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("normal"),
      },
      {
        label: "Scholarship Certificate",
        labelKey: "navigation.certificateScholarship",
        href: "/dashboard/certificates?type=scholarship",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("scholarship"),
      },
      {
        label: "Internship Certificate",
        labelKey: "navigation.certificateInternship",
        href: "/dashboard/certificates?type=internship",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("internship"),
      },
      {
        label: "Office Certificate",
        labelKey: "navigation.certificateOffice",
        href: "/dashboard/certificates?type=office",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("office"),
      },
      {
        label: "Certificate Report",
        labelKey: "navigation.certificateReport",
        href: "/dashboard/certificates?type=report",
        match: ["/dashboard/certificates"],
        exact: false,
        isActive: isType("report"),
      },
    ],
  };
}
