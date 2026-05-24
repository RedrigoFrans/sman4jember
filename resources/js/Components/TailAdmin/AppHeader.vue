<template>
  <header class="sticky top-0 flex w-full bg-white border-gray-200 z-40 dark:border-gray-800 dark:bg-gray-900 lg:border-b transition-colors">
    <div class="flex flex-col items-center justify-between grow lg:flex-row lg:px-6">
      <div class="flex items-center justify-between w-full gap-2 px-3 py-3 border-b border-gray-200 dark:border-gray-800 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4">
        <!-- Sidebar Toggle -->
        <button
          @click="handleToggle"
          class="flex items-center justify-center w-10 h-10 text-gray-500 border-gray-200 rounded-lg dark:border-gray-800 dark:text-gray-400 lg:h-11 lg:w-11 lg:border hover:bg-gray-50 dark:hover:bg-gray-800"
          :class="[isMobileOpen ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800' : '']"
        >
          <MenuIcon v-if="!isMobileOpen" class="w-5 h-5" />
          <svg v-else class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"/>
          </svg>
        </button>

        <!-- Mobile Logo -->
        <div class="lg:hidden font-bold text-xl tracking-tight text-indigo-600 dark:text-indigo-400">
          SMA NEGERI 4 JEMBER
        </div>

        <button
          @click="toggleApplicationMenu"
          class="flex items-center justify-center w-10 h-10 text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
        >
          <HorizontalDots class="w-5 h-5" />
        </button>

        <!-- Search Bar -->
        <SearchBar />
      </div>

      <!-- Right Menu Actions -->
      <div
        :class="[isApplicationMenuOpen ? 'flex' : 'hidden']"
        class="items-center justify-between w-full gap-4 px-5 py-4 shadow-theme-md lg:flex lg:justify-end lg:px-0 lg:shadow-none bg-white dark:bg-gray-900 transition-colors"
      >
        <div class="flex items-center gap-2 2xsm:gap-3">
          <ThemeToggler />
          
          <!-- Notification Dropdown (admin only) -->
          <NotificationDropdown v-if="isAdmin" />
        </div>

        <UserMenu />
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useSidebar } from '@/Composables/useSidebar'
import ThemeToggler from './ThemeToggler.vue'
import SearchBar from './SearchBar.vue'
import UserMenu from './UserMenu.vue'
import NotificationDropdown from './NotificationDropdown.vue'
import MenuIcon from '@/Components/TailAdminIcons/MenuIcon.vue'
import HorizontalDots from '@/Components/TailAdminIcons/HorizontalDots.vue'

const page = usePage()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

const { toggleSidebar, toggleMobileSidebar, isMobileOpen } = useSidebar()

const handleToggle = () => {
  if (window.innerWidth >= 1024) {
    toggleSidebar()
  } else {
    toggleMobileSidebar()
  }
}

const isApplicationMenuOpen = ref(false)
const toggleApplicationMenu = () => {
  isApplicationMenuOpen.value = !isApplicationMenuOpen.value
}
</script>
