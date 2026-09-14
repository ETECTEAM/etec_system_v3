export const key = "website";

// Keep the Website Management sidebar group scoped to the public-site admin routes.
// The menu is hidden from instructors and other roles without admin access.
export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/website");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  // This parent item groups the settings and content-management screens so the
  // sidebar stays compact while each website area keeps its own active route.
  return {
    label: "Website Management",
    labelKey: "navigation.websiteManagement",
    key,
    match: ["/dashboard/website"],
    icon: "website",
    children: [
      {
        // Controls the public school name and logo shown across the website.
        label: "School Settings",
        labelKey: "navigation.schoolSettings",
        href: "/dashboard/website/school-settings",
        match: ["/dashboard/website/school-settings"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/school-settings"),
      },
      {
        // Creates and edits public website pages and their hero content.
        label: "Page Management",
        labelKey: "navigation.pageManagement",
        href: "/dashboard/website/pages",
        match: ["/dashboard/website/pages"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/pages"),
      },
      {
        // Controls the public navigation structure and connected pages.
        label: "Menu Management",
        labelKey: "navigation.menuManagement",
        href: "/dashboard/website/menus",
        match: ["/dashboard/website/menus"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/menus"),
      },
      {
        // Manages published website videos and their playback metadata.
        label: "Video Management",
        labelKey: "navigation.videoManagement",
        href: "/dashboard/website/videos",
        match: ["/dashboard/website/videos"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/videos"),
      },
      {
        // Manages published news articles, images, and article details.
        label: "News Management",
        labelKey: "navigation.newsManagement",
        href: "/dashboard/website/news",
        match: ["/dashboard/website/news"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/news"),
      },
    ],
  };
}
