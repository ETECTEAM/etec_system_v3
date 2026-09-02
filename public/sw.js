/* ETEC PWA service worker.
   Conservative runtime caching: only same-origin GETs, never Inertia XHR
   responses or the /api routes. The app shell + static assets are kept for
   offline reuse; everything else always hits the network. */

const CACHE_VERSION = "etec-v1";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGE_CACHE = `${CACHE_VERSION}-pages`;

self.addEventListener("install", () => {
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys.filter((key) => !key.startsWith(CACHE_VERSION)).map((key) => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const request = event.request;

  if (request.method !== "GET") return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Inertia visits are XHR (X-Inertia header) — always network, never cached.
  if (request.headers.get("X-Inertia")) return;

  if (url.pathname.startsWith("/api/")) return;

  // Full page navigations: network first, fall back to the cached app shell.
  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const copy = response.clone();
            event.waitUntil(
              caches.open(PAGE_CACHE).then((cache) => cache.put("/student-register", copy))
            );
          }
          return response;
        })
        .catch(async () => {
          const cached = await caches.match("/student-register");
          return cached || Response.error();
        })
    );
    return;
  }

  // Same-origin static assets: serve from cache, refresh in the background.
  if (/\.(?:css|js|png|jpe?g|gif|svg|webp|avif|woff2?|ttf|ico|webmanifest|json)$/.test(url.pathname)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        const network = fetch(request)
          .then((response) => {
            if (response.ok) {
              const copy = response.clone();
              event.waitUntil(
                caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy))
              );
            }
            return response;
          })
          .catch(() => cached);

        return cached || network;
      })
    );
  }
});