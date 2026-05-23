<template>
  <AdminLayout title="">

    <div class="max-w-6xl mx-auto pb-20">
      <!-- Header Section -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan Sistem</h1>
        <p class="text-gray-500 mt-1">Kelola konfigurasi perpustakaan, denda, dan aturan peminjaman</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <div v-for="group in sortedGroups" :key="group.key" class="card h-full flex flex-col">
          <div class="card-header border-b border-gray-100 flex items-center gap-3 py-4 px-5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0" v-html="group.icon"></div>
            <h2 class="text-lg font-bold text-gray-900">{{ group.label }}</h2>
          </div>
          
          <div class="card-body p-0 flex-1">
            <div class="divide-y divide-gray-100">
              
              <div v-for="setting in settings[group.key]" :key="setting.key" class="p-5 hover:bg-gray-50/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                
                <!-- Setting Label & Desc -->
                <div class="flex-1">
                  <label :for="setting.key" class="font-semibold text-gray-900 cursor-pointer block text-sm">{{ setting.description || setting.key }}</label>
                </div>

                <!-- Setting Input -->
                <div class="w-full sm:w-48 flex-shrink-0">
                  
                  <!-- Boolean Toggle -->
                  <label v-if="setting.type === 'boolean'" class="flex items-center gap-3 cursor-pointer justify-end">
                    <span class="text-sm font-medium" :class="form[setting.key] ? 'text-emerald-700' : 'text-gray-500'">
                      {{ form[setting.key] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <div class="relative">
                      <input :id="setting.key" type="checkbox" v-model="form[setting.key]" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </div>
                  </label>

                  <!-- Text / Number Input -->
                  <div v-else class="relative">
                    <!-- Prefix (Rp) -->
                    <div v-if="group.key === 'denda'" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 sm:text-sm font-medium">Rp</span>
                    </div>
                    
                    <input 
                      v-if="group.key === 'denda'"
                      :id="setting.key"
                      type="text"
                      :value="formatCurrency(form[setting.key])"
                      @input="updateCurrency($event, setting.key)"
                      class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm text-right font-medium"
                    />
                    
                    <input 
                      v-else
                      :id="setting.key"
                      v-model="form[setting.key]"
                      :type="setting.type === 'integer' ? 'number' : 'text'"
                      class="w-full rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm text-right font-medium"
                      :class="{ 
                        'pl-4 pr-12 py-2': setting.type === 'integer',
                        'px-4 py-2': setting.type !== 'integer'
                      }"
                    />
                    
                    <!-- Suffix -->
                    <div v-if="setting.type === 'integer' && group.key !== 'denda'" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 sm:text-sm text-xs border-l border-gray-200 pl-2">
                        {{ getSuffix(setting.key) }}
                      </span>
                    </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Action Bar (Sticky Bottom) -->
      <div class="fixed bottom-0 left-0 right-0 lg:left-64 z-40 bg-white border-t border-gray-200 p-4 shadow-[0_-4px_10px_rgba(0,0,0,0.02)]">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
          <p class="text-sm text-gray-500 hidden sm:block">⚠️ Perubahan akan langsung mengubah aturan di seluruh sistem</p>
          <button @click="save" :disabled="saving" class="ml-auto flex items-center gap-2 px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
            <svg v-if="saving" class="w-4 h-4 mr-1 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <svg v-else width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M16.5 3.75v3m0 0v3m0-3h3m-3 0h-3M6.75 3.75h-.375C4.996 3.75 3.86 3.75 3 4.61c-.86.86-.86 1.996-.86 3.375v.03c0 1.38 0 2.516.86 3.375.86.86 1.996.86 3.375.86h.375c1.38 0 2.516 0 3.375-.86.86-.86.86-1.996.86-3.375v-.03c0-1.38 0-2.516-.86-3.375-.86-.86-1.996-.86-3.375-.86Zm0 9H6.375C4.996 12.75 3.86 12.75 3 13.61c-.86.86-.86 1.996-.86 3.375v.03c0 1.38 0 2.516.86 3.375.86.86 1.996.86 3.375.86h.375c1.38 0 2.516 0 3.375-.86.86-.86.86-1.996.86-3.375v-.03c0-1.38 0-2.516-.86-3.375-.86-.86-1.996-.86-3.375-.86Zm9 0h-.375c-1.38 0-2.516 0-3.375.86-.86.86-.86 1.996-.86 3.375v.03c0 1.38 0 2.516.86 3.375.86.86 1.996.86 3.375.86h.375c1.38 0 2.516 0 3.375-.86.86-.86.86-1.996.86-3.375v-.03c0-1.38 0-2.516-.86-3.375-.86-.86-1.996-.86-3.375-.86Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/TailAdminLayout.vue'

const props = defineProps({ settings: Object })
const saving = ref(false)

// Group Definitions
const groupInfo = {
  peminjaman: { label: 'Aturan Peminjaman', icon: '<svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>' },
  denda: { label: 'Tarif & Denda', icon: '<svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>' },
}

const activeGroup = ref(Object.keys(props.settings)[0] || 'peminjaman')

const sortedGroups = computed(() => {
  return Object.keys(props.settings).map(key => ({
    key,
    label: groupInfo[key]?.label || key,
    icon: groupInfo[key]?.icon || ''
  }))
})

function getSuffix(key) {
  if (key.includes('hari') || key.includes('batas_ambil')) return 'hari'
  if (key.includes('pinjam') && !key.includes('lama')) return 'buku'
  if (key.includes('perpanjangan')) return 'kali'
  return ''
}

// Format number (e.g. 50000 -> 50.000)
function formatCurrency(val) {
  if (!val && val !== 0) return ''
  return String(val).replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

// Update state when currency input changes
function updateCurrency(e, key) {
  // Hanya ambil angka
  let rawValue = e.target.value.replace(/\D/g, '')
  if (rawValue === '') {
    form[key] = ''
    e.target.value = ''
    return
  }
  
  // Update state form
  form[key] = parseInt(rawValue, 10)
  
  // Format dan set ulang input value supaya kursor tetap benar (optional tapi bagus UX nya)
  e.target.value = formatCurrency(form[key])
}

// Build reactive form dari semua setting database
const form = reactive(
  Object.values(props.settings).flat()
    .reduce((acc, s) => {
      acc[s.key] = s.type === 'boolean' ? s.value === 'true' : s.type === 'integer' ? Number(s.value) : s.value
      return acc
    }, {})
)

function save() {
  saving.value = true
  const payload = Object.entries(form).map(([key, value]) => ({
    key,
    value: typeof value === 'boolean' ? (value ? 'true' : 'false') : String(value),
  }))
  router.post(route('settings.update'), { settings: payload }, {
    onFinish: () => { saving.value = false },
    preserveScroll: true
  })
}
</script>
