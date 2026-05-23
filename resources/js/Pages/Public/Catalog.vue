<template>
  <PublicLayout>
    <!-- Header Area -->
    <section class="emerald-gradient-bg py-stack-lg pt-32">
      <div class="max-w-container-max mx-auto px-margin-desktop text-center">
        <h1 class="font-headline-lg text-[48px] text-primary mb-2">Katalog Koleksi Buku</h1>
        <p class="font-body-lg text-[18px] text-on-surface-variant max-w-2xl mx-auto">
          Eksplorasi ribuan judul buku dari berbagai kategori ilmu untuk mendukung visi akademis masa depan.
        </p>
      </div>
    </section>

    <!-- Search & Filter Bar (Sticky) -->
    <div class="sticky top-[72px] z-40 py-stack-md bg-background/80 backdrop-blur-md border-b border-surface-variant">
      <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="glass-morphism rounded-full p-2 shadow-sm flex flex-col md:flex-row items-center gap-4">
          <form @submit.prevent="applyFilters" class="relative flex-1 group w-full m-0">
            <span class="material-symbols-outlined absolute left-6 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">search</span>
            <input v-model="localFilters.search" type="text"
              class="w-full bg-secondary-container/30 border-none rounded-full py-4 pl-14 pr-6 focus:ring-2 focus:ring-primary/20 font-body-md transition-all outline-none" 
              placeholder="Cari judul, penulis, atau ISBN..." />
            <!-- hidden submit button just in case -->
            <button type="submit" class="hidden"></button>
          </form>
          <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 px-2 no-scrollbar">
            <button @click="clearFilter('category')" 
              :class="!localFilters.category ? 'bg-primary text-on-primary shadow-md scale-95' : 'bg-secondary-container text-on-secondary-container hover:bg-secondary-fixed'"
              class="whitespace-nowrap px-6 py-2.5 rounded-full font-label-md transition-all border-none cursor-pointer">Semua Kategori</button>
            <button @click="setAvailability('')" 
              :class="!localFilters.availability ? 'bg-primary text-on-primary shadow-md scale-95' : 'bg-secondary-container text-on-secondary-container hover:bg-secondary-fixed'"
              class="whitespace-nowrap px-6 py-2.5 rounded-full font-label-md transition-all border-none cursor-pointer">Semua Ketersediaan</button>
            <button @click="setAvailability('tersedia')" 
              :class="localFilters.availability === 'tersedia' ? 'bg-primary text-on-primary shadow-md scale-95' : 'bg-secondary-container text-on-secondary-container hover:bg-secondary-fixed'"
              class="whitespace-nowrap px-6 py-2.5 rounded-full font-label-md transition-all border-none cursor-pointer">Tersedia</button>
            <button @click="setAvailability('dipinjam')" 
              :class="localFilters.availability === 'dipinjam' ? 'bg-primary text-on-primary shadow-md scale-95' : 'bg-secondary-container text-on-secondary-container hover:bg-secondary-fixed'"
              class="whitespace-nowrap px-6 py-2.5 rounded-full font-label-md transition-all border-none cursor-pointer">Dipinjam</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Layout -->
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-stack-lg grid grid-cols-1 md:grid-cols-12 gap-gutter">
      
      <!-- Left Sidebar (Categories) -->
      <aside class="md:col-span-3 space-y-stack-md">
        <h3 class="font-title-lg text-[22px] text-primary mb-4 px-2">Kategori Buku</h3>
        <nav class="space-y-1">
          <button v-for="cat in categories" :key="cat.id" @click="setCategory(cat.id)"
            :class="String(localFilters.category) === String(cat.id) ? 'bg-primary-container text-on-primary-container' : 'hover:bg-secondary-container/50 text-on-surface-variant'"
            class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all group border-none cursor-pointer">
            <div class="flex items-center gap-3">
              <span class="material-symbols-outlined" :style="String(localFilters.category) === String(cat.id) ? 'font-variation-settings:\'FILL\' 1' : ''">{{ getCatMaterialIcon(cat.name) }}</span>
              <span class="font-body-md" :class="String(localFilters.category) === String(cat.id) ? 'font-semibold' : ''">{{ cat.name }}</span>
            </div>
            <span class="text-label-md px-2 py-0.5 rounded-full"
              :class="String(localFilters.category) === String(cat.id) ? 'opacity-80' : 'bg-secondary-container'">
              {{ cat.books_count }}
            </span>
          </button>
        </nav>
        
        <!-- Helper Card -->
        <div class="mt-stack-lg p-6 rounded-2xl bg-primary/5 border border-primary/10">
          <p class="font-label-md text-[14px] text-primary uppercase tracking-widest mb-2">Bantuan</p>
          <p class="font-body-md text-[16px] text-on-surface-variant mb-4">Bingung mencari buku? Gunakan filter pencarian yang tersedia.</p>
          <button @click="clearAll" v-if="hasActiveFilter" class="w-full py-2.5 rounded-lg border border-primary text-primary font-label-md text-[14px] hover:bg-primary hover:text-white transition-all bg-transparent cursor-pointer">Reset Semua Filter</button>
        </div>
      </aside>

      <!-- Right Grid (Books) -->
      <section class="md:col-span-9">
        <div class="mb-4 text-on-surface-variant font-body-md text-[14px]">
          Menampilkan {{ books.from || 0 }}–{{ books.to || 0 }} dari {{ books.total.toLocaleString('id-ID') }} buku
        </div>
        
        <div v-if="books.data.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-gutter mb-stack-lg">
          
          <div v-for="book in books.data" :key="book.id" class="group flex flex-col cursor-pointer" @click="openModal(book)">
            <div class="relative aspect-[3/4] rounded-xl overflow-hidden book-card-shadow mb-4">
              <img v-if="book.cover_image" 
                :src="book.cover_image.startsWith('http') ? book.cover_image : '/' + book.cover_image"
                :alt="book.title" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center bg-secondary-container">
                <span class="material-symbols-outlined text-[40px] text-outline opacity-50">book</span>
              </div>
              
              <!-- Hover Overlay -->
              <div class="absolute inset-0 bg-primary/80 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center gap-4 px-4 backdrop-blur-sm">
                <button class="w-full py-2 border border-on-primary text-on-primary font-label-md rounded-lg hover:bg-white/10 transition-transform scale-90 group-hover:scale-100 bg-transparent cursor-pointer">Detail</button>
              </div>
              
              <!-- Category badge on cover -->
              <span class="absolute top-2 left-2 bg-white/90 text-primary font-label-md text-[10px] px-2 py-1 rounded-md shadow-sm">{{ book.category }}</span>
            </div>
            
            <h4 class="font-title-lg text-[16px] text-on-surface line-clamp-2 mb-1 group-hover:text-primary transition-colors" :title="book.title">{{ book.title }}</h4>
            <p class="font-body-md text-[14px] text-outline mb-2 line-clamp-1" :title="book.author">{{ book.author }}</p>
            
            <div class="mt-auto flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="book.available_count > 0 ? 'bg-tertiary-fixed-dim' : 'bg-error'"></span>
                <span class="text-[12px] font-semibold" :class="book.available_count > 0 ? 'text-on-tertiary-fixed-variant' : 'text-error'">
                  {{ book.available_count > 0 ? 'Tersedia ' + book.available_count : 'Dipinjam' }}
                </span>
              </div>
              <span class="text-[10px] font-bold text-outline bg-secondary-container px-2 py-0.5 rounded-sm">{{ book.rack_number }}</span>
            </div>
          </div>

        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-20 text-center">
          <span class="material-symbols-outlined text-[80px] text-secondary-container mb-6">search_off</span>
          <h3 class="font-headline-md text-[32px] text-primary mb-2">Buku tidak ditemukan</h3>
          <p class="font-body-md text-on-surface-variant">Coba gunakan kata kunci lain atau periksa filter Anda.</p>
          <button @click="clearAll" class="mt-4 px-6 py-2 bg-primary text-on-primary rounded-lg font-label-md border-none cursor-pointer shadow-sm hover:opacity-90">Hapus Semua Filter</button>
        </div>

        <!-- Pagination -->
        <div v-if="books.last_page > 1" class="flex items-center justify-center gap-2 py-10 border-t border-surface-variant">
          <component :is="books.prev_page_url ? 'Link' : 'button'" :href="books.prev_page_url" 
            :disabled="!books.prev_page_url"
            class="w-10 h-10 rounded-full flex items-center justify-center border border-outline-variant hover:border-primary text-outline hover:text-primary transition-all disabled:opacity-50 no-underline cursor-pointer bg-transparent">
            <span class="material-symbols-outlined">chevron_left</span>
          </component>
          
          <div class="hidden sm:flex items-center gap-2">
            <template v-if="books.links">
              <template v-for="(link, i) in books.links" :key="i">
                <Link v-if="!link.label.includes('Previous') && !link.label.includes('Next')"
                  :href="link.url || '#'"
                  :class="link.active ? 'bg-primary text-on-primary shadow-md border-none' : 'border border-transparent hover:border-primary text-outline hover:text-primary bg-transparent'"
                  class="w-10 h-10 rounded-full flex items-center justify-center font-label-md transition-all no-underline"
                  v-html="link.label"></Link>
              </template>
            </template>
            <template v-else>
              <span class="text-outline font-label-md">Halaman {{ books.current_page }} dari {{ books.last_page }}</span>
            </template>
          </div>
          <div class="sm:hidden text-outline font-label-md">Hal {{ books.current_page }} / {{ books.last_page }}</div>

          <component :is="books.next_page_url ? 'Link' : 'button'" :href="books.next_page_url"
            :disabled="!books.next_page_url"
            class="w-10 h-10 rounded-full flex items-center justify-center border border-outline-variant hover:border-primary text-outline hover:text-primary transition-all disabled:opacity-50 no-underline cursor-pointer bg-transparent">
            <span class="material-symbols-outlined">chevron_right</span>
          </component>
        </div>
      </section>
    </div>

    <!-- ─── Book Detail Modal ────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="selectedBook" class="fixed inset-0 bg-surface/80 backdrop-blur-md flex items-center justify-center z-[9999] p-4" @click.self="closeModal">
          <div class="bg-surface border border-outline-variant rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl relative flex flex-col md:flex-row">
            
            <button class="absolute top-4 right-4 text-outline hover:text-primary bg-secondary-container p-2 rounded-full transition-colors z-10 border-none cursor-pointer flex" @click="closeModal">
              <span class="material-symbols-outlined text-[20px]">close</span>
            </button>

            <!-- Left: Cover -->
            <div class="md:w-1/3 bg-surface-container-low p-8 flex flex-col items-center border-r border-outline-variant">
              <div class="w-full aspect-[3/4] rounded-xl overflow-hidden book-card-shadow mb-6 relative">
                <img v-if="selectedBook.cover_image"
                  :src="selectedBook.cover_image.startsWith('http') ? selectedBook.cover_image : '/' + selectedBook.cover_image"
                  :alt="selectedBook.title" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center bg-secondary-container">
                  <span class="material-symbols-outlined text-[60px] text-outline opacity-50">book</span>
                </div>
              </div>
              
              <div class="w-full rounded-xl p-4 flex flex-col items-center text-center" :class="selectedBook.available_count > 0 ? 'bg-primary-container text-on-primary-container' : 'bg-error-container text-on-error-container'">
                <div class="flex items-center gap-2 font-bold mb-1">
                  <span class="material-symbols-outlined text-[20px]">{{ selectedBook.available_count > 0 ? 'check_circle' : 'cancel' }}</span>
                  {{ selectedBook.available_count > 0 ? 'Tersedia' : 'Sedang Dipinjam' }}
                </div>
                <div v-if="selectedBook.available_count > 0" class="text-sm opacity-90">
                  {{ selectedBook.available_count }} eksemplar tersedia
                </div>
              </div>

            </div>

            <!-- Right: Detail -->
            <div class="md:w-2/3 p-8 flex flex-col">
              <span class="bg-secondary-container text-primary font-label-md px-3 py-1 rounded-full text-[11px] self-start mb-4 uppercase tracking-wider">{{ selectedBook.category }}</span>
              
              <h2 class="font-headline-md text-[28px] md:text-[32px] text-primary mb-2 leading-tight">{{ selectedBook.title }}</h2>
              <p class="font-body-lg text-[16px] md:text-[18px] text-outline mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">person</span>
                {{ selectedBook.author }}
              </p>

              <div class="grid grid-cols-2 gap-y-4 gap-x-6 mb-8 text-[14px]">
                <div v-if="selectedBook.publisher">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">Penerbit</span>
                  <span class="text-on-surface font-semibold">{{ selectedBook.publisher }}</span>
                </div>
                <div v-if="selectedBook.year">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">Tahun Terbit</span>
                  <span class="text-on-surface font-semibold">{{ selectedBook.year }}</span>
                </div>
                <div v-if="selectedBook.isbn">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">ISBN</span>
                  <span class="text-on-surface font-semibold">{{ selectedBook.isbn }}</span>
                </div>
                <div v-if="selectedBook.pages">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">Jumlah Halaman</span>
                  <span class="text-on-surface font-semibold">{{ selectedBook.pages }} halaman</span>
                </div>
                <div v-if="selectedBook.language">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">Bahasa</span>
                  <span class="text-on-surface font-semibold">{{ selectedBook.language }}</span>
                </div>
                <div v-if="selectedBook.rack_number">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">Nomor Rak</span>
                  <span class="text-on-surface font-semibold bg-secondary-container px-2 py-0.5 rounded">{{ selectedBook.rack_number }}</span>
                </div>
                <div v-if="selectedBook.total_loans">
                  <span class="block text-outline font-label-md mb-1 text-[12px]">Total Dipinjam</span>
                  <span class="text-on-surface font-semibold">{{ selectedBook.total_loans }} kali</span>
                </div>
              </div>

              <div class="border-t border-outline-variant pt-6 mt-auto">
                <h3 class="font-title-lg text-[20px] text-primary mb-3">Sinopsis</h3>
                <p v-if="selectedBook.description" class="text-on-surface-variant text-[15px] leading-relaxed">
                  {{ selectedBook.description }}
                </p>
                <p v-else class="text-outline italic text-[15px]">
                  Sinopsis tidak tersedia untuk buku ini. Namun, dipastikan buku ini sangat menarik untuk dibaca dan menambah wawasan Anda di perpustakaan kami.
                </p>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </PublicLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
  books:      Object,
  categories: Array,
  filters:    Object,
})

const localFilters = reactive({
  search:       props.filters?.search       || '',
  category:     props.filters?.category     ? String(props.filters.category) : '',
  availability: props.filters?.availability || '',
})

const hasActiveFilter = computed(() =>
  localFilters.search || localFilters.category || localFilters.availability
)

function applyFilters() {
  const params = {}
  if (localFilters.search)       params.search = localFilters.search
  if (localFilters.category)     params.category = localFilters.category
  if (localFilters.availability) params.availability = localFilters.availability
  router.get(route('catalog'), params, { preserveState: true, replace: true })
}

function clearFilter(key) {
  localFilters[key] = ''
  applyFilters()
}

function setAvailability(val) {
  localFilters.availability = val
  applyFilters()
}

function setCategory(id) {
  localFilters.category = String(id)
  applyFilters()
}

function clearAll() {
  localFilters.search = ''
  localFilters.category = ''
  localFilters.availability = ''
  applyFilters()
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

// ── Modal ────────────────────────────────────────────────────
const selectedBook = ref(null)

function openModal(book) {
  selectedBook.value = book
  document.body.style.overflow = 'hidden'
}

function closeModal() {
  selectedBook.value = null
  document.body.style.overflow = ''
}
</script>

<style scoped>
.glass-morphism {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.book-card-shadow {
  box-shadow: 0px 4px 20px rgba(6, 78, 59, 0.05);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.book-card-shadow:hover {
  box-shadow: 0px 20px 40px rgba(6, 78, 59, 0.12);
  transform: translateY(-8px);
}
.emerald-gradient-bg {
  background: radial-gradient(circle at top right, #064e3b15, transparent),
              radial-gradient(circle at bottom left, #ecfdf5, transparent);
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Modal transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-active .bg-surface,
.modal-leave-active .bg-surface {
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .bg-surface,
.modal-leave-to .bg-surface {
  transform: scale(0.95) translateY(20px);
}
</style>
