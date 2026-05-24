<template>
  <aside
    :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-50 border-r border-gray-200',
      {
        'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
        'lg:w-[90px]': !isExpanded && !isHovered,
        'translate-x-0 w-[290px]': isMobileOpen,
        '-translate-x-full': !isMobileOpen,
        'lg:translate-x-0': true,
      },
    ]"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <!-- Logo -->
    <div :class="['py-8 flex', !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start']">
      <Link :href="route('dashboard')" class="flex items-center gap-3">
        <!-- Logo Icon (collapsed) atau full (expanded) -->
        <div class="w-10 h-10 rounded-xl flex items-center justify-center p-1 bg-white border border-gray-100 flex-shrink-0 shadow-sm">
          <img src="/images/logo-sman4-jember.png" alt="Logo SMAN 4" class="w-full h-full object-contain" />
        </div>
        <div v-if="isExpanded || isHovered || isMobileOpen" class="font-bold text-2xl tracking-tight text-indigo-900 dark:text-white">
          SMA NEGERI 4 JEMBER<span class="text-indigo-500"></span>
        </div>
      </Link>
    </div>

    <!-- Menus -->
    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
      <nav class="mb-6">
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <h2
              :class="[
                'mb-4 text-xs font-semibold uppercase flex leading-[20px] text-gray-400 dark:text-gray-500',
                !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
              ]"
            >
              <template v-if="isExpanded || isHovered || isMobileOpen">
                {{ menuGroup.title }}
              </template>
              <HorizontalDots v-else class="w-5 h-5" />
            </h2>
            
            <ul class="flex flex-col gap-2">
              <li v-for="item in menuGroup.items" :key="item.name">
                <Link
                  v-if="item.route"
                  :href="route(item.route)"
                  :class="[
                    'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-colors duration-200',
                    isActive(item.route) 
                      ? 'bg-indigo-50 text-indigo-700 dark:bg-white/10 dark:text-white' 
                      : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white',
                    !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start'
                  ]"
                >
                  <component
                    :is="item.icon"
                    :class="[
                      'w-5 h-5 transition-colors',
                      isActive(item.route) ? 'text-indigo-600 dark:text-white' : 'text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300'
                    ]"
                  />
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="truncate"
                  >
                    {{ item.name }}
                  </span>
                  
                  <!-- Badge for pending counts -->
                  <span
                    v-if="(isExpanded || isHovered || isMobileOpen) && item.badge && item.badge > 0"
                    class="absolute py-[2px] right-3 rounded-full bg-red-500 text-white text-[10px] font-bold px-2 leading-none"
                  >
                    {{ item.badge }}
                  </span>
                </Link>
              </li>
            </ul>
          </div>
        </div>
      </nav>

      <SidebarWidget v-if="isExpanded || isHovered || isMobileOpen" />
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useSidebar } from '@/Composables/useSidebar'
import SidebarWidget from './SidebarWidget.vue'

// Icons
import GridIcon from '@/Components/TailAdminIcons/GridIcon.vue'
import UserGroupIcon from '@/Components/TailAdminIcons/UserGroupIcon.vue'
import BookIcon from '@/Components/TailAdminIcons/DocsIcon.vue' // Placeholder using DocsIcon
import LoanIcon from '@/Components/TailAdminIcons/ArchiveIcon.vue'
import ReturnIcon from '@/Components/TailAdminIcons/RefreshIcon.vue'
import FineIcon from '@/Components/TailAdminIcons/BoxCubeIcon.vue'
import SettingsIcon from '@/Components/TailAdminIcons/SettingsIcon.vue'
import TableIcon from '@/Components/TailAdminIcons/TableIcon.vue'
import ListIcon from '@/Components/TailAdminIcons/ListIcon.vue'
import HorizontalDots from '@/Components/TailAdminIcons/HorizontalDots.vue'
import BarChartIcon from '@/Components/TailAdminIcons/BarChartIcon.vue'
import PieChartIcon from '@/Components/TailAdminIcons/PieChartIcon.vue'

const page = usePage()
const { isExpanded, isMobileOpen, isHovered } = useSidebar()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

const menuGroups = computed(() => [
  {
    title: 'Utama',
    items: [
      { name: 'Dashboard', route: 'dashboard', icon: GridIcon },
    ],
  },
  {
    title: 'Sirkulasi',
    items: [
      { name: 'Peminjaman', route: 'loans.index', icon: LoanIcon },
      { name: 'Pengembalian', route: 'returns.index', icon: ReturnIcon },
      { name: 'Presensi', route: 'visits.index', icon: UserGroupIcon },
      { name: 'Riwayat', route: 'history.index', icon: ListIcon },
      { name: 'Denda', route: 'fines.index', icon: FineIcon },
    ],
  },
  {
    title: 'Laporan',
    items: [
      { name: 'Laporan Denda', route: 'reports.fines', icon: BarChartIcon },
      { name: 'Laporan Presensi', route: 'reports.attendance', icon: PieChartIcon },
    ],
  },
  {
    title: 'Katalog & Master',
    items: [
      { name: 'Buku', route: 'books.index', icon: BookIcon },
      { name: 'Data Kelas', route: 'kelas.index', icon: TableIcon },
      { name: 'Anggota', route: 'members.index', icon: UserGroupIcon, badge: page.props.pendingCount },
      ...(isAdmin.value ? [{ name: 'Pengaturan', route: 'settings.index', icon: SettingsIcon }] : []),
    ],
  },
])

function isActive(routeName) {
  // Simple check for inertia current route pattern
  return route().current(routeName) || route().current(routeName.replace('.index', '.*'))
}
</script>
