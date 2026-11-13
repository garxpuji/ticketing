@extends('layouts.app')

@section('title', 'Grup Teknisi & Bot Telegram')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 shrink-0">
                    <i class="fa-solid fa-users-gear text-base"></i>
                </div>
                <span>Grup Teknisi & Routing Notifikasi Bot Telegram</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Kelola pembagian tim teknisi dan sambungkan ke masing-masing grup/topic Telegram untuk notifikasi tiket otomatis.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openCreateModal()" class="w-full sm:w-auto min-h-[42px] px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm shadow-emerald-600/20 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <i class="fa-solid fa-plus"></i> Tambah Grup Baru
            </button>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">Total Grup</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalGroups }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">Grup Aktif</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $activeGroups }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-sky-600 dark:text-sky-400 font-semibold uppercase tracking-wider">Telegram Terhubung</div>
            <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ $connectedTelegram }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold uppercase tracking-wider">Teknisi Terpetakan</div>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $totalTechnicians }}</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative transition-colors">
        <form action="{{ route('technician-groups.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Cari Grup</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode (TEAM MTC 1), chat ID..." class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status Grup</label>
                <select name="status" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 transition">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 min-h-[42px] px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition">
                    <i class="fa-solid fa-filter"></i> Terapkan Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('technician-groups.index') }}" class="min-h-[42px] px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-xl text-xs flex items-center justify-center transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                        <th class="py-4 px-4 sm:px-6">Grup & Kode Tim</th>
                        <th class="py-4 px-4 sm:px-6">Koneksi Telegram</th>
                        <th class="py-4 px-4 sm:px-6">Anggota Teknisi</th>
                        <th class="py-4 px-4 sm:px-6 text-center">Tiket</th>
                        <th class="py-4 px-4 sm:px-6 text-center">Status</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs text-slate-600 dark:text-slate-300">
                    @forelse($groups as $group)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                        <!-- Group Name & Code -->
                        <td class="py-4 px-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold shrink-0">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $group->name }}</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ $group->code }}
                                        </span>
                                        @if($group->description)
                                        <span class="text-slate-400 text-[11px] truncate max-w-xs" title="{{ $group->description }}">
                                            • {{ $group->description }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Telegram Connection -->
                        <td class="py-4 px-4 sm:px-6">
                            @if($group->telegram_chat_id)
                            <div class="space-y-1">
                                <div class="flex items-center gap-1.5 font-mono text-[11px] text-slate-800 dark:text-slate-200">
                                    <i class="fa-brands fa-telegram text-sky-500 text-sm"></i>
                                    <code>{{ $group->telegram_chat_id }}</code>
                                </div>
                                @if($group->telegram_topic_id)
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <i class="fa-solid fa-hashtag text-[9px] text-purple-400"></i>
                                    <span>Topic / Thread ID: <b>#{{ $group->telegram_topic_id }}</b></span>
                                </div>
                                @endif
                            </div>
                            @else
                            <span class="inline-flex items-center gap-1.5 text-slate-400 dark:text-slate-500 text-[11px] italic">
                                <i class="fa-solid fa-circle-exclamation text-amber-500"></i> Belum dikonfigurasi (Fallback ke Global)
                            </span>
                            @endif
                        </td>

                        <!-- Members -->
                        <td class="py-4 px-4 sm:px-6">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20">
                                    <i class="fa-solid fa-user-gear mr-1.5 text-[10px]"></i> {{ $group->users_count }} Teknisi
                                </span>
                            </div>
                        </td>

                        <!-- Tickets count -->
                        <td class="py-4 px-4 sm:px-6 text-center">
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $group->tickets_count }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-4 sm:px-6 text-center">
                            @if($group->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200/50 dark:border-rose-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Nonaktif
                            </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 sm:px-6 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($group->telegram_chat_id)
                                <button type="button" 
                                        onclick="testTelegram({{ $group->id }}, '{{ addslashes($group->name) }}')"
                                        id="btn-test-{{ $group->id }}"
                                        class="px-2.5 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-500/20 text-xs font-semibold flex items-center gap-1.5 transition border border-sky-200/50 dark:border-sky-500/20"
                                        title="Kirim pesan tes ke grup Telegram">
                                    <i class="fa-brands fa-telegram text-sm"></i>
                                    <span class="hidden sm:inline">Tes Bot</span>
                                </button>
                                @endif

                                <button type="button" 
                                        onclick="openEditModal({{ json_encode($group) }})" 
                                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition" 
                                        title="Edit Grup">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>

                                <form action="{{ route('technician-groups.destroy', $group->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus grup \'{{ addslashes($group->name) }}\'?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center transition" 
                                            title="Hapus Grup">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                <i class="fa-solid fa-users-slash text-xl"></i>
                            </div>
                            <p class="font-semibold text-slate-700 dark:text-slate-300">Belum ada grup teknisi</p>
                            <p class="text-xs mt-1">Tambahkan grup pertama (misal: GROUP 1, TEAM MTC 1) untuk menghubungkan notifikasi Telegram.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($groups->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $groups->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Create Group -->
<div id="createModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden transition-all">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-emerald-600"></i> Tambah Grup Teknisi Baru
            </h3>
            <button onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="{{ route('technician-groups.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Grup <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="Contoh: GROUP 1, GROUP 2" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Kode Tim <span class="text-rose-500">*</span></label>
                <input type="text" name="code" required placeholder="Contoh: TEAM MTC 1, TEAM MTC 2" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white uppercase focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
                <span class="text-[10px] text-slate-400 mt-1 block">Kode unik pengenal untuk tiket dan laporan.</span>
            </div>

            <div class="p-3.5 rounded-xl bg-sky-50/60 dark:bg-sky-500/10 border border-sky-100 dark:border-sky-500/20 space-y-3">
                <div class="flex items-center gap-2 text-sky-900 dark:text-sky-300 text-xs font-bold">
                    <i class="fa-brands fa-telegram text-sky-500 text-sm"></i> Konfigurasi Notifikasi Bot Telegram
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Telegram Chat ID</label>
                    <input type="text" name="telegram_chat_id" placeholder="Contoh: -1001234567890" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-sky-500">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">ID Grup Telegram (biasanya diawali minus <code>-100...</code>). Bot harus sudah diundang ke grup sebagai admin/member.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Telegram Topic ID (Thread ID) <span class="text-[10px] font-normal text-slate-400 lowercase">(opsional)</span></label>
                    <input type="text" name="telegram_topic_id" placeholder="Contoh: 105 (kosongkan jika bukan supergroup topic)" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-sky-500">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Gunakan ini jika grup Anda adalah Supergroup Telegram bertema Forum/Topics.</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Deskripsi / Area Kerja</label>
                <textarea name="description" rows="2" placeholder="Catatan wilayah kerja atau spesialisasi tim..." class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="create_is_active" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-400 h-4 w-4">
                <label for="create_is_active" class="text-xs text-slate-700 dark:text-slate-300 font-medium">Grup Aktif</label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm shadow-emerald-600/20 transition">Simpan Grup</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Group -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden transition-all">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i> Edit Grup Teknisi
            </h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Grup <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="edit_name" required class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Kode Tim <span class="text-rose-500">*</span></label>
                <input type="text" name="code" id="edit_code" required class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white uppercase focus:outline-none focus:border-emerald-500">
            </div>

            <div class="p-3.5 rounded-xl bg-sky-50/60 dark:bg-sky-500/10 border border-sky-100 dark:border-sky-500/20 space-y-3">
                <div class="flex items-center gap-2 text-sky-900 dark:text-sky-300 text-xs font-bold">
                    <i class="fa-brands fa-telegram text-sky-500 text-sm"></i> Konfigurasi Notifikasi Bot Telegram
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Telegram Chat ID</label>
                    <input type="text" name="telegram_chat_id" id="edit_telegram_chat_id" placeholder="-100xxxxxxxxxx" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Telegram Topic ID (Thread ID)</label>
                    <input type="text" name="telegram_topic_id" id="edit_telegram_topic_id" placeholder="Opsional" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-sky-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Deskripsi / Area Kerja</label>
                <textarea name="description" id="edit_description" rows="2" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-400 h-4 w-4">
                <label for="edit_is_active" class="text-xs text-slate-700 dark:text-slate-300 font-medium">Grup Aktif</label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm shadow-emerald-600/20 transition">Update Grup</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(group) {
    document.getElementById('edit_name').value = group.name;
    document.getElementById('edit_code').value = group.code;
    document.getElementById('edit_telegram_chat_id').value = group.telegram_chat_id || '';
    document.getElementById('edit_telegram_topic_id').value = group.telegram_topic_id || '';
    document.getElementById('edit_description').value = group.description || '';
    document.getElementById('edit_is_active').checked = !!group.is_active;

    document.getElementById('editForm').action = `/technician-groups/${group.id}`;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function testTelegram(groupId, groupName) {
    const btn = document.getElementById(`btn-test-${groupId}`);
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i> Mengirim...';

    fetch(`/technician-groups/${groupId}/test-telegram`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(async (response) => {
        const data = await response.json();
        if (response.ok && data.success) {
            alert(`✅ ${data.message}`);
        } else {
            alert(`❌ Gagal: ${data.message || 'Terjadi kesalahan'}`);
        }
    })
    .catch((err) => {
        alert(`❌ Terjadi kesalahan jaringan: ${err.message}`);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
@endpush
@endsection

