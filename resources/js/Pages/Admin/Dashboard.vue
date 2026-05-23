<template>
  <AdminLayout title="Dashboard">
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="stat-card">
        <div class="stat-icon" style="background:#eef2ff">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke="#2b5a41" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800">{{ stats.total_anggota }}</div>
          <div class="text-sm text-slate-500 mt-0.5">Anggota Aktif</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" stroke="#b45309" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800">{{ stats.active_loans }}</div>
          <div class="text-sm text-slate-500 mt-0.5">Pinjaman Aktif</div>
          <div v-if="stats.overdue_loans > 0" class="mt-1">
            <span class="text-xs text-red-500 font-medium">{{ stats.overdue_loans }} terlambat</span>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="#dc2626" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800">{{ formatRupiah(stats.unpaid_fines) }}</div>
          <div class="text-sm text-slate-500 mt-0.5">Denda Belum Lunas</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v8.25m0 0A2.25 2.25 0 0 0 4.5 16.5h15a2.25 2.25 0 0 0 2.25-2.25V8.25m-16.5 8.25h16.5" stroke="#16a34a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800">{{ stats.total_books }}</div>
          <div class="text-sm text-slate-500 mt-0.5">Judul Buku</div>
          <div class="text-xs text-slate-400 mt-0.5">Kunjungan hari ini: {{ stats.visits_today }}</div>
        </div>
      </div>
    </div>

    <!-- Chart Peminjaman -->
    <div class="card mb-6">
      <div class="card-header">
        <div>
          <div class="font-semibold text-slate-800 flex items-center gap-2">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" stroke="#2b5a41" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Tren Peminjaman
          </div>
          <div class="text-xs text-slate-400 mt-0.5">{{ chartPeriodLabel }}</div>
        </div>
        <div class="flex items-center gap-4">
          <!-- Legend -->
          <div class="flex items-center gap-3 text-xs text-slate-500">
            <span class="flex items-center gap-1.5">
              <span class="inline-block w-5 h-0.5 rounded" style="background:#2b5a41"></span>
              {{ activePeriod === 'week' ? 'Minggu ini' : activePeriod === 'year' ? 'Tahun ini' : 'Bulan ini' }}
            </span>
            <span class="flex items-center gap-1.5">
              <span class="inline-block w-5 h-0.5 rounded" style="background:#94a3b8;border-top:2px dashed #94a3b8"></span>
              {{ activePeriod === 'week' ? 'Minggu lalu' : activePeriod === 'year' ? 'Tahun lalu' : 'Bulan lalu' }}
            </span>
          </div>
          <!-- Filter Tabs -->
          <div class="chart-filter-tabs">
            <button v-for="p in periods" :key="p.key"
              @click="activePeriod = p.key"
              :class="['chart-filter-btn', activePeriod === p.key ? 'active' : '']">
              {{ p.label }}
            </button>
          </div>
          <!-- Summary -->
          <div class="text-right">
            <div class="text-sm font-semibold text-slate-800">{{ loanSummary.this_month }} <span class="text-xs font-normal text-slate-400">bulan ini</span></div>
            <div class="flex items-center gap-1 justify-end">
              <span v-if="monthTrend > 0" class="text-xs text-emerald-600 font-semibold">▲ +{{ monthTrend }}</span>
              <span v-else-if="monthTrend < 0" class="text-xs text-red-500 font-semibold">▼ {{ monthTrend }}</span>
              <span v-else class="text-xs text-slate-400 font-medium">— sama</span>
              <span class="text-xs text-slate-400">vs bln lalu</span>
            </div>
          </div>
        </div>
      </div>
      <div class="px-5 pb-5 pt-1" style="height:280px">
        <Line v-if="chartDataset" :data="chartDataset" :options="chartOptions" />
      </div>
    </div>

    <!-- Bottom Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Loans -->
      <div class="lg:col-span-2 card">
        <div class="card-header">
          <div>
            <div class="font-semibold text-slate-800">Peminjaman Terbaru</div>
            <div class="text-xs text-slate-400 mt-0.5">10 transaksi terakhir</div>
          </div>
          <Link :href="route('history.index')" class="text-sm text-indigo-500 font-medium hover:text-indigo-700">Lihat semua →</Link>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in recentLoans" :key="loan.id">
                <td>
                  <div class="font-medium text-slate-800">{{ loan.member?.name }}</div>
                  <div class="text-xs text-slate-400">{{ loan.loan_code }}</div>
                </td>
                <td>
                  <div class="text-xs text-slate-600">{{ loan.items?.length }} buku</div>
                </td>
                <td class="text-sm" :class="isOverdue(loan) ? 'text-red-600 font-semibold' : 'text-slate-600'">
                  {{ formatDate(loan.due_date) }}
                </td>
                <td><LoanBadge :status="loan.status" /></td>
              </tr>
              <tr v-if="recentLoans.length === 0">
                <td colspan="4" class="text-center text-slate-400 py-8">Belum ada data peminjaman</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Panel -->
      <div class="space-y-6">
        <!-- Popular Books -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="font-semibold text-slate-800 flex items-center gap-2">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Buku Terpopuler Minggu Ini
              </div>
            </div>
          </div>
          <div class="divide-y divide-slate-50">
            <div v-for="(book, index) in popularBooks" :key="book.id" class="px-5 py-3 flex items-center gap-4">
              <div class="w-8 h-8 rounded bg-amber-50 flex items-center justify-center text-amber-600 font-bold text-sm shrink-0">
                #{{ index + 1 }}
              </div>
              <div class="w-10 h-10 rounded shrink-0 bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200">
                <img v-if="book.cover_image" :src="book.cover_image" class="w-full h-full object-cover">
                <svg v-else width="20" height="20" fill="none" viewBox="0 0 24 24" class="text-slate-400"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-slate-800 truncate">{{ book.title }}</div>
                <div class="text-xs text-slate-500 truncate">{{ book.author || 'Author tidak diketahui' }}</div>
              </div>
              <div class="shrink-0 text-right">
                <div class="text-xs font-bold text-slate-700">{{ book.borrow_count }}</div>
                <div class="text-[10px] text-slate-400">kali dipinjam</div>
              </div>
            </div>

            <div v-if="popularBooks.length === 0" class="px-5 py-6 text-center text-sm text-slate-400">
              Belum ada peminjaman minggu ini.
            </div>
          </div>
        </div>

        <!-- Overdue Loans -->
        <div class="card">
          <div class="card-header">
            <div class="font-semibold text-slate-800">Pinjaman Terlambat</div>
            <span v-if="overdueLoans.length > 0" class="badge badge-red">{{ overdueLoans.length }}</span>
          </div>
          <div class="divide-y divide-slate-50">
            <div v-for="loan in overdueLoans" :key="loan.id" class="px-5 py-3 flex items-center justify-between">
              <div>
                <div class="text-sm font-medium text-slate-800">{{ loan.member?.name }}</div>
                <div class="text-xs text-red-500 mt-0.5">Jatuh tempo: {{ formatDate(loan.due_date) }}</div>
              </div>
              <Link :href="route('history.index', { search: loan.member?.name, status: 'terlambat' })" class="btn btn-sm btn-secondary">Lihat</Link>
            </div>
            <div v-if="overdueLoans.length === 0" class="px-5 py-6 text-center text-sm text-slate-400">
              Tidak ada pinjaman terlambat ✓
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card card-body">
          <div class="font-semibold text-slate-800 mb-3">Aksi Cepat</div>
          <div class="space-y-2">
            <Link :href="route('loans.index')" class="btn btn-primary w-full justify-center">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M12 4.5v15m7.5-7.5h-15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Peminjaman Baru
            </Link>
            <Link :href="route('returns.index')" class="btn btn-secondary w-full justify-center">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Scan Pengembalian
            </Link>
            <Link :href="route('members.index')" class="btn btn-secondary w-full justify-center">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Kelola Anggota
            </Link>
          </div>
        </div>
      </div>
    </div>

  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/TailAdminLayout.vue'
import LoanBadge from '@/Components/LoanBadge.vue'
import { Link } from '@inertiajs/vue3'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale,
  PointElement, LineElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
  stats:           Object,
  recentLoans:     Array,
  overdueLoans:    Array,
  popularBooks:    Array,
  chartDays:       Array,
  chartWeekCompare: Array,
  chartYearCompare: Array,
  loanSummary:     Object,
})

// ── Chart ──────────────────────────────────────────────
const periods = [
  { key: 'week',  label: 'Minggu' },
  { key: 'month', label: 'Bulan'  },
  { key: 'year',  label: 'Tahun'  },
]
const activePeriod = ref('month')

const activeData = computed(() => {
  if (activePeriod.value === 'week') return props.chartWeekCompare ?? []
  if (activePeriod.value === 'year') return props.chartYearCompare ?? []
  return props.chartDays ?? []
})

const chartPeriodLabel = computed(() => {
  if (activePeriod.value === 'week') return 'Minggu ini vs minggu lalu'
  if (activePeriod.value === 'year') return '12 bulan ini vs 12 bulan lalu'
  return 'Bulan ini vs bulan lalu (per hari)'
})

const monthTrend = computed(() => (props.loanSummary?.this_month ?? 0) - (props.loanSummary?.last_month ?? 0))

const chartDataset = computed(() => {
  const data = activeData.value
  if (!data?.length) return null
  return {
    labels: data.map(d => d.label),
    datasets: [
      {
        label: activePeriod.value === 'week' ? 'Minggu ini' : activePeriod.value === 'year' ? 'Tahun ini' : 'Bulan ini',
        data: data.map(d => d.this_month),
        borderColor: '#2b5a41',
        backgroundColor: (ctx) => {
          const chart = ctx.chart
          const { chartArea, ctx: c } = chart
          if (!chartArea) return 'rgba(43,90,65,0.08)'
          const grad = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
          grad.addColorStop(0, 'rgba(43,90,65,0.18)')
          grad.addColorStop(1, 'rgba(43,90,65,0.0)')
          return grad
        },
        borderWidth: 2.5,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: '#2b5a41',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        tension: 0.4,
        fill: true,
      },
      {
        label: activePeriod.value === 'week' ? 'Minggu lalu' : activePeriod.value === 'year' ? 'Tahun lalu' : 'Bulan lalu',
        data: data.map(d => d.last_month),
        borderColor: '#94a3b8',
        backgroundColor: 'transparent',
        borderWidth: 1.8,
        borderDash: [5, 4],
        pointRadius: 2,
        pointHoverRadius: 5,
        pointBackgroundColor: '#94a3b8',
        pointBorderColor: '#fff',
        pointBorderWidth: 1.5,
        tension: 0.4,
        fill: false,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1e293b',
      titleColor: '#f8fafc',
      bodyColor: '#cbd5e1',
      padding: 10,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y} transaksi`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: {
        font: { size: 10 },
        color: '#94a3b8',
        maxRotation: 0,
        maxTicksLimit: 10,
      },
      border: { display: false },
    },
    y: {
      beginAtZero: true,
      grid: { color: '#1e293b', lineWidth: 1 },
      ticks: {
        font: { size: 10 },
        color: '#94a3b8',
        stepSize: 1,
        maxTicksLimit: 5,
        callback: v => Number.isInteger(v) ? v : '',
      },
      border: { display: false },
    },
  },
  animation: { duration: 400, easing: 'easeInOutQuart' },
}

// ── Helpers ────────────────────────────────────────────
function formatRupiah(n) {
  if (n >= 1000000) return 'Rp ' + (n / 1000000).toFixed(1) + 'jt'
  if (n >= 1000)    return 'Rp ' + (n / 1000).toFixed(0) + 'rb'
  return 'Rp ' + (n || 0)
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function isOverdue(loan) {
  return ['aktif', 'diperpanjang'].includes(loan.status) && new Date(loan.due_date) < new Date()
}


</script>

<style scoped>
/* Filter tabs */
.chart-filter-tabs {
  display: flex;
  gap: 4px;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
}
.chart-filter-btn {
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
  border: none;
  cursor: pointer;
  background: transparent;
  transition: all 0.15s ease;
}
.chart-filter-btn:hover { color: #2b5a41; }
.chart-filter-btn.active {
  background: #fff;
  color: #2b5a41;
  font-weight: 600;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
/* Heatmap */
.heatmap-cell {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  cursor: pointer;
  transition: opacity 0.15s;
}
.heatmap-cell:hover { opacity: 0.75; }
</style>
