<template>
  <AdminLayout title="Data Anggota">
    <template #topbar-actions>
      <div class="flex items-center gap-2">
        <button @click="showImportModal = true" class="btn py-2 btn-secondary text-sm font-medium gap-1.5 bg-white border-slate-200 shadow-sm text-slate-600 hover:bg-slate-50">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Impor Excel
        </button>
        <button @click="openAddModal" class="btn py-2 btn-primary text-sm font-medium gap-1.5">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M12 4.5v15m7.5-7.5h-15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Tambah Anggota
        </button>
      </div>
    </template>

    <!-- Flash -->
    <div v-if="$page.props.flash?.success"
      class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-medium flex items-center gap-2">
      <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash?.error"
      class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-sm font-medium flex items-center gap-2">
      <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
      {{ $page.props.flash.error }}
    </div>

    <!-- ── Tab Type Anggota ── -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4 overflow-hidden">
      <div class="flex border-b border-gray-200 overflow-x-auto">
        <button
          v-for="tab in typeTabs"
          :key="tab.value"
          @click="switchTab(tab.value)"
          class="relative flex items-center gap-2 px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors flex-shrink-0"
          :class="activeType === tab.value
            ? 'text-emerald-600 border-b-2 border-emerald-500 bg-emerald-50/50'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 border-b-2 border-transparent'"
        >
          <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
          {{ tab.label }}
          <span
            class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold"
            :class="activeType === tab.value
              ? 'bg-emerald-500 text-white'
              : 'bg-gray-100 text-gray-500'"
          >
            {{ tab.value === '' ? totalAllCount : (typeCounts[tab.value] || 0) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4 items-center justify-between">
      <div class="flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap">
        <span>Tampilkan</span>
        <select v-model="perPage" @change="applyFilter"
          class="px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
        </select>
        <span>baris</span>
      </div>

      <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
        <select v-model="filters.status" @change="applyFilter"
          class="w-full md:w-auto px-4 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
          <option value="">Semua Status</option>
          <option v-if="activeType === '' || activeType === 'umum'" value="pending">Pending</option>
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>

        <select v-if="activeType === '' || activeType === 'siswa'" v-model="filters.class_id" @change="applyFilter"
          class="w-full md:w-auto px-4 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
          <option value="">Semua Kelas</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <div class="relative w-full md:w-96">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input v-model="filters.search" type="text" placeholder="Cari nama atau kode..."
            class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm mt-4">
      <table class="w-full min-w-[900px]">
        <thead class="bg-gray-200">
          <tr>
            <th class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">No</th>
            <th class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Anggota</th>
            <th class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Kode</th>
            <th v-if="activeType === ''" class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Tipe</th>
            <th v-if="activeType === '' || activeType === 'siswa'" class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Kelas</th>
            <th class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Status</th>
            <th class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Bergabung</th>
            <th class="px-3 md:px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="members.data && members.data.length > 0" v-for="(m, i) in members.data" :key="m.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-3 md:px-6 py-3 md:py-4 text-sm font-medium text-gray-900">{{ (members.from || 1) + i }}</td>
            <td class="px-3 md:px-6 py-3 md:py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                  :class="m.type === 'siswa' ? 'bg-gradient-to-br from-blue-500 to-blue-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600'">
                  {{ m.name[0].toUpperCase() }}
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ m.name }}</div>
                  <div class="text-xs text-gray-400">{{ m.user?.email || '-' }}</div>
                </div>
              </div>
            </td>
            <td class="px-3 md:px-6 py-3 md:py-4 font-mono text-sm text-gray-500">{{ m.member_code }}</td>
            <!-- Kolom Tipe: hanya tampil di tab "Semua" -->
            <td v-if="activeType === ''" class="px-3 md:px-6 py-3 md:py-4">
              <span class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="{ 'bg-blue-100 text-blue-700': m.type === 'siswa', 'bg-emerald-100 text-emerald-700': m.type === 'guru', 'bg-gray-100 text-gray-600': m.type === 'umum' }">
                {{ m.type === 'guru' ? 'Guru/Karyw.' : m.type }}
              </span>
            </td>
            <!-- Kolom Kelas: tampil di "Semua" dan "Siswa" -->
            <td v-if="activeType === '' || activeType === 'siswa'" class="px-3 md:px-6 py-3 md:py-4 text-sm text-gray-600">{{ m.kelas?.name || '-' }}</td>
            <td class="px-3 md:px-6 py-3 md:py-4">
              <span class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="{
                  'bg-amber-100 text-amber-700': m.status === 'pending',
                  'bg-green-100 text-green-700': m.status === 'aktif',
                  'bg-red-100 text-red-700': m.status === 'suspended' || m.status === 'ditolak',
                  'bg-gray-100 text-gray-500': m.status === 'nonaktif',
                }">
                {{ m.status }}
              </span>
            </td>
            <td class="px-3 md:px-6 py-3 md:py-4 text-sm text-gray-500">{{ formatDate(m.created_at) }}</td>
            <td class="px-3 md:px-6 py-3 md:py-4">
              <div class="flex gap-2">
                <button @click="openDetailModal(m)" class="px-3 py-1 text-sm text-blue-600 hover:text-blue-700 font-medium">Detail</button>
                <button @click="openEditModal(m)" class="px-3 py-1 text-sm text-amber-600 hover:text-amber-700 font-medium">Edit</button>
                <button @click="confirmDelete(m)" class="px-3 py-1 text-sm text-red-600 hover:text-red-700 font-medium">Hapus</button>
                <button v-if="m.status === 'pending'" @click="confirmApprove(m)" class="px-3 py-1 text-sm text-emerald-600 hover:text-emerald-700 font-medium">Setujui</button>
                <button v-if="m.status === 'pending'" @click="confirmReject(m)" class="px-3 py-1 text-sm text-rose-600 hover:text-rose-700 font-medium">Tolak</button>
              </div>
            </td>
          </tr>
          <!-- Empty state -->
          <tr v-if="!members.data || members.data.length === 0">
            <td colspan="8" class="px-6 py-16 text-center">
              <svg width="40" height="40" fill="none" viewBox="0 0 24 24" class="mx-auto mb-3 text-gray-300"><path stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
              <div class="text-gray-500 font-medium">Belum ada anggota ditemukan</div>
              <p class="text-sm text-gray-400 mt-1">Coba filter lain atau tambah anggota baru</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="members.data && members.data.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">
      <div class="text-sm text-gray-500">
        Menampilkan <span class="font-semibold text-gray-800">{{ members.from || 0 }}</span> sampai <span class="font-semibold text-gray-800">{{ members.to || 0 }}</span> dari <span class="font-semibold text-gray-800">{{ members.total }}</span> anggota
      </div>
      <div v-if="members.last_page > 1" class="flex gap-1.5 items-center">
        <template v-for="(link, idx) in members.links" :key="idx">
          <Link v-if="link.url" :href="link.url"
            class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-colors border"
            :class="link.active ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 hover:text-gray-800'"
            v-html="link.label.includes('Previous') ? '‹' : (link.label.includes('Next') ? '›' : link.label)" />
          <span v-else class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 text-sm border border-gray-200" v-html="link.label.includes('Previous') ? '‹' : (link.label.includes('Next') ? '›' : link.label)"></span>
        </template>
      </div>
    </div>

    <!-- ══════ MODAL: Tambah / Edit Anggota ══════ -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showAddModal = false">
      <div class="w-full max-w-2xl bg-white rounded-2xl border border-gray-200 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">{{ editingMember ? 'Edit Anggota' : 'Tambah Anggota Baru' }}</h3>
          </div>
          <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
        <!-- Body -->
        <form @submit.prevent="submitMemberForm" class="px-6 py-5 overflow-y-auto space-y-5">
          <!-- Tipe -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Anggota <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="addForm.type" value="siswa" class="text-emerald-600 focus:ring-emerald-500" />
                <span class="text-sm font-medium text-gray-700">Siswa</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="addForm.type" value="guru" class="text-emerald-600 focus:ring-emerald-500" />
                <span class="text-sm font-medium text-gray-700">Guru / Karyawan</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="addForm.type" value="umum" class="text-emerald-600 focus:ring-emerald-500" />
                <span class="text-sm font-medium text-gray-700">Umum</span>
              </label>
            </div>
          </div>

          <!-- Nama & NIS/NIP -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
              <input v-model="addForm.name" type="text" required placeholder="Ahmad Firdaus"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
              <p v-if="addForm.errors.name" class="text-xs text-red-500 mt-1">{{ addForm.errors.name }}</p>
            </div>
            <!-- NIS/NIP hanya untuk siswa dan guru -->
            <div v-if="addForm.type !== 'umum'">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ addForm.type === 'siswa' ? 'NIS' : 'NIP' }} <span class="text-red-500">*</span></label>
              <input v-model="addForm.nis_nip" type="text" :required="addForm.type !== 'umum'" :placeholder="addForm.type === 'siswa' ? '12345' : '19800101...'"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
              <p v-if="addForm.errors.nis_nip" class="text-xs text-red-500 mt-1">{{ addForm.errors.nis_nip }}</p>
            </div>
          </div>

          <!-- Siswa: Kelas & NISN -->
          <div v-if="addForm.type === 'siswa'" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kelas <span class="text-red-500">*</span></label>
              <select v-model="addForm.class_id" required
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
                <option value="">Pilih Kelas</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <p v-if="addForm.errors.class_id" class="text-xs text-red-500 mt-1">{{ addForm.errors.class_id }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NISN</label>
              <input v-model="addForm.nisn" type="text" placeholder="00xxxxxxxx"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>

          <!-- Guru: Pangkat -->
          <div v-if="addForm.type === 'guru'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pangkat / Golongan</label>
            <input v-model="addForm.pangkat_golongan" type="text" placeholder="Pembina / IV a"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
          </div>

          <!-- Detail Data -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
              <select v-model="addForm.jenis_kelamin"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
                <option value="">Pilih</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
              <input v-model="addForm.phone" type="tel" placeholder="08xxxxxxxx"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir</label>
              <input v-model="addForm.tempat_lahir" type="text" placeholder="Jakarta"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
              <input v-model="addForm.tanggal_lahir" type="date"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
              <input v-model="addForm.nik" type="text" placeholder="32xxxxxxxxxxx"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Agama</label>
              <select v-model="addForm.agama"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
                <option value="">Pilih</option>
                <option value="Islam">Islam</option>
                <option value="Kristen">Kristen</option>
                <option value="Katolik">Katolik</option>
                <option value="Hindu">Hindu</option>
                <option value="Buddha">Buddha</option>
                <option value="Konghucu">Konghucu</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
            <textarea v-model="addForm.address" rows="2" placeholder="Jl. Raya ..."
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">Batal</button>
            <button type="submit" :disabled="addForm.processing" class="px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-lg text-sm transition disabled:opacity-50">
              {{ addForm.processing ? 'Menyimpan...' : (editingMember ? 'Simpan Perubahan' : 'Simpan Anggota') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════ MODAL: Import Excel ══════ -->
    <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showImportModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-gray-900">Impor Data Anggota</h2>
            <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
              <svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
          <form @submit.prevent="submitImport" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Anggota</label>
              <select v-model="importForm.type" required
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-emerald-500">
                <option value="siswa">Siswa</option>
                <option value="guru">Guru / Karyawan</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
              <input type="file" @change="e => importForm.file = e.target.files[0]" accept=".xlsx,.xls,.csv"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" required />
              <p v-if="importForm.errors.file" class="text-xs text-red-500 mt-1">{{ importForm.errors.file }}</p>
            </div>
            <div class="pt-2 flex justify-between items-center">
              <a :href="route('members.template', importForm.type)" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                ↓ Download Template
              </a>
              <div class="flex gap-2">
                <button type="button" @click="showImportModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">Batal</button>
                <button type="submit" :disabled="importForm.processing" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-lg text-sm transition disabled:opacity-50">
                  {{ importForm.processing ? 'Mengimpor...' : 'Impor' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ══════ MODAL: Detail Anggota ══════ -->
    <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showDetailModal = false">
      <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold"
              :class="selectedMember?.type === 'siswa' ? 'bg-gradient-to-br from-blue-500 to-blue-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600'">
              {{ selectedMember?.name[0].toUpperCase() }}
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-900">{{ selectedMember?.name }}</h2>
              <p class="text-sm text-gray-500 font-mono">{{ selectedMember?.member_code }}</p>
            </div>
          </div>
          <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
        <div class="px-6 py-5 overflow-y-auto">
          <!-- Badges -->
          <div class="flex gap-3 mb-5">
            <span class="px-2 py-1 text-xs font-semibold rounded-full"
              :class="{ 'bg-blue-100 text-blue-700': selectedMember?.type === 'siswa', 'bg-green-100 text-green-700': selectedMember?.type === 'guru', 'bg-gray-100 text-gray-600': selectedMember?.type === 'umum' }">
              {{ selectedMember?.type?.toUpperCase() }}
            </span>
            <span class="px-2 py-1 text-xs font-semibold rounded-full"
              :class="{
                'bg-amber-100 text-amber-700': selectedMember?.status === 'pending',
                'bg-green-100 text-green-700': selectedMember?.status === 'aktif',
                'bg-red-100 text-red-700': selectedMember?.status === 'suspended' || selectedMember?.status === 'ditolak',
                'bg-gray-100 text-gray-500': selectedMember?.status === 'nonaktif',
              }">
              {{ selectedMember?.status }}
            </span>
          </div>

          <!-- Info Grid -->
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Akun Login</div>
              <div class="text-sm font-medium text-gray-900">{{ selectedMember?.user?.email || 'Belum ada akun' }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">No. Handphone</div>
              <div class="text-sm font-medium text-gray-900">{{ selectedMember?.phone || '-' }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Tgl Bergabung</div>
              <div class="text-sm font-medium text-gray-900">{{ selectedMember ? formatDate(selectedMember.created_at) : '-' }}</div>
            </div>

            <!-- Siswa -->
            <template v-if="selectedMember?.type === 'siswa'">
              <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">NIS</div>
                <div class="text-sm font-medium text-gray-900">{{ selectedMember?.nis_nip || '-' }}</div>
              </div>
              <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">NISN</div>
                <div class="text-sm font-medium text-gray-900">{{ selectedMember?.nisn || '-' }}</div>
              </div>
              <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Kelas</div>
                <div class="text-sm font-medium text-gray-900">{{ selectedMember?.kelas?.name || '-' }}</div>
              </div>
            </template>

            <!-- Guru -->
            <template v-if="selectedMember?.type === 'guru'">
              <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">NIP</div>
                <div class="text-sm font-medium text-gray-900">{{ selectedMember?.nis_nip || '-' }}</div>
              </div>
              <div class="p-3 rounded-lg bg-gray-50 border border-gray-100 col-span-2">
                <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Pangkat / Golongan</div>
                <div class="text-sm font-medium text-gray-900">{{ selectedMember?.pangkat_golongan || '-' }}</div>
              </div>
            </template>

            <!-- Data Pribadi -->
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">NIK</div>
              <div class="text-sm font-medium text-gray-900">{{ selectedMember?.nik || '-' }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">TTL</div>
              <div class="text-sm font-medium text-gray-900">{{ selectedMember?.tempat_lahir || '-' }}, {{ selectedMember?.tanggal_lahir ? formatDate(selectedMember.tanggal_lahir) : '-' }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Jenis Kelamin</div>
              <div class="text-sm font-medium text-gray-900">{{ selectedMember?.jenis_kelamin === 'L' ? 'Laki-laki' : (selectedMember?.jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100 col-span-2 lg:col-span-3">
              <div class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Alamat</div>
              <div class="text-sm font-medium text-gray-900 leading-relaxed">{{ selectedMember?.address || '-' }}</div>
            </div>
          </div>
        </div>
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
          <Link :href="route('members.show', selectedMember?.id)" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
            Lihat Histori Sirkulasi &rarr;
          </Link>
          <button @click="showDetailModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">Tutup</button>
        </div>
      </div>
    </div>

    <!-- ══════ MODAL: Hapus Anggota ══════ -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="deleteTarget = null">
      <div class="bg-white rounded-2xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Hapus Anggota</h3>
            <p class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-5">
          Yakin ingin menghapus anggota <strong>"{{ deleteTarget.name }}"</strong>?
        </p>
        <div class="flex justify-end gap-3">
          <button @click="deleteTarget = null" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">Batal</button>
          <button @click="doDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg text-sm transition">Ya, Hapus</button>
        </div>
      </div>
    </div>

    <!-- ══════ MODAL: Setujui Anggota ══════ -->
    <div v-if="approveTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="approveTarget = null">
      <div class="bg-white rounded-2xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Setujui Anggota</h3>
            <p class="text-sm text-gray-500">Anggota ini akan diberi akses ke sistem.</p>
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-5">
          Yakin ingin menyetujui pendaftaran <strong>"{{ approveTarget.name }}"</strong>?
        </p>
        <div class="flex justify-end gap-3">
          <button @click="approveTarget = null" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">Batal</button>
          <button @click="doApprove" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-sm transition">Ya, Setujui</button>
        </div>
      </div>
    </div>

    <!-- ══════ MODAL: Tolak Anggota ══════ -->
    <div v-if="rejectTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="rejectTarget = null">
      <div class="bg-white rounded-2xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#e11d48" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Tolak Anggota</h3>
            <p class="text-sm text-gray-500">Pendaftaran akan ditolak.</p>
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-3">
          Berikan alasan penolakan untuk <strong>"{{ rejectTarget.name }}"</strong>:
        </p>
        <textarea v-model="rejectForm.reason" rows="3" placeholder="Alasan penolakan..." class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-rose-500 mb-2"></textarea>
        <p v-if="rejectForm.errors.reason" class="text-xs text-rose-500 mb-4">{{ rejectForm.errors.reason }}</p>

        <div class="flex justify-end gap-3 mt-4">
          <button @click="rejectTarget = null" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">Batal</button>
          <button @click="doReject" :disabled="rejectForm.processing" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-sm transition disabled:opacity-50">
            {{ rejectForm.processing ? 'Menyimpan...' : 'Tolak' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, h } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/TailAdminLayout.vue'

const props = defineProps({
  members: Object,
  filters: Object,
  classes: Array,
  typeCounts: Object,
})

// ── Tab Definitions ──
const typeTabs = [
  {
    value: '',
    label: 'Semua',
    icon: {
      render() {
        return h('svg', { width: '16', height: '16', fill: 'none', viewBox: '0 0 24 24' }, [
          h('path', { d: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
        ])
      }
    }
  },
  {
    value: 'siswa',
    label: 'Siswa',
    icon: {
      render() {
        return h('svg', { width: '16', height: '16', fill: 'none', viewBox: '0 0 24 24' }, [
          h('path', { d: 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 10.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
        ])
      }
    }
  },
  {
    value: 'guru',
    label: 'Guru / Karyawan',
    icon: {
      render() {
        return h('svg', { width: '16', height: '16', fill: 'none', viewBox: '0 0 24 24' }, [
          h('path', { d: 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
        ])
      }
    }
  },
  {
    value: 'umum',
    label: 'Umum',
    icon: {
      render() {
        return h('svg', { width: '16', height: '16', fill: 'none', viewBox: '0 0 24 24' }, [
          h('path', { d: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
        ])
      }
    }
  },
]

// ── State ──
const activeType = ref(props.filters?.type || '')

const filters = reactive({
  search:   props.filters?.search   || '',
  status:   props.filters?.status   || '',
  class_id: props.filters?.class_id || '',
})
const perPage = ref(props.filters?.per_page || 20)

// Total semua = jumlah semua typeCounts
const totalAllCount = computed(() => {
  return Object.values(props.typeCounts || {}).reduce((sum, v) => sum + Number(v), 0)
})

// ── Switch Tab ──
function switchTab(typeValue) {
  activeType.value = typeValue
  // Reset class filter jika bukan siswa
  if (typeValue !== 'siswa' && typeValue !== '') {
    filters.class_id = ''
  }
  // Reset status filter jika pindah ke tab yang tidak mendukung status tersebut
  if ((typeValue === 'siswa' || typeValue === 'guru') && ['pending', 'suspended', 'ditolak'].includes(filters.status)) {
    filters.status = ''
  }
  if (['suspended', 'ditolak'].includes(filters.status)) {
    filters.status = ''
  }
  applyFilter()
}

function applyFilter() {
  router.get(route('members.index'), {
    ...filters,
    type: activeType.value,
    per_page: perPage.value,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => applyFilter(), 400)
})

// ── Detail Modal ──
const showDetailModal = ref(false)
const selectedMember = ref(null)
function openDetailModal(member) { selectedMember.value = member; showDetailModal.value = true }

// ── Add / Edit Modal ──
const showAddModal = ref(false)
const editingMember = ref(null)
const addForm = useForm({
  type: 'siswa', name: '', nis_nip: '', class_id: '', nisn: '',
  pangkat_golongan: '', nik: '', agama: '', tempat_lahir: '',
  tanggal_lahir: '', jenis_kelamin: '', phone: '', address: '',
})

function openAddModal() {
  editingMember.value = null
  addForm.reset()
  addForm.type = activeType.value === 'guru' ? 'guru' : activeType.value === 'umum' ? 'umum' : 'siswa'
  showAddModal.value = true
}

function openEditModal(member) {
  editingMember.value = member
  addForm.type = member.type || 'siswa'
  addForm.name = member.name || ''
  addForm.nis_nip = member.nis_nip || ''
  addForm.class_id = member.class_id || ''
  addForm.nisn = member.nisn || ''
  addForm.pangkat_golongan = member.pangkat_golongan || ''
  addForm.nik = member.nik || ''
  addForm.agama = member.agama || ''
  addForm.tempat_lahir = member.tempat_lahir || ''
  addForm.tanggal_lahir = member.tanggal_lahir ? member.tanggal_lahir.substring(0, 10) : ''
  addForm.jenis_kelamin = member.jenis_kelamin || ''
  addForm.phone = member.phone || ''
  addForm.address = member.address || ''
  showAddModal.value = true
}

function submitMemberForm() {
  if (editingMember.value) {
    addForm.put(route('members.update', editingMember.value.id), {
      preserveState: true,
      onSuccess: () => { showAddModal.value = false; editingMember.value = null; addForm.reset() },
    })
  } else {
    addForm.post(route('members.store'), {
      preserveState: true,
      onSuccess: () => { showAddModal.value = false; addForm.reset() },
    })
  }
}

// ── Delete ──
const deleteTarget = ref(null)
function confirmDelete(member) { deleteTarget.value = member }
function doDelete() {
  router.delete(route('members.destroy', deleteTarget.value.id), {
    onSuccess: () => { deleteTarget.value = null },
  })
}

// ── Import Modal ──
const showImportModal = ref(false)
const importForm = useForm({ type: 'siswa', file: null })

function submitImport() {
  importForm.post(route('members.import'), {
    preserveScroll: true,
    onSuccess: () => { showImportModal.value = false; importForm.reset() },
  })
}

// ── Actions ──
const approveTarget = ref(null)
function confirmApprove(member) { approveTarget.value = member }
function doApprove() {
  router.post(route('members.approve', approveTarget.value.id), {}, {
    onSuccess: () => { approveTarget.value = null },
  })
}

const rejectTarget = ref(null)
const rejectForm = useForm({ reason: '' })

function confirmReject(member) {
  rejectTarget.value = member
  rejectForm.reset()
  rejectForm.clearErrors()
}

function doReject() {
  rejectForm.post(route('members.reject', rejectTarget.value.id), {
    onSuccess: () => { rejectTarget.value = null; rejectForm.reset() }
  })
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>
