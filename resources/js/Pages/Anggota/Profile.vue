<template>
  <PublicLayout>
    <main class="pt-24 pb-section-gap">
      <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        
        <!-- Flash success -->
        <div v-if="$page.props.flash?.success" class="mb-6 flex items-center gap-2 px-4 py-3 rounded-xl bg-[#f0fdf4] text-[#15803d] border border-[#bbf7d0] font-body-md shadow-sm">
          <span class="material-symbols-outlined text-[20px]">check_circle</span>
          {{ $page.props.flash.success }}
        </div>

        <!-- Hero Header Card -->
        <section class="emerald-gradient rounded-3xl p-8 md:p-12 mb-stack-lg relative overflow-hidden shadow-2xl">
          <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary-fixed/10 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-tertiary-fixed-dim/5 rounded-full blur-3xl"></div>
          
          <div class="relative flex flex-col md:flex-row items-center md:items-end justify-between gap-8">
            <div class="flex flex-col md:flex-row items-center md:items-center gap-6">
              <div class="relative">
                <img v-if="member?.photo" :src="`/storage/${member.photo}`" alt="Foto profil" class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-primary-fixed/30 avatar-glow object-cover bg-white" />
                <div v-else class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-primary-fixed/30 avatar-glow bg-gradient-to-br from-primary-container to-primary flex items-center justify-center text-white text-[48px] font-bold">
                  {{ user.name[0].toUpperCase() }}
                </div>
                
                <div v-if="member?.verified_at" class="absolute bottom-2 right-2 bg-tertiary-fixed text-primary w-8 h-8 rounded-full flex items-center justify-center shadow-lg">
                  <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">verified</span>
                </div>
              </div>
              <div class="text-center md:text-left">
                <h1 class="font-headline-lg text-[32px] md:text-[40px] text-white mb-2">{{ user.name }}</h1>
                <p class="font-body-lg text-primary-fixed/80 mb-4">{{ user.email }}</p>
                <div class="flex flex-wrap justify-center md:justify-start gap-2">
                  <span class="px-4 py-1 rounded-full bg-white/10 backdrop-blur-md text-white text-label-md border border-white/20 capitalize">{{ member?.type || 'Anggota' }}</span>
                  <span class="px-4 py-1 rounded-full text-label-md border backdrop-blur-md" :class="statusClass(member?.status)">{{ statusLabel(member?.status) }}</span>
                  <span v-if="member?.kelas" class="px-4 py-1 rounded-full bg-white/10 backdrop-blur-md text-white text-label-md border border-white/20">{{ member.kelas.name }}</span>
                </div>
              </div>
            </div>
            <div class="glass-card px-6 py-4 rounded-2xl text-center md:text-right">
              <p class="text-label-md text-white/60 uppercase tracking-widest mb-1">Kode Anggota</p>
              <p class="font-title-lg text-white font-bold tracking-wider">{{ member?.member_code || '—' }}</p>
            </div>
          </div>
        </section>

        <!-- Navigation Tabs -->
        <nav class="flex overflow-x-auto pb-4 gap-2 mb-stack-md no-scrollbar">
          <button @click="activeTab = 'info'" 
            class="flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap border-none cursor-pointer"
            :class="activeTab === 'info' ? 'bg-primary text-white shadow-lg active:scale-95' : 'bg-white text-on-surface-variant hover:bg-secondary-fixed active:scale-95 shadow-sm border border-outline-variant/30'">
            <span class="material-symbols-outlined" :style="activeTab === 'info' ? 'font-variation-settings:\'FILL\' 1;' : ''">person</span>
            <span class="font-label-md">Informasi Akun</span>
          </button>
          
          <button @click="activeTab = 'qr'" 
            class="flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap border-none cursor-pointer"
            :class="activeTab === 'qr' ? 'bg-primary text-white shadow-lg active:scale-95' : 'bg-white text-on-surface-variant hover:bg-secondary-fixed active:scale-95 shadow-sm border border-outline-variant/30'">
            <span class="material-symbols-outlined" :style="activeTab === 'qr' ? 'font-variation-settings:\'FILL\' 1;' : ''">qr_code</span>
            <span class="font-label-md">QR Saya</span>
          </button>
          
          <button @click="activeTab = 'edit'" 
            class="flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap border-none cursor-pointer"
            :class="activeTab === 'edit' ? 'bg-primary text-white shadow-lg active:scale-95' : 'bg-white text-on-surface-variant hover:bg-secondary-fixed active:scale-95 shadow-sm border border-outline-variant/30'">
            <span class="material-symbols-outlined" :style="activeTab === 'edit' ? 'font-variation-settings:\'FILL\' 1;' : ''">edit</span>
            <span class="font-label-md">Edit Profil</span>
          </button>
          
          <button @click="activeTab = 'loans'" 
            class="flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap border-none cursor-pointer"
            :class="activeTab === 'loans' ? 'bg-primary text-white shadow-lg active:scale-95' : 'bg-white text-on-surface-variant hover:bg-secondary-fixed active:scale-95 shadow-sm border border-outline-variant/30'">
            <span class="material-symbols-outlined" :style="activeTab === 'loans' ? 'font-variation-settings:\'FILL\' 1;' : ''">menu_book</span>
            <span class="font-label-md">Peminjaman Aktif</span>
            <span v-if="activeLoans.length" class="ml-1 px-2 py-0.5 rounded-full text-[11px]" :class="activeTab === 'loans' ? 'bg-white/30' : 'bg-primary/10 text-primary'">{{ activeLoans.length }}</span>
          </button>
        </nav>

        <!-- Account Details Card -->
        <div class="bg-white rounded-[2rem] p-6 md:p-12 shadow-[0px_4px_20px_rgba(6,78,59,0.05)] border border-outline-variant/30 relative">
          
          <!-- Tab: Info -->
          <div v-show="activeTab === 'info'" class="w-full">
            <div class="flex items-center justify-between mb-8">
              <h2 class="font-headline-md text-[24px] text-primary">Detail Informasi Akun</h2>
              
              <div class="hidden md:flex items-center gap-2 text-sm">
                <span class="text-on-surface-variant font-label-md">Kelengkapan Profil: </span>
                <div class="w-32 h-2.5 bg-surface-variant rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-500" :style="{ width: completeness + '%' }"
                    :class="completeness >= 80 ? 'bg-primary' : completeness >= 50 ? 'bg-amber-500' : 'bg-error'"></div>
                </div>
                <span class="font-bold text-primary">{{ completeness }}%</span>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 md:gap-x-16 gap-y-0">
              <!-- Left Column -->
              <div class="space-y-0">
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Nama Lengkap</span>
                  <span class="font-title-lg text-on-surface text-[15px] md:text-[18px]">{{ user.name }}</span>
                </div>
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Alamat Email</span>
                  <span class="font-title-lg text-on-surface text-[15px] md:text-[18px]">{{ user.email }}</span>
                </div>
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Tipe Keanggotaan</span>
                  <span class="font-title-lg text-on-surface text-[15px] md:text-[18px] capitalize">{{ member?.type || '—' }}</span>
                </div>
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant" v-if="member?.kelas">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Kelas</span>
                  <span class="font-title-lg text-on-surface text-[15px] md:text-[18px]">{{ member.kelas.name }}</span>
                </div>
              </div>
              
              <!-- Right Column -->
              <div class="space-y-0">
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Nomor Telepon</span>
                  <div class="flex items-center justify-between">
                    <span class="font-title-lg text-on-surface text-[15px] md:text-[18px]">{{ member?.phone || '—' }}</span>
                    <button v-if="!member?.phone" @click="activeTab = 'edit'" class="text-primary text-sm hover:underline border-none bg-transparent cursor-pointer">+ Isi Data</button>
                  </div>
                </div>
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">NIS / NIP</span>
                  <div class="flex items-center justify-between">
                    <span class="font-title-lg text-on-surface text-[15px] md:text-[18px]">{{ member?.nis_nip || '—' }}</span>
                    <button v-if="!member?.nis_nip" @click="activeTab = 'edit'" class="text-primary text-sm hover:underline border-none bg-transparent cursor-pointer">+ Isi Data</button>
                  </div>
                </div>
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Tanggal Bergabung</span>
                  <span class="font-title-lg text-on-surface text-[15px] md:text-[18px]">{{ formatDate(member?.created_at) }}</span>
                </div>
                <div class="group py-4 md:py-5 flex flex-col gap-1 border-b border-surface-variant">
                  <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Status Verifikasi</span>
                  <div class="flex items-center gap-2">
                    <span class="font-title-lg text-[15px] md:text-[18px]" :class="member?.verified_at ? 'text-primary' : 'text-on-surface-variant'">
                      {{ member?.verified_at ? 'Terverifikasi' : 'Belum Verifikasi' }}
                    </span>
                    <span v-if="member?.verified_at" class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="group py-4 md:py-5 flex flex-col gap-1 mt-2">
              <span class="text-label-md text-on-surface-variant uppercase tracking-wider text-[11px] md:text-[12px]">Alamat Lengkap</span>
              <div class="flex items-center justify-between">
                <span class="font-body-lg text-on-surface text-[15px] md:text-[18px]">{{ member?.address || '—' }}</span>
                <button v-if="!member?.address" @click="activeTab = 'edit'" class="text-primary text-sm hover:underline border-none bg-transparent cursor-pointer">+ Isi Data</button>
              </div>
            </div>
            
            <div v-if="completeness < 100" class="mt-6 md:hidden p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
              <p class="font-semibold mb-2">Profil belum lengkap!</p>
              <button @click="activeTab = 'edit'" class="text-amber-900 underline border-none bg-transparent cursor-pointer p-0">Lengkapi sekarang</button>
            </div>
            
            <div class="mt-12 flex flex-col sm:flex-row gap-4">
              <button @click="activeTab = 'qr'" class="bg-primary text-white px-8 py-3.5 rounded-lg font-title-lg text-[16px] flex items-center justify-center gap-2 hover:bg-primary-container transition-all shadow-lg active:scale-95 border-none cursor-pointer">
                <span class="material-symbols-outlined">qr_code_scanner</span>
                Lihat QR Code
              </button>
              <button @click="activeTab = 'loans'" class="border border-primary text-primary px-8 py-3.5 rounded-lg font-title-lg text-[16px] flex items-center justify-center gap-2 hover:bg-primary-fixed/20 transition-all active:scale-95 bg-transparent cursor-pointer">
                <span class="material-symbols-outlined">history</span>
                Riwayat Peminjaman
              </button>
            </div>
          </div>
          
          <!-- Tab: QR Saya -->
          <div v-show="activeTab === 'qr'" class="w-full flex flex-col items-center py-6">
            <h2 class="font-headline-md text-primary mb-8 text-[24px]">Kartu Anggota Digital</h2>
            
            <div v-if="member?.member_code" class="flex flex-col items-center gap-6 w-full max-w-md">
              <div class="bg-white border-2 border-outline-variant/50 rounded-2xl p-6 shadow-xl w-full flex justify-center">
                <canvas ref="qrCanvas" class="block rounded-lg max-w-full"></canvas>
              </div>
              <div class="text-center w-full">
                <div class="font-headline-md text-[20px] text-primary mb-1">{{ user.name }}</div>
                <div class="text-sm font-mono text-on-surface-variant bg-surface-variant px-4 py-1.5 rounded-full inline-block mb-6">{{ member.member_code }}</div>
                
                <div class="flex items-center justify-center gap-2 text-[13px] font-semibold text-[#065f46] bg-[#d1fae5] border border-[#a7f3d0] px-4 py-2 rounded-xl mb-4">
                  <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">verified</span>
                  QR siap digunakan — tunjukkan ke petugas
                </div>
                <p class="text-[14px] text-on-surface-variant">Gunakan *QR Code* ini pada *scanner* perpustakaan untuk melakukan peminjaman atau pengembalian buku.</p>
              </div>
            </div>
            <div v-else class="text-center py-10 opacity-60">
              <span class="material-symbols-outlined text-[64px] mb-4">qr_code_2</span>
              <p class="font-title-lg">QR Belum Tersedia</p>
              <p class="text-sm">Silakan hubungi administrator.</p>
            </div>
          </div>
          
          <!-- Tab: Edit Profil -->
          <div v-show="activeTab === 'edit'" class="w-full">
            <h2 class="font-headline-md text-[24px] text-primary mb-6">Edit Informasi Akun</h2>
            
            <form @submit.prevent="submitEdit" enctype="multipart/form-data" class="flex flex-col gap-6 w-full max-w-2xl">
              <!-- Photo -->
              <div class="flex flex-col gap-2">
                <label class="font-label-md text-on-surface-variant flex items-center gap-2">Foto Profil</label>
                <div class="flex items-center gap-6">
                  <div class="shrink-0 relative w-20 h-20 rounded-full border border-outline-variant overflow-hidden bg-surface-variant flex items-center justify-center">
                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                    <img v-else-if="member?.photo" :src="`/storage/${member.photo}`" class="w-full h-full object-cover bg-white" />
                    <span v-else class="text-2xl font-bold text-outline">{{ user.name[0] }}</span>
                  </div>
                  <div>
                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-outline-variant rounded-lg cursor-pointer hover:bg-surface-variant transition-colors text-sm font-semibold">
                      <span class="material-symbols-outlined text-[18px]">upload</span>
                      Pilih Foto
                      <input type="file" accept="image/*" @change="onPhotoChange" class="hidden" />
                    </label>
                    <p class="text-[12px] text-outline mt-2">Format: JPG/PNG, Maks. 2MB</p>
                    <p v-if="form.errors?.photo" class="text-[12px] text-error mt-1">{{ form.errors.photo }}</p>
                  </div>
                </div>
              </div>
              
              <hr class="border-t border-surface-variant my-2" />
              
              <!-- Email -->
              <div class="flex flex-col gap-2">
                <label class="font-label-md text-on-surface-variant flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                  Email Akun
                  <span class="text-[11px] font-normal opacity-70">*(Digunakan untuk login)*</span>
                </label>
                <input v-model="form.email" type="email" class="w-full border border-outline-variant rounded-xl px-4 py-3 text-[14px] bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
                <p v-if="form.errors?.email" class="text-[12px] text-error">{{ form.errors.email }}</p>
              </div>
              
              <!-- Phone & NIS/NIP grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                  <label class="font-label-md text-on-surface-variant">Nomor Handphone</label>
                  <input v-model="form.phone" type="tel" class="w-full border border-outline-variant rounded-xl px-4 py-3 text-[14px] bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Contoh: 08123456789" />
                  <p v-if="form.errors?.phone" class="text-[12px] text-error">{{ form.errors.phone }}</p>
                </div>
                <div class="flex flex-col gap-2">
                  <label class="font-label-md text-on-surface-variant">NIS / NIP</label>
                  <input v-model="form.nis_nip" type="text" class="w-full border border-outline-variant rounded-xl px-4 py-3 text-[14px] bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Nomor Induk" />
                  <p v-if="form.errors?.nis_nip" class="text-[12px] text-error">{{ form.errors.nis_nip }}</p>
                </div>
              </div>
              
              <!-- Address -->
              <div class="flex flex-col gap-2">
                <label class="font-label-md text-on-surface-variant">Alamat Lengkap</label>
                <textarea v-model="form.address" rows="3" class="w-full border border-outline-variant rounded-xl px-4 py-3 text-[14px] bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"></textarea>
                <p v-if="form.errors?.address" class="text-[12px] text-error">{{ form.errors.address }}</p>
              </div>
              
              <!-- Actions -->
              <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-4">
                <button type="button" @click="activeTab = 'info'" class="px-6 py-3 border border-outline-variant rounded-lg font-label-md bg-white hover:bg-surface-variant cursor-pointer transition-colors">Batal</button>
                <button type="submit" :disabled="form.processing" class="px-6 py-3 bg-primary text-white rounded-lg font-label-md flex justify-center items-center gap-2 hover:opacity-90 disabled:opacity-50 cursor-pointer shadow-md transition-all border-none">
                  <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
              </div>
            </form>
          </div>
          
          <!-- Tab: Peminjaman -->
          <div v-show="activeTab === 'loans'" class="w-full">
            <h2 class="font-headline-md text-[24px] text-primary mb-6">Peminjaman Buku Aktif</h2>
            
            <div v-if="activeLoans.length > 0" class="flex flex-col gap-4">
              <div v-for="loan in activeLoans" :key="loan.id" class="border border-outline-variant rounded-xl p-5 hover:border-primary transition-colors bg-surface-container-low shadow-sm">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-4 border-b border-outline-variant/30 pb-3">
                  <div class="font-mono text-sm bg-white border border-outline-variant px-3 py-1 rounded-md text-on-surface-variant font-semibold">
                    {{ loan.loan_code }}
                  </div>
                  <div class="flex items-center gap-2 text-sm font-semibold px-3 py-1 rounded-full border"
                    :class="isOverdue(loan) ? 'bg-error-container text-error border-error-container' : 'bg-primary-container/20 text-primary border-primary-container/30'">
                    <span class="material-symbols-outlined text-[16px]">{{ isOverdue(loan) ? 'warning' : 'event' }}</span>
                    {{ isOverdue(loan) ? 'Terlambat (' + formatDate(loan.due_date) + ')' : 'Kembali: ' + formatDate(loan.due_date) }}
                  </div>
                </div>
                
                <div class="flex flex-col gap-2">
                  <div v-for="item in loan.items" :key="item.id" class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-outline mt-0.5 text-[20px]">book</span>
                    <span class="font-title-lg text-[15px] text-on-surface">{{ item.book_copy?.book?.title || 'Buku Tidak Diketahui' }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-16 opacity-60 bg-surface-variant rounded-2xl">
              <span class="material-symbols-outlined text-[64px] mb-4 text-outline">history_edu</span>
              <p class="font-title-lg text-[18px] text-on-surface-variant">Tidak ada pinjaman aktif</p>
              <p class="text-sm mt-1 mb-6">Kamu belum meminjam buku atau sudah mengembalikan semuanya.</p>
              <Link :href="route('catalog')" class="inline-flex border border-primary text-primary px-6 py-2 rounded-lg font-label-md hover:bg-primary-fixed/20 no-underline transition-colors">Cari Buku</Link>
            </div>
          </div>

        </div>
        
        <!-- Bottom Section -->
        <div class="mt-8 flex flex-wrap justify-end items-center gap-4">
          <Link :href="route('logout')" method="post" as="button" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-error bg-white border border-error hover:bg-error-container transition-colors font-label-md cursor-pointer shadow-sm">
            <span class="material-symbols-outlined text-[18px]">logout</span>
            Keluar Akun
          </Link>
        </div>

      </div>
    </main>
  </PublicLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Link, usePage, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import QRCode from 'qrcode'

const props = defineProps({ member: Object })
const user  = usePage().props.auth.user

const activeTab    = ref('info')
const photoPreview = ref(null)
const qrCanvas     = ref(null)

watch(activeTab, async (tab) => {
  if (tab === 'qr' && props.member?.member_code) {
    await nextTick()
    if (qrCanvas.value) {
      await QRCode.toCanvas(qrCanvas.value, props.member.member_code, {
        width: 240,
        margin: 2,
        color: { dark: '#003527', light: '#ffffff' },
      })
    }
  }
})

const form = useForm({
  email:   user.email || '',
  phone:   props.member?.phone   || '',
  nis_nip: props.member?.nis_nip || '',
  address: props.member?.address || '',
  photo:   null,
  _method: 'PUT',
})

function onPhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.photo = file
  const reader = new FileReader()
  reader.onload = ev => { photoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

function submitEdit() {
  form.post(route('anggota.profile.update'), {
    forceFormData: true,
    onSuccess: () => {
      activeTab.value = 'info'
      photoPreview.value = null
    },
  })
}

const activeLoans = computed(() => props.member?.loans || [])

const completeness = computed(() => {
  const fields = [user.name, user.email, props.member?.phone, props.member?.nis_nip, props.member?.address, props.member?.photo]
  const filled  = fields.filter(Boolean).length
  return Math.round((filled / fields.length) * 100)
})

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

function isOverdue(loan) {
  return new Date(loan.due_date) < new Date()
}

function statusLabel(s) {
  return { aktif: 'Aktif', pending: 'Menunggu Verifikasi', suspended: 'Suspended', nonaktif: 'Nonaktif', ditolak: 'Ditolak' }[s] ?? s
}

function statusClass(s) {
  return { 
    aktif: 'bg-white/20 text-white border-white/30', 
    pending: 'bg-amber-500/20 text-amber-100 border-amber-300/30', 
    suspended: 'bg-red-500/20 text-red-100 border-red-300/30', 
    nonaktif: 'bg-gray-500/20 text-gray-100 border-gray-300/30', 
    ditolak: 'bg-red-500/20 text-red-100 border-red-300/30' 
  }[s] ?? 'bg-white/10 text-white border-white/20'
}
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.emerald-gradient {
  background: linear-gradient(135deg, #064e3b 0%, #003527 100%);
}
.avatar-glow {
  box-shadow: 0 0 25px rgba(149, 211, 186, 0.4);
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
