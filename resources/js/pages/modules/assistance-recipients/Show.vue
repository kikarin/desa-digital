<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Eye, Download, Calendar, User, Home, FileText } from 'lucide-vue-next';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        assistance_program_id: number;
        target_type: string;
        family_id?: number;
        resident_id?: number;
        status: string;
        tanggal_penyaluran?: string;
        foto_bukti_pengambilan?: string;
        foto_bukti_url?: string;
        absen_mandiri: boolean;
        catatan?: string;
        program?: {
            id: number;
            nama_program: string;
            tahun: number;
            periode?: string;
            tanggal_penyaluran?: string;
            jam_mulai_pengambilan?: string;
            jam_selesai_pengambilan?: string;
        };
        family?: {
            id: number;
            no_kk: string;
        };
        resident?: {
            id: number;
            nama: string;
            nik: string;
        };
        kepala_keluarga?: {
            id: number;
            nama: string;
            nik: string;
        };
        penerima_lapangan?: {
            id: number;
            nama: string;
            nik: string;
        };
    };
}>();

const breadcrumbs = [
    { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
    { title: 'Penerima Bantuan', href: '/program-bantuan/penerima' },
    { title: 'Detail Penerima Bantuan', href: '#' },
];

const fields = computed(() => {
    const baseFields = [
        { 
            label: 'Program Bantuan', 
            value: props.item.program?.nama_program || '-',
            icon: FileText,
        },
        { 
            label: 'Tahun', 
            value: props.item.program?.tahun?.toString() || '-',
        },
        {
            label: 'Periode', 
            value: props.item.program?.periode || '-',
        },
        {
            label: 'Jadwal Penyaluran',
            value: getJadwalLabel(),
        },
        { 
            label: 'Tipe Penerima', 
            value: props.item.target_type === 'KELUARGA' ? 'Keluarga' : 'Individu',
        },
    ];

    if (props.item.target_type === 'KELUARGA' && props.item.family) {
        baseFields.push({
            label: 'No. KK',
            value: props.item.family.no_kk || '-',
            icon: Home,
        });
    } else if (props.item.target_type === 'INDIVIDU' && props.item.resident) {
        baseFields.push({
            label: 'Nama Warga',
            value: props.item.resident.nama || '-',
            icon: User,
        });
        baseFields.push({
            label: 'NIK',
            value: props.item.resident.nik || '-',
        });
    }

    baseFields.push(
        {
            label: 'Kepala Keluarga',
            value: props.item.kepala_keluarga?.nama || '-',
        },
        {
            label: 'Penerima Lapangan',
            value: props.item.penerima_lapangan?.nama || '-',
        },
        {
            label: 'Status',
            value: getStatusLabel(props.item.status),
        },
        {
            label: 'Tanggal Penyaluran',
            value: props.item.tanggal_penyaluran 
                ? new Date(props.item.tanggal_penyaluran).toLocaleString('id-ID', { 
                    timeZone: 'Asia/Jakarta',
                    dateStyle: 'long',
                    timeStyle: 'short',
                })
                : '-',
            icon: Calendar,
        },
        {
            label: 'Absen Mandiri',
            value: props.item.absen_mandiri ? 'Ya' : 'Tidak (Admin)',
        },
        {
            label: 'Catatan',
            value: props.item.catatan || '-',
        }
    );

    return baseFields;
});

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        'PROSES': 'Proses',
        'DATANG': 'Datang',
        'TIDAK_DATANG': 'Tidak Datang',
    };
    return labels[status] || status;
};

const getJadwalLabel = () => {
    const program = props.item.program;
    if (!program || !program.tanggal_penyaluran) {
        return '-';
    }
    
    const tanggal = new Date(program.tanggal_penyaluran).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
    
    if (program.jam_mulai_pengambilan && program.jam_selesai_pengambilan) {
        const jamMulai = program.jam_mulai_pengambilan.substring(0, 5); // Format HH:mm
        const jamSelesai = program.jam_selesai_pengambilan.substring(0, 5);
        return `${tanggal}, ${jamMulai} - ${jamSelesai} WIB`;
    } else if (program.jam_mulai_pengambilan) {
        const jamMulai = program.jam_mulai_pengambilan.substring(0, 5);
        return `${tanggal}, mulai ${jamMulai} WIB`;
    }
    
    return tanggal;
};

const handleEdit = () => {
    router.visit(`/program-bantuan/penerima/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/program-bantuan/penerima/${props.item.id}`, {
        onSuccess: () => {
            router.visit('/program-bantuan/penerima');
        },
    });
};

const openFotoBukti = () => {
    if (props.item.foto_bukti_url) {
        window.open(props.item.foto_bukti_url, '_blank');
    }
};

const downloadFotoBukti = () => {
    if (props.item.foto_bukti_url) {
        const link = document.createElement('a');
        link.href = props.item.foto_bukti_url;
        link.download = `bukti-pengambilan-${props.item.id}.jpg`;
        link.click();
    }
};
</script>

<template>
    <PageShow
        title="Detail Penerima Bantuan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="[]"
        @edit="handleEdit"
        @delete="handleDelete"
    >
        <!-- Foto Bukti Section -->
        <div v-if="item.foto_bukti_url" class="mt-6">
            <div class="bg-card border rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <Eye class="h-5 w-5" />
                        Foto Bukti Pengambilan
                    </h3>
                    <div class="flex gap-2">
                        <button
                            @click="openFotoBukti"
                            class="px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors flex items-center gap-2"
                        >
                            <Eye class="h-4 w-4" />
                            Lihat
                        </button>
                        <button
                            @click="downloadFotoBukti"
                            class="px-3 py-1.5 text-sm font-medium text-green-600 bg-green-50 hover:bg-green-100 rounded-md transition-colors flex items-center gap-2"
                        >
                            <Download class="h-4 w-4" />
                            Download
                        </button>
                    </div>
                </div>
                <div class="relative rounded-lg overflow-hidden border-2 border-dashed border-gray-300 bg-gray-50">
                    <img
                        :src="item.foto_bukti_url"
                        alt="Foto Bukti Pengambilan"
                        class="w-full h-auto max-h-96 object-contain cursor-pointer hover:opacity-90 transition-opacity"
                        @click="openFotoBukti"
                    />
                </div>
                <p v-if="item.absen_mandiri" class="mt-3 text-sm text-muted-foreground flex items-center gap-2">
                    <span class="px-2 py-1 text-xs font-semibold text-indigo-800 bg-indigo-100 rounded-full">
                        Absen Mandiri via PWA
                    </span>
                </p>
            </div>
        </div>
    </PageShow>
</template>
