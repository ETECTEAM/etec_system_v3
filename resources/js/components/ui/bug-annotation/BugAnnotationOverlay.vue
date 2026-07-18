<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Undo2, Trash2, X } from "@lucide/vue";

const emit = defineEmits(["close"]);

const canvasRef = ref(null);
let ctx = null;

const colors = [
  { name: "Red", value: "#ef4444" },
  { name: "Orange", value: "#f97316" },
  { name: "Yellow", value: "#eab308" },
  { name: "Blue", value: "#2563eb" },
  { name: "Green", value: "#16a34a" },
  { name: "Black", value: "#111827" },
];

const activeColor = ref(colors[0].value);
const brushSize = ref(4);

// Hotspot (2, 22) lines up with the nib in the path below so the drawn
// stroke starts exactly under the visible pen tip, not the icon's corner.
const cursorStyle = computed(() => {
  const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='${activeColor.value}' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .622.622l4.353-1.32a2 2 0 0 0 .83-.497Z'/><path d='m15 5 4 4'/></svg>`;
  return `url("data:image/svg+xml,${encodeURIComponent(svg)}") 2 22, crosshair`;
});

const strokes = [];
let currentStroke = null;
let isDrawing = false;

function resizeCanvas() {
  const canvas = canvasRef.value;
  if (!canvas) return;

  const dpr = window.devicePixelRatio || 1;
  const width = window.innerWidth;
  const height = window.innerHeight;

  canvas.width = width * dpr;
  canvas.height = height * dpr;
  canvas.style.width = `${width}px`;
  canvas.style.height = `${height}px`;

  ctx = canvas.getContext("2d");
  ctx.scale(dpr, dpr);
  redraw();
}

function redraw() {
  if (!ctx) return;
  ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
  for (const stroke of strokes) {
    drawStroke(stroke);
  }
}

function drawStroke(stroke) {
  if (!ctx || stroke.points.length < 2) return;

  ctx.strokeStyle = stroke.color;
  ctx.lineWidth = stroke.size;
  ctx.lineJoin = "round";
  ctx.lineCap = "round";
  ctx.beginPath();
  ctx.moveTo(stroke.points[0].x, stroke.points[0].y);
  for (let i = 1; i < stroke.points.length; i += 1) {
    ctx.lineTo(stroke.points[i].x, stroke.points[i].y);
  }
  ctx.stroke();
}

function getPoint(event) {
  const rect = canvasRef.value.getBoundingClientRect();
  return { x: event.clientX - rect.left, y: event.clientY - rect.top };
}

function handlePointerDown(event) {
  isDrawing = true;
  currentStroke = {
    color: activeColor.value,
    size: brushSize.value,
    points: [getPoint(event)],
  };
  strokes.push(currentStroke);
  canvasRef.value.setPointerCapture(event.pointerId);
}

function handlePointerMove(event) {
  if (!isDrawing || !currentStroke) return;

  const point = getPoint(event);
  const previous = currentStroke.points[currentStroke.points.length - 1];
  currentStroke.points.push(point);

  ctx.strokeStyle = currentStroke.color;
  ctx.lineWidth = currentStroke.size;
  ctx.lineJoin = "round";
  ctx.lineCap = "round";
  ctx.beginPath();
  ctx.moveTo(previous.x, previous.y);
  ctx.lineTo(point.x, point.y);
  ctx.stroke();
}

function handlePointerUp(event) {
  isDrawing = false;
  currentStroke = null;
  try {
    canvasRef.value.releasePointerCapture(event.pointerId);
  } catch {
    // pointer capture may already be released
  }
}

function undo() {
  strokes.pop();
  redraw();
}

function clearAll() {
  strokes.length = 0;
  redraw();
}

function close() {
  emit("close");
}

function handleKeydown(event) {
  if (event.key === "Escape") close();
}

onMounted(() => {
  resizeCanvas();
  window.addEventListener("resize", resizeCanvas);
  window.addEventListener("keydown", handleKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeCanvas);
  window.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999]" @mousedown.stop @click.stop>
      <canvas
        ref="canvasRef"
        class="absolute inset-0 h-full w-full touch-none"
        :style="{ cursor: cursorStyle }"
        @pointerdown="handlePointerDown"
        @pointermove="handlePointerMove"
        @pointerup="handlePointerUp"
        @pointercancel="handlePointerUp"
      />

      <div
        class="fixed left-1/2 top-4 z-[10000] -translate-x-1/2 rounded-full bg-slate-900/80 px-3 py-1 text-xs font-medium text-white"
      >
        Draw on the page to mark an issue · Esc to exit
      </div>

      <div
        class="fixed bottom-6 left-1/2 z-[10000] flex -translate-x-1/2 items-center gap-3 rounded-full border border-slate-200 bg-white/95 px-4 py-2 shadow-lg backdrop-blur"
      >
        <div class="flex items-center gap-1.5">
          <button
            v-for="color in colors"
            :key="color.value"
            type="button"
            :title="color.name"
            class="h-6 w-6 rounded-full border-2 transition"
            :class="activeColor === color.value ? 'scale-110 border-slate-900' : 'border-white'"
            :style="{ backgroundColor: color.value }"
            @click="activeColor = color.value"
          />
        </div>

        <input
          v-model.number="brushSize"
          type="range"
          min="2"
          max="14"
          step="1"
          class="w-20 accent-blue-600"
          title="Brush size"
        />

        <div class="h-6 w-px bg-slate-200" />

        <button
          type="button"
          class="rounded-lg p-1.5 text-slate-600 transition hover:bg-slate-100"
          title="Undo"
          @click="undo"
        >
          <Undo2 class="h-4 w-4" />
        </button>
        <button
          type="button"
          class="rounded-lg p-1.5 text-slate-600 transition hover:bg-slate-100"
          title="Clear all"
          @click="clearAll"
        >
          <Trash2 class="h-4 w-4" />
        </button>

        <div class="h-6 w-px bg-slate-200" />

        <button
          type="button"
          class="flex items-center gap-1 rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700"
          @click="close"
        >
          <X class="h-3.5 w-3.5" />
          Done
        </button>
      </div>
    </div>
  </Teleport>
</template>
