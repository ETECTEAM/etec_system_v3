<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowRight,
  CalendarDays,
  Star,
  User,
} from "@lucide/vue";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const defaultImage = "https://i.pinimg.com/736x/6e/f1/55/6ef155e25a6a517d8c89a46ebba37d71.jpg";

const imageUrl = computed(() => {
  const activeImage = props.item.images?.find(
    (image) => image.is_active && image.image_url,
  );

  return (
    activeImage?.image_url ??
    props.item.images?.[0]?.image_url ??
    defaultImage
  );
});

const summary = computed(() => {
  if (props.item.excerpt) {
    return props.item.excerpt;
  }

  const plainText = (props.item.description ?? "")
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim();

  return plainText.length > 145
    ? `${plainText.slice(0, 145)}...`
    : plainText;
});

const formattedDate = computed(() => {
  if (!props.item.published_at) {
    return "Recently published";
  }

  const date = new Date(`${props.item.published_at}T00:00:00`);

  if (Number.isNaN(date.getTime())) {
    return props.item.published_at;
  }

  return new Intl.DateTimeFormat("en-US", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(date);
});

function handleImageError(event) {
  if (event.target.src.endsWith(defaultImage)) {
    return;
  }

  event.target.src = defaultImage;
}
</script>

<template>
  <article
    class="group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-500 ease-out hover:-translate-y-2 hover:border-[#1e5aa8]/20 hover:shadow-2xl"
  >
    <Link
      :href="`/news/${item.slug}`"
      class="relative block overflow-hidden bg-slate-100"
    >
      <img
        :src="imageUrl"
        :alt="item.title"
        class="aspect-[16/10] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
        loading="lazy"
        @error="handleImageError"
      />

      <div
        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/55 via-transparent to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"
      ></div>

      <span
        v-if="item.is_featured"
        class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-[#f4a261] px-3 py-1.5 text-xs font-black text-slate-950 shadow-lg"
      >
        <Star class="h-3.5 w-3.5 fill-current" />
        Featured
      </span>
    </Link>

    <div class="flex flex-1 flex-col p-6">
      <div class="flex flex-wrap gap-4 text-xs font-bold text-slate-500">
        <span class="inline-flex items-center gap-1.5">
          <CalendarDays class="h-4 w-4 text-[#1e5aa8]" />
          {{ formattedDate }}
        </span>

        <span
          v-if="item.author"
          class="inline-flex items-center gap-1.5"
        >
          <User class="h-4 w-4 text-[#1e5aa8]" />
          {{ item.author }}
        </span>
      </div>

      <h3
        class="mt-4 line-clamp-2 text-xl font-black text-slate-950 transition-colors duration-300 group-hover:text-[#1e5aa8]"
      >
        <Link :href="`/news/${item.slug}`">
          {{ item.title }}
        </Link>
      </h3>

      <p class="mt-3 line-clamp-3 text-sm leading-7 text-slate-600">
        {{ summary }}
      </p>

      <div class="mt-auto pt-6">
        <Link
          :href="`/news/${item.slug}`"
          class="inline-flex items-center gap-2 text-sm font-black text-[#1e5aa8] transition-all duration-300 group-hover:gap-3 group-hover:text-[#f4a261]"
        >
          Read More
          <ArrowRight class="h-4 w-4" />
        </Link>
      </div>
    </div>
  </article>
</template>