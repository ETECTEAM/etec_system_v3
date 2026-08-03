import { ref, onMounted } from "vue";

const news = ref([]);
const meta = ref({});
const loading = ref(false);

const search = ref("");
const featured = ref("");
const page = ref(1);
const perPage = ref(12);

async function fetchNews() {
    loading.value = true;

    try {
        const params = new URLSearchParams({
            page: page.value.toString(),
            per_page: perPage.value.toString(),
        });

        if (search.value.trim()) {
            params.set("search", search.value.trim());
        }

        if (featured.value) {
            params.set("featured", featured.value);
        }

        const response = await fetch(`/api/public/news?${params.toString()}`);

        if (!response.ok) {
            throw new Error("Failed to load news.");
        }

        const result = await response.json();

        news.value = result.data;
        meta.value = result.meta;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
}

onMounted(fetchNews);