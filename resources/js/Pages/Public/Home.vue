<template>
  <PublicLayout>
    <!-- Hero Section -->
    <section class="pt-32 pb-section-gap px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div class="space-y-stack-lg">
          <h1 class="font-display-lg-mobile md:font-display-lg text-[40px] md:text-[64px] text-primary leading-tight font-bold">
            Jelajahi Dunia Pengetahuan dalam Setiap Halaman
          </h1>
          <p class="font-body-lg text-[18px] text-on-surface-variant max-w-xl">
            Akses ribuan koleksi buku digital, jurnal penelitian, dan karya sastra klasik langsung dari genggaman Anda. Membangun masa depan literasi SMA Negeri 4 Jember yang lebih cerdas.
          </p>
          <div class="flex flex-wrap gap-4 pt-4">
            <Link v-if="!$page.props.auth.user" :href="route('register')" class="bg-primary text-on-primary px-8 py-4 rounded-lg font-title-lg text-[18px] shadow-lg hover:brightness-110 transition-all no-underline">
              Mulai Membaca
            </Link>
            <Link v-else :href="route('catalog')" class="bg-primary text-on-primary px-8 py-4 rounded-lg font-title-lg text-[18px] shadow-lg hover:brightness-110 transition-all no-underline">
              Lanjut Membaca
            </Link>
            <Link :href="route('catalog')" class="border-2 border-primary text-primary px-8 py-4 rounded-lg font-title-lg text-[18px] hover:bg-primary/5 transition-all no-underline">
              Jelajahi Katalog
            </Link>
          </div>
        </div>
        <div class="relative flex justify-center lg:justify-end">
          <div class="relative w-full max-w-[500px] aspect-square">
            <img alt="3D Book Concept" class="rounded-2xl shadow-2xl object-cover w-full h-full transform rotate-3" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAc5SHiA7xIWr8yHPwEfi5I80Iglhr5mmdw-NWmAir20yawr2jgs76Nk9i6lbNRC9OjS9ORCPQnMgwi8Kj1xyQFuBiEUYv-Axrv7Hdi5M5aIfFgfg3N-O6xqZO6pQ499EwdP5VgJSvZMaKeHu7avc6Y0DjrAoOT4HzUpFSjHHHHV2kDLudzFgJzL0lGI24waDqEANf-1ULhU84f5rmzj8SVu9WvXJI9oBjspOxBq-RmIzfl06lC5W5BbHKPmVe1hQglOcPpAUJSHdzG"/>
          </div>
        </div>
      </div>
      <div class="mt-20 grid grid-cols-2 md:grid-cols-3 gap-8 border-t border-secondary-container pt-12">
        <div class="text-center md:text-left">
          <h3 class="font-display-lg text-[48px] text-primary font-bold">{{ formatNum(stats.total_books) }}+</h3>
          <p class="text-on-surface-variant font-label-md text-[14px] font-semibold uppercase tracking-wider">Koleksi Buku</p>
        </div>
        <div class="text-center md:text-left">
          <h3 class="font-display-lg text-[48px] text-primary font-bold">{{ formatNum(stats.total_members) }}</h3>
          <p class="text-on-surface-variant font-label-md text-[14px] font-semibold uppercase tracking-wider">Anggota Aktif</p>
        </div>
        <div class="text-center md:text-left col-span-2 md:col-span-1">
          <h3 class="font-display-lg text-[48px] text-primary font-bold">{{ stats.total_categories }}</h3>
          <p class="text-on-surface-variant font-label-md text-[14px] font-semibold uppercase tracking-wider">Kategori Ilmu</p>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="bg-secondary-container/30 py-section-gap">
      <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="text-center mb-16">
          <h2 class="font-headline-lg text-[48px] text-primary mb-4 font-bold">Kenapa Memilih Perpustakaan Digital Kami?</h2>
          <p class="text-on-surface-variant font-body-lg text-[18px] max-w-2xl mx-auto">Dirancang untuk kenyamanan dan kecepatan akses dalam menunjang kegiatan belajar mengajar.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
          <div class="bg-surface p-8 rounded-xl book-shadow border border-white/20">
            <div class="w-12 h-12 bg-primary-fixed rounded-lg flex items-center justify-center mb-6">
              <span class="material-symbols-outlined text-primary text-[24px]">search</span>
            </div>
            <h4 class="font-title-lg text-[22px] text-primary mb-3 font-semibold">Pencarian Cepat</h4>
            <p class="text-on-surface-variant text-[16px]">Temukan buku berdasarkan judul, penulis, atau kata kunci dalam hitungan detik.</p>
          </div>
          <div class="bg-surface p-8 rounded-xl book-shadow border border-white/20">
            <div class="w-12 h-12 bg-primary-fixed rounded-lg flex items-center justify-center mb-6">
              <span class="material-symbols-outlined text-primary text-[24px]">qr_code</span>
            </div>
            <h4 class="font-title-lg text-[22px] text-primary mb-3 font-semibold">QR Code Member</h4>
            <p class="text-on-surface-variant text-[16px]">Identitas digital untuk peminjaman fisik maupun digital di lingkungan sekolah.</p>
          </div>
          <div class="bg-surface p-8 rounded-xl book-shadow border border-white/20">
            <div class="w-12 h-12 bg-primary-fixed rounded-lg flex items-center justify-center mb-6">
              <span class="material-symbols-outlined text-primary text-[24px]">history</span>
            </div>
            <h4 class="font-title-lg text-[22px] text-primary mb-3 font-semibold">Riwayat Pinjam</h4>
            <p class="text-on-surface-variant text-[16px]">Pantau daftar buku yang sedang dibaca dan riwayat literasi Anda dengan rapi.</p>
          </div>
          <div class="bg-surface p-8 rounded-xl book-shadow border border-white/20">
            <div class="w-12 h-12 bg-primary-fixed rounded-lg flex items-center justify-center mb-6">
              <span class="material-symbols-outlined text-primary text-[24px]">notifications_active</span>
            </div>
            <h4 class="font-title-lg text-[22px] text-primary mb-3 font-semibold">Notifikasi Otomatis</h4>
            <p class="text-on-surface-variant text-[16px]">Pengingat otomatis untuk pengembalian buku dan update koleksi terbaru.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Kategori Populer Section -->
    <section class="py-section-gap px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto">
      <div class="flex justify-between items-end mb-12 flex-wrap gap-4">
        <div>
          <h2 class="font-headline-lg text-[48px] text-primary font-bold">Kategori Populer</h2>
          <p class="text-on-surface-variant mt-2 text-[18px]">Pilih bidang ilmu yang ingin Anda eksplorasi hari ini.</p>
        </div>
        <Link :href="route('catalog')" class="text-primary font-bold flex items-center gap-2 group no-underline text-[16px]">
          Lihat Semua <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
        </Link>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <Link v-for="(cat, i) in categories.slice(0, 4)" :key="cat.id" :href="route('catalog', { category: cat.id })"
          class="group cursor-pointer relative overflow-hidden rounded-xl p-10 flex flex-col items-center text-center transition-all hover:scale-[1.02] no-underline"
          :class="i === 0 ? 'bg-primary-container text-white border-none' : 'bg-surface-container-high text-primary border border-outline-variant'">
          
          <span class="material-symbols-outlined text-[48px] mb-4" :class="i === 0 ? 'text-primary-fixed' : 'text-primary'">
            {{ getCatMaterialIcon(cat.name) }}
          </span>
          <h4 class="font-title-lg text-[22px] font-semibold" :class="i === 0 ? 'text-white' : 'text-primary'">{{ cat.name }}</h4>
          <p class="text-sm mt-2" :class="i === 0 ? 'text-primary-fixed-dim' : 'text-on-surface-variant'">{{ cat.books_count }} Koleksi</p>
        </Link>
      </div>
    </section>

    <!-- Buku Populer Section -->
    <section v-if="popularBooks && popularBooks.length > 0" class="py-section-gap bg-surface-container-low">
      <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <h2 class="font-headline-lg text-[48px] text-primary mb-12 text-center font-bold">Buku Terpopuler Minggu Ini</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-gutter">
          <div v-for="book in popularBooks.slice(0, 5)" :key="book.id" class="flex flex-col">
            <Link :href="route('catalog') + '?book=' + book.id" class="aspect-[3/4] relative rounded-lg overflow-hidden book-shadow mb-4 group block">
              <img :src="book.cover_image ? '/storage/' + book.cover_image : '/images/placeholder-cover.jpg'" 
                   :alt="book.title" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-primary/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                <span class="bg-on-primary text-primary px-4 py-2 rounded-lg font-label-md font-semibold text-[14px]">Detail</span>
              </div>
            </Link>
            <h5 class="font-bold text-[16px] text-primary truncate" :title="book.title">{{ book.title }}</h5>
            <p class="text-sm text-on-surface-variant mb-3 truncate" :title="book.author">{{ book.author }}</p>
            <Link :href="route('catalog') + '?book=' + book.id" class="text-primary font-bold text-sm hover:underline mt-auto">Pinjam Sekarang</Link>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Banner -->
    <section class="px-margin-mobile md:px-margin-desktop py-section-gap max-w-container-max mx-auto">
      <div class="relative overflow-hidden bg-primary-container rounded-3xl p-12 md:p-20 text-center">
        <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-primary rounded-full opacity-20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-96 h-96 bg-tertiary rounded-full opacity-10 blur-3xl"></div>
        
        <h2 class="relative z-10 font-headline-lg text-[48px] text-on-primary mb-6 font-bold">Siap Untuk Mulai Membaca?</h2>
        <p class="relative z-10 text-on-primary-container font-body-lg text-[18px] max-w-2xl mx-auto mb-10">
          Gabung dengan ribuan siswa dan guru lainnya. Dapatkan akses penuh ke seluruh koleksi digital kami secara gratis sebagai bagian dari civitas akademika.
        </p>
        <Link v-if="!$page.props.auth.user" :href="route('register')" class="relative z-10 inline-block bg-white text-primary px-10 py-4 rounded-lg font-title-lg text-[18px] font-semibold shadow-xl hover:scale-105 active:scale-95 transition-all no-underline">
          Buat Akun Gratis
        </Link>
        <Link v-else :href="route('catalog')" class="relative z-10 inline-block bg-white text-primary px-10 py-4 rounded-lg font-title-lg text-[18px] font-semibold shadow-xl hover:scale-105 active:scale-95 transition-all no-underline">
          Jelajahi Katalog
        </Link>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
  stats: Object,
  popularBooks: Array,
  categories: Array,
  settings: Object,
})

function formatNum(n) {
  if (!n) return '0'
  if (n >= 1000) return (n / 1000).toFixed(1).replace('.0', '') + 'rb'
  return String(n)
}

const matIcons = {
  'Sains': 'biotech', 'Teknologi': 'devices', 'Sejarah': 'history_edu',
  'Bahasa': 'translate', 'Sastra': 'auto_stories', 'Seni': 'palette', 
  'Olahraga': 'sports_soccer', 'Agama': 'mosque', 'Ekonomi': 'payments', 
  'Biologi': 'science', 'Sosial': 'groups', 'Fiksi': 'auto_awesome', 
}

function getCatMaterialIcon(name) {
  for (const [key, val] of Object.entries(matIcons)) {
    if (name?.toLowerCase().includes(key.toLowerCase())) return val
  }
  return 'library_books'
}
</script>
