<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        jenis_surat_id: number;
        jenis_surat_nama: string;
        resident_nama: string;
        resident_nik: string;
        tanggal_surat: string;
        status: string;
        rt_verifikasi_id: number | null;
        rt_verifikasi_at: string | null;
        rt_catatan: string | null;
        rt_verifikasi: {
            id: number;
            name: string;
        } | null;
        created_at: string;
        updated_at: string;
    };
    atribut_detail?: Array<{
        id: number;
        atribut_nama: string;
        atribut_tipe: string;
        nilai: string;
        lampiran_files: string[];
    }>;
    can?: {
        Verifikasi?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Verifikasi Pengajuan Surat RT', href: '/layanan-surat/pengajuan-surat-rt' },
    { title: 'Detail Pengajuan', href: `/layanan-surat/pengajuan-surat-rt/${props.item.id}` },
];

const fields = [
    { label: 'Jenis Surat', value: props.item.jenis_surat_nama || '-' },
    { label: 'Warga', value: props.item.resident_nama && props.item.resident_nik ? `${props.item.resident_nama} (${props.item.resident_nik})` : '-' },
    { label: 'Tanggal Surat', value: props.item.tanggal_surat ? new Date(props.item.tanggal_surat).toLocaleDateString('id-ID') : '-' },
    { label: 'Status', value: getStatusLabel(props.item.status) },
];

if (props.item.rt_verifikasi_at) {
    fields.push({ 
        label: 'Diverifikasi RT Pada', 
        value: props.item.rt_verifikasi_at ? new Date(props.item.rt_verifikasi_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' 
    });
}

if (props.item.rt_verifikasi) {
    fields.push({ label: 'Diverifikasi Oleh RT', value: props.item.rt_verifikasi.name });
}

if (props.item.rt_catatan) {
    fields.push({ label: 'Catatan RT', value: props.item.rt_catatan });
}

const actionFields = [
    { label: 'Created At', value: props.item.created_at ? new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
    { label: 'Updated At', value: props.item.updated_at ? new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
];

function getStatusLabel(status: string): string {
    const statusMap: Record<string, string> = {
        menunggu: 'Menunggu Verifikasi RT',
        diverifikasi_rt: 'Sudah Diverifikasi RT',
        disetujui: 'Disetujui',
        ditolak: 'Ditolak',
        diperbaiki: 'Diperbaiki',
    };
    return statusMap[status] || status.toUpperCase();
}

const handleVerifikasi = () => {
    if (props.item.status === 'menunggu' && props.can?.Verifikasi) {
        router.visit(`/layanan-surat/pengajuan-surat-rt/${props.item.id}/verifikasi`);
    }
};
</script>

<template>
    <PageShow
        title="Detail Pengajuan Surat RT"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/layanan-surat/pengajuan-surat-rt'"
    >
        <template #custom-action>
            <Button
                v-if="item.status === 'menunggu' && can?.Verifikasi"
                @click="handleVerifikasi"
            >
                Verifikasi
            </Button>
        </template>

        <template #additional-content>
            <!-- Atribut Detail -->
            <div v-if="atribut_detail && atribut_detail.length > 0" class="mt-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Data Atribut</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="atribut in atribut_detail"
                                :key="atribut.id"
                                class="p-4 border rounded-lg"
                            >
                                <div class="font-semibold mb-2">{{ atribut.atribut_nama }}</div>
                                <div class="text-sm text-muted-foreground mb-2">Tipe: {{ atribut.atribut_tipe }}</div>
                                <div class="mb-2">
                                    <strong>Nilai:</strong>
                                    <div class="mt-1">{{ atribut.nilai || '-' }}</div>
                                </div>
                                <div v-if="atribut.lampiran_files && atribut.lampiran_files.length > 0" class="mt-2">
                                    <strong>Lampiran:</strong>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        <a
                                            v-for="(file, index) in atribut.lampiran_files"
                                            :key="index"
                                            :href="file.path"
                                            target="_blank"
                                            class="text-sm text-blue-600 hover:underline"
                                        >
                                            {{ file.name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </PageShow>
</template>
