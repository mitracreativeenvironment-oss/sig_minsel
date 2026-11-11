<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface Props {
  title?: string;
  subtitle?: string;
  type: 'bar' | 'line' | 'doughnut' | 'pie' | 'polarArea';
  data: {
    labels: string[];
    datasets: {
      label?: string;
      data: number[];
      backgroundColor?: string | string[];
      borderColor?: string | string[];
      borderWidth?: number;
      fill?: boolean;
      tension?: number;
      borderRadius?: number;
      hoverOffset?: number;
    }[];
  };
  options?: any;
  height?: number;
  badge?: string;
  showLegend?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  height: 220,
  badge: '',
  showLegend: true
});

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const hasData = computed(() => {
  if (!props.data || !props.data.datasets || props.data.datasets.length === 0) return false;
  return props.data.datasets.some((d) => d.data && d.data.some((val) => val > 0));
});

function getDefaultOptions() {
  const isPieOrDoughnut = ['doughnut', 'pie', 'polarArea'].includes(props.type);

  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
      duration: 600,
      easing: 'easeOutQuart'
    },
    plugins: {
      legend: {
        display: props.showLegend,
        position: isPieOrDoughnut ? 'right' : 'top',
        labels: {
          color: '#cbd5e1',
          font: {
            family: "'Plus Jakarta Sans', system-ui, sans-serif",
            size: 11,
            weight: '500'
          },
          boxWidth: 12,
          boxHeight: 12,
          padding: 12
        }
      },
      tooltip: {
        backgroundColor: 'rgba(15, 23, 42, 0.92)',
        titleColor: '#38bdf8',
        bodyColor: '#f8fafc',
        borderColor: 'rgba(56, 189, 248, 0.25)',
        borderWidth: 1,
        padding: 10,
        boxPadding: 4,
        cornerRadius: 8,
        titleFont: {
          family: "'Plus Jakarta Sans', system-ui, sans-serif",
          weight: '600',
          size: 12
        },
        bodyFont: {
          family: "'Plus Jakarta Sans', system-ui, sans-serif",
          size: 12
        }
      }
    },
    scales: isPieOrDoughnut
      ? {}
      : {
          x: {
            grid: {
              color: 'rgba(255, 255, 255, 0.05)',
              drawBorder: false
            },
            ticks: {
              color: '#94a3b8',
              font: {
                family: "'Plus Jakarta Sans', system-ui, sans-serif",
                size: 10
              },
              maxRotation: 35,
              minRotation: 0
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(255, 255, 255, 0.05)',
              drawBorder: false
            },
            ticks: {
              color: '#94a3b8',
              precision: 0,
              font: {
                family: "'Plus Jakarta Sans', system-ui, sans-serif",
                size: 10
              }
            }
          }
        }
  };
}

function initChart() {
  if (!canvasRef.value) return;

  if (chartInstance) {
    chartInstance.destroy();
    chartInstance = null;
  }

  const mergedOptions = {
    ...getDefaultOptions(),
    ...(props.options || {})
  };

  chartInstance = new Chart(canvasRef.value, {
    type: props.type,
    data: JSON.parse(JSON.stringify(props.data)),
    options: mergedOptions
  });
}

function updateChart() {
  if (!chartInstance) {
    initChart();
    return;
  }
  chartInstance.data = JSON.parse(JSON.stringify(props.data));
  const mergedOptions = {
    ...getDefaultOptions(),
    ...(props.options || {})
  };
  chartInstance.options = mergedOptions;
  chartInstance.update();
}

watch(
  () => props.data,
  () => {
    updateChart();
  },
  { deep: true }
);

watch(
  () => props.type,
  () => {
    initChart();
  }
);

onMounted(() => {
  initChart();
});

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
    chartInstance = null;
  }
});
</script>

<template>
  <div class="stat-chart-card">
    <div v-if="title || subtitle || badge" class="stat-chart-header">
      <div class="header-text-group">
        <h4 v-if="title" class="stat-chart-title">{{ title }}</h4>
        <p v-if="subtitle" class="stat-chart-sub">{{ subtitle }}</p>
      </div>
      <span v-if="badge" class="stat-chart-badge">{{ badge }}</span>
    </div>

    <div class="chart-canvas-wrapper" :style="{ height: `${height}px` }">
      <canvas ref="canvasRef"></canvas>
      <div v-if="!hasData" class="chart-empty-overlay">
        <span class="empty-text">Belum ada rekaman statistik</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stat-chart-card {
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.stat-chart-card:hover {
  border-color: rgba(56, 189, 248, 0.25);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35);
}

.stat-chart-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 0.85rem;
  gap: 0.75rem;
}

.header-text-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.stat-chart-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: #f1f5f9;
  letter-spacing: -0.01em;
}

.stat-chart-sub {
  font-size: 0.73rem;
  color: #94a3b8;
  line-height: 1.35;
}

.stat-chart-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  font-size: 0.65rem;
  font-weight: 600;
  background: rgba(56, 189, 248, 0.12);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.25);
  white-space: nowrap;
}

.chart-canvas-wrapper {
  position: relative;
  width: 100%;
}

.chart-empty-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 8px;
  pointer-events: none;
}

.empty-text {
  font-size: 0.75rem;
  color: #64748b;
  font-style: italic;
}
</style>

