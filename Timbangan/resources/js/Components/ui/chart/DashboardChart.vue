<script setup>
import { computed } from 'vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  data: {
    type: Array,
    required: true,
  }
});

const chartData = computed(() => {
  return {
    labels: props.data.map(d => d.name),
    datasets: [
      {
        label: 'Total Berat (kg)',
        backgroundColor: '#4f46e5', // indigo-600
        borderRadius: 4,
        data: props.data.map(d => d.berat),
        yAxisID: 'y',
        order: 1
      },
      {
        label: 'Total Item',
        backgroundColor: '#e0e7ff', // indigo-100
        borderRadius: 4,
        data: props.data.map(d => d.total),
        yAxisID: 'y1',
        order: 2
      }
    ]
  };
});

const chartDataKey = computed(() => {
  return props.data.map(d => `${d.name}-${d.total}-${d.berat}`).join('|');
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      display: true,
      position: 'bottom',
      labels: {
        usePointStyle: true,
        boxWidth: 8,
        font: {
          family: "'Inter', sans-serif",
          weight: 'bold'
        }
      }
    },
    tooltip: {
      backgroundColor: '#111827',
      titleFont: { family: "'Inter', sans-serif", size: 13 },
      bodyFont: { family: "'Inter', sans-serif", size: 12 },
      padding: 12,
      cornerRadius: 8,
      callbacks: {
        label: function(context) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.parsed.y !== null) {
            label += new Intl.NumberFormat('id-ID').format(context.parsed.y);
            if (context.dataset.label.includes('Berat')) {
              label += ' kg';
            }
          }
          return label;
        }
      }
    }
  },
  scales: {
    x: {
      grid: {
        display: false,
        drawBorder: false,
      },
      ticks: {
        font: { family: "'Inter', sans-serif", size: 11, weight: 'bold' },
        color: '#6b7280'
      }
    },
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      grid: {
        color: '#f3f4f6',
        borderDash: [4, 4],
        drawBorder: false,
      },
      ticks: {
        font: { family: "'Inter', sans-serif", size: 11 },
        color: '#9ca3af'
      }
    },
    y1: {
      type: 'linear',
      display: true,
      position: 'right',
      grid: {
        drawOnChartArea: false,
      },
      ticks: {
        font: { family: "'Inter', sans-serif", size: 11 },
        color: '#9ca3af'
      }
    }
  }
};
</script>

<template>
  <div class="w-full h-[300px] md:h-[400px]">
    <Bar
      :key="chartDataKey"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>
