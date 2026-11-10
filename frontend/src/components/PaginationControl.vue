<script setup lang="ts">
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

interface Props {
  page: number;
  pageSize: number;
  total: number;
  pageSizeOptions?: number[];
  label?: string;
}

const props = withDefaults(defineProps<Props>(), {
  pageSizeOptions: () => [10, 25, 50, 100],
  label: 'data'
});

const emit = defineEmits<{
  (e: 'update:page', val: number): void;
  (e: 'update:pageSize', val: number): void;
}>();

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(props.total / props.pageSize));
});

const startIndex = computed(() => {
  if (props.total === 0) return 0;
  return (props.page - 1) * props.pageSize + 1;
});

const endIndex = computed(() => {
  return Math.min(props.total, props.page * props.pageSize);
});

function setPage(p: number) {
  if (p < 1 || p > totalPages.value || p === props.page) return;
  emit('update:page', p);
}

function onPageSizeChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  const newSize = Number(target.value) || 10;
  emit('update:pageSize', newSize);
  emit('update:page', 1);
}

// Compute visible pagination pages with ellipsis
const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = props.page;

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  if (current <= 4) {
    return [1, 2, 3, 4, 5, '...', total];
  }

  if (current >= total - 3) {
    return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
  }

  return [1, '...', current - 1, current, current + 1, '...', total];
});
</script>

<template>
  <div v-if="total > 0" class="pagination-bar">
    <!-- Info Range Data -->
    <div class="pagination-info">
      <span>
        Menampilkan <strong>{{ startIndex }}</strong> &ndash; <strong>{{ endIndex }}</strong> dari <strong>{{ total.toLocaleString('id-ID') }}</strong> {{ label }}
      </span>
    </div>

    <!-- Cluster Kontrol Navigasi & Ukuran Halaman -->
    <div class="pagination-controls">
      <!-- Selector Per Halaman -->
      <div class="page-size-selector">
        <label class="page-size-lbl">Per hal:</label>
        <select :value="pageSize" class="page-size-select" @change="onPageSizeChange">
          <option v-for="opt in pageSizeOptions" :key="opt" :value="opt">
            {{ opt }}
          </option>
        </select>
      </div>

      <!-- Tombol Awal (First) -->
      <button
        type="button"
        class="pg-btn"
        :disabled="page <= 1"
        title="Halaman Pertama"
        @click="setPage(1)"
      >
        <ChevronsLeft :size="14" />
      </button>

      <!-- Tombol Sebelumnya (Prev) -->
      <button
        type="button"
        class="pg-btn"
        :disabled="page <= 1"
        title="Halaman Sebelumnya"
        @click="setPage(page - 1)"
      >
        <ChevronLeft :size="14" />
      </button>

      <!-- Nomor Halaman -->
      <div class="pg-numbers">
        <template v-for="(p, idx) in visiblePages" :key="idx">
          <span v-if="p === '...'" class="pg-ellipsis">&hellip;</span>
          <button
            v-else
            type="button"
            class="pg-btn pg-num"
            :class="{ active: p === page }"
            @click="setPage(Number(p))"
          >
            {{ p }}
          </button>
        </template>
      </div>

      <!-- Tombol Berikutnya (Next) -->
      <button
        type="button"
        class="pg-btn"
        :disabled="page >= totalPages"
        title="Halaman Berikutnya"
        @click="setPage(page + 1)"
      >
        <ChevronRight :size="14" />
      </button>

      <!-- Tombol Akhir (Last) -->
      <button
        type="button"
        class="pg-btn"
        :disabled="page >= totalPages"
        title="Halaman Terakhir"
        @click="setPage(totalPages)"
      >
        <ChevronsRight :size="14" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 10px;
  margin-top: 16px;
  backdrop-filter: blur(8px);
}

.pagination-info {
  font-size: 0.8rem;
  color: #94a3b8;
}

.pagination-info strong {
  color: #f1f5f9;
  font-weight: 600;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.page-size-selector {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-right: 8px;
}

.page-size-lbl {
  font-size: 0.75rem;
  color: #64748b;
  white-space: nowrap;
}

.page-size-select {
  background: rgba(30, 41, 59, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.25);
  color: #e2e8f0;
  font-size: 0.78rem;
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}

.page-size-select:focus {
  border-color: #38bdf8;
}

.pg-numbers {
  display: flex;
  align-items: center;
  gap: 4px;
}

.pg-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 30px;
  height: 30px;
  padding: 0 6px;
  font-size: 0.78rem;
  font-weight: 500;
  color: #cbd5e1;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pg-btn:hover:not(:disabled) {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  border-color: rgba(56, 189, 248, 0.35);
}

.pg-btn.active {
  background: #0284c7;
  color: #ffffff;
  border-color: #0284c7;
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
  font-weight: 700;
}

.pg-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
  pointer-events: none;
}

.pg-ellipsis {
  font-size: 0.8rem;
  color: #64748b;
  padding: 0 4px;
}

@media (max-width: 640px) {
  .pagination-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .pagination-controls {
    justify-content: center;
  }
}
</style>

