<template>
  <div class="bg-background text-on-surface font-body-md overflow-x-hidden min-h-screen flex flex-col">
    <!-- ── NAV ── -->
    <Disclosure as="header"
      class="fixed top-0 w-full z-50 bg-surface/70 backdrop-blur-xl border-b border-white/20 shadow-sm transition-all"
      v-slot="{ open }">
      <nav class="flex justify-between items-center max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-4">
        
        <!-- Mobile hamburger -->
        <div class="flex items-center sm:hidden">
          <DisclosureButton class="text-primary hover:opacity-80 p-1 focus:outline-none">
            <span class="sr-only">Buka menu</span>
            <Bars3Icon v-if="!open" class="block h-6 w-6" />
            <XMarkIcon v-else class="block h-6 w-6" />
          </DisclosureButton>
        </div>

        <!-- Logo -->
        <div class="flex-1 flex justify-center sm:justify-start">
          <Link :href="route('home')" class="font-headline-md text-[24px] sm:text-headline-md font-bold text-primary no-underline flex items-center gap-2">
            <img src="/images/logo-sman4-jember.png" alt="Logo" class="h-8 sm:h-10 w-auto drop-shadow-sm" />
            <span class="hidden sm:inline">Perpustakaan Digital</span>
          </Link>
        </div>

        <!-- Desktop nav -->
        <div class="hidden sm:flex gap-8 items-center">
          <Link v-for="item in navigation" :key="item.name"
            :href="item.href"
            :class="[
              isActivePage(item.key)
                ? 'text-primary relative after:content-[\'\'] after:absolute after:-bottom-1 after:left-1/2 after:-translate-x-1/2 after:w-1 after:h-1 after:bg-primary after:rounded-full'
                : 'text-on-surface-variant hover:text-primary transition-colors',
              'font-title-lg text-[18px] no-underline'
            ]">
            {{ item.name }}
          </Link>
        </div>

        <!-- Right: auth -->
        <div class="flex items-center gap-4 ml-4">
          <template v-if="$page.props.auth?.user">
            <Menu as="div" class="relative">
              <MenuButton class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 text-sm bg-surface-container hover:bg-surface-container-high transition-colors border-none cursor-pointer">
                <div class="w-[32px] h-[32px] rounded-full flex items-center justify-center font-bold text-on-primary bg-primary shrink-0">
                  {{ $page.props.auth.user.name[0].toUpperCase() }}
                </div>
                <span class="hidden sm:block font-medium text-primary">{{ $page.props.auth.user.name.split(' ')[0] }}</span>
                <ChevronDownIcon class="hidden sm:block h-4 w-4 text-outline" />
              </MenuButton>

              <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="absolute right-0 z-10 mt-2 w-48 rounded-xl bg-surface border border-outline-variant shadow-xl overflow-hidden outline-none">
                  <div class="px-4 py-3 border-b border-outline-variant">
                    <p class="text-xs text-on-surface-variant">Masuk sebagai</p>
                    <p class="text-sm font-semibold text-on-surface truncate">{{ $page.props.auth.user.name }}</p>
                  </div>
                  <MenuItem v-slot="{ active }" v-if="['admin', 'petugas'].includes($page.props.auth.user.role)">
                    <Link :href="route('dashboard')" :class="[active ? 'bg-surface-container' : '', 'flex items-center gap-2 px-4 py-2.5 text-sm text-on-surface-variant transition-colors no-underline']">
                      📊 Dashboard Menu
                    </Link>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <Link :href="route('catalog')" :class="[active ? 'bg-surface-container' : '', 'flex items-center gap-2 px-4 py-2.5 text-sm text-on-surface-variant transition-colors no-underline']">
                      📚 Jelajahi Katalog
                    </Link>
                  </MenuItem>
                  <div class="border-t border-outline-variant">
                    <MenuItem v-slot="{ active }">
                      <Link :href="route('logout')" method="post" as="button" :class="[active ? 'bg-error-container' : '', 'flex w-full items-center gap-2 px-4 py-2.5 text-sm text-error transition-colors']">
                        🚪 Keluar
                      </Link>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>
          </template>

          <template v-else>
            <Link :href="route('login')" class="hidden md:block text-primary font-semibold hover:opacity-80 transition-opacity no-underline">
              Masuk
            </Link>
            <Link :href="route('register')" class="bg-primary text-on-primary px-6 py-2 rounded-lg font-semibold scale-95 active:scale-90 transition-transform no-underline">
              Daftar Akun
            </Link>
          </template>
        </div>
      </nav>

      <!-- Mobile menu -->
      <DisclosurePanel class="sm:hidden bg-surface border-t border-outline-variant shadow-lg absolute w-full">
        <div class="space-y-1 px-4 py-4">
          <DisclosureButton v-for="item in navigation" :key="item.name"
            as="a" :href="item.href"
            :class="[
              isActivePage(item.key) ? 'bg-primary-container text-on-primary-container font-medium' : 'text-on-surface hover:bg-surface-container',
              'block rounded-lg px-3 py-3 text-base no-underline transition-colors'
            ]">
            {{ item.name }}
          </DisclosureButton>
        </div>
        <div v-if="!$page.props.auth?.user" class="px-4 py-4 border-t border-outline-variant flex gap-3">
          <Link :href="route('login')" class="flex-1 text-center py-2.5 rounded-lg font-medium text-primary border border-outline-variant no-underline">
            Masuk
          </Link>
          <Link :href="route('register')" class="flex-1 text-center py-2.5 rounded-lg font-semibold text-on-primary bg-primary no-underline">
            Daftar
          </Link>
        </div>
      </DisclosurePanel>
    </Disclosure>

    <!-- Page Content -->
    <main class="flex-1 flex flex-col">
      <slot />
    </main>

    <!-- ── FOOTER ── -->
    <footer class="bg-surface-container-highest py-section-gap mt-auto">
      <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop grid grid-cols-1 md:grid-cols-4 gap-gutter">
        <div class="col-span-1 md:col-span-1">
          <h3 class="font-headline-md text-headline-md font-bold text-primary mb-6 flex items-center gap-3">
            <img src="/images/logo-sman4-jember.png" alt="Logo" class="h-10 w-auto" />
            <span class="text-xl">Perpustakaan<br>Digital</span>
          </h3>
          <p class="text-on-surface-variant">Membangun generasi cerdas melalui literasi digital yang inklusif dan berkualitas di lingkungan SMA Negeri 4 Jember.</p>
        </div>
        <div>
          <h4 class="font-title-lg text-title-lg text-primary mb-6">Alamat Sekolah</h4>
          <p class="text-on-surface-variant leading-relaxed">
            Jl. Hayam Wuruk No. 145<br/>
            Sempusari, Kaliwates, Jember<br/>
            Jawa Timur, 68131
          </p>
        </div>
        <div>
          <h4 class="font-title-lg text-title-lg text-primary mb-6">Tautan Cepat</h4>
          <ul class="space-y-3 p-0 m-0 list-none">
            <li><Link :href="route('catalog')" class="text-on-surface-variant hover:text-primary transition-colors no-underline">Katalog Buku</Link></li>
            <li><Link :href="route('register')" class="text-on-surface-variant hover:text-primary transition-colors no-underline">Daftar Anggota</Link></li>
            <li><Link :href="route('login')" class="text-on-surface-variant hover:text-primary transition-colors no-underline">Syarat & Ketentuan</Link></li>
          </ul>
        </div>
        <div>
          <h4 class="font-title-lg text-title-lg text-primary mb-6">Kontak</h4>
          <div class="flex gap-4">
            <a href="#" class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all no-underline">
              <span class="material-symbols-outlined text-[20px]">public</span>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all no-underline">
              <span class="material-symbols-outlined text-[20px]">alternate_email</span>
            </a>
          </div>
          <div class="mt-8">
            <p class="text-on-surface-variant font-bold mb-1">Email:</p>
            <p class="text-on-surface-variant">info@sman4jember.sch.id</p>
          </div>
        </div>
      </div>
      <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop mt-16 pt-8 border-t border-outline-variant text-center">
        <p class="text-on-surface-variant text-sm">© {{ new Date().getFullYear() }} SMA Negeri 4 Jember. All rights reserved.</p>
      </div>
    </footer>
  </div>

  <ToastNotification />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Disclosure, DisclosureButton, DisclosurePanel,
  Menu, MenuButton, MenuItem, MenuItems,
} from '@headlessui/vue'
import {
  Bars3Icon, XMarkIcon, ChevronDownIcon,
} from '@heroicons/vue/24/outline'
import ToastNotification from '@/Components/ToastNotification.vue'
import { useNotificationStore } from '@/stores/notification'

const page = usePage()
const isLoggedIn = computed(() => !!page.props.auth?.user)
const notificationStore = useNotificationStore()

watch(() => page.props.flash, (flash) => {
  if (flash?.success) notificationStore.success(flash.success)
  else if (flash?.error) notificationStore.error(flash.error)
  else if (flash?.warning) notificationStore.warning(flash.warning)
  else if (flash?.info) notificationStore.info(flash.info)
}, { deep: true, immediate: true })

const navigation = computed(() => {
  const base = [
    { name: 'Beranda', href: route('home'),    key: 'home'    },
    { name: 'Katalog', href: route('catalog'), key: 'catalog' },
  ]
  if (isLoggedIn.value) {
    if (['admin', 'petugas'].includes(page.props.auth.user.role)) {
      base.push({ name: 'Dashboard', href: route('dashboard'), key: 'dashboard' })
    } else {
      try {
        base.push({ name: 'Profil', href: route('anggota.profile'), key: 'anggota' })
      } catch (_) {
        base.push({ name: 'Profil', href: '/anggota/profile', key: 'anggota' })
      }
    }
  }
  return base
})

function isActivePage(key) {
  return route().current()?.startsWith(key)
}

onMounted(() => {
  document.documentElement.classList.remove('dark')
  // Micro-interaction for header
  window.addEventListener('scroll', () => {
      const header = document.querySelector('header');
      if (header) {
        if (window.scrollY > 20) {
            header.classList.add('shadow-md', 'bg-surface/90');
            header.classList.remove('shadow-sm', 'bg-surface/70');
        } else {
            header.classList.remove('shadow-md', 'bg-surface/90');
            header.classList.add('shadow-sm', 'bg-surface/70');
        }
      }
  });
})
</script>

<style>
/* Global utilities untuk Layout Publik (mengadopsi dari desain AI) */
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    vertical-align: middle;
}
.book-shadow {
    box-shadow: 0px 4px 20px rgba(6, 78, 59, 0.05);
    transition: all 0.3s ease;
}
.book-shadow:hover {
    box-shadow: 0px 20px 40px rgba(6, 78, 59, 0.12);
    transform: translateY(-8px);
}
</style>
