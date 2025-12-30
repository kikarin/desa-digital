<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const isDisetujui = computed(() => {
    const status = props.item?.status;
    if (!status) return false;
    return status === 'disetujui' || String(status).toLowerCase() === 'disetujui';
});

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        jenis_surat_id: number;
        jenis_surat_nama: string;
        jenis_surat_kode: string;
        resident_id: number;
        resident_nama: string;
        resident_nik: string;
        tanggal_surat: string;
        status: string;
        nomor_surat: string | null;
        tanggal_disetujui: string | null;
        alasan_penolakan: string | null;
        admin_verifikasi_id: number | null;
        tanda_tangan_digital: string | null;
        foto_tanda_tangan: string | null;
        tanda_tangan_type: string | null;
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
        updated_at: string;
        updated_by_user: {
            id: number;
            name: string;
        } | null;
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
        'Export PDF'?: boolean;
        'Preview PDF'?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Pengajuan Surat', href: '/layanan-surat/pengajuan-surat' },
    { title: 'Detail Pengajuan', href: `/layanan-surat/pengajuan-surat/${props.item.id}` },
];

const fields = [
    { label: 'Jenis Surat', value: props.item.jenis_surat_nama || '-' },
    { label: 'Warga', value: props.item.resident_nama && props.item.resident_nik ? `${props.item.resident_nama} (${props.item.resident_nik})` : '-' },
    { label: 'Tanggal Surat', value: props.item.tanggal_surat ? new Date(props.item.tanggal_surat).toLocaleDateString('id-ID') : '-' },
    { label: 'Status', value: props.item.status ? props.item.status.toUpperCase() : '-' },
];

if (props.item.nomor_surat) {
    fields.push({ label: 'Nomor Surat', value: props.item.nomor_surat });
}

if (props.item.tanggal_disetujui) {
    fields.push({ label: 'Tanggal Disetujui', value: new Date(props.item.tanggal_disetujui).toLocaleDateString('id-ID') });
}

if (props.item.alasan_penolakan) {
    fields.push({ label: 'Alasan Penolakan', value: props.item.alasan_penolakan });
}

const actionFields = [
    { label: 'Created At', value: props.item.created_at ? new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: props.item.updated_at ? new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleVerifikasi = () => {
    router.visit(`/layanan-surat/pengajuan-surat/${props.item.id}/verifikasi`);
};

const handlePreviewPdf = () => {
    window.open(`/layanan-surat/pengajuan-surat/${props.item.id}/preview-pdf`, '_blank');
};

const handleExportPdf = () => {
    window.location.href = `/layanan-surat/pengajuan-surat/${props.item.id}/export-pdf`;
};
</script>

<template>
    <PageShow
        title="Pengajuan Surat"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/layanan-surat/pengajuan-surat'"
    >
        <template #custom-action>
            <Button
                v-if="isDisetujui"
                @click="handlePreviewPdf"
                variant="secondary"
            >
                Preview PDF
            </Button>
            <Button
                v-if="isDisetujui"
                @click="handleExportPdf"
                variant="secondary"
            >
                Export PDF
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
                                    <div class="mt-1 space-y-1">
                                        <a
                                            v-for="(file, index) in atribut.lampiran_files"
                                            :key="index"
                                            :href="`/storage/${file}`"
                                            target="_blank"
                                            class="block text-primary hover:underline"
                                        >
                                            File {{ index + 1 }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tanda Tangan -->
            <div v-if="item.status === 'disetujui' && (item.tanda_tangan_digital || item.foto_tanda_tangan)" class="mt-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Tanda Tangan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="item.tanda_tangan_type === 'digital' && item.tanda_tangan_digital">
                            <img :src="item.tanda_tangan_digital" alt="Tanda Tangan Digital" class="max-w-xs border rounded" />
                        </div>
                        <div v-else-if="item.tanda_tangan_type === 'foto' && item.foto_tanda_tangan">
                            <img :src="`/storage/${item.foto_tanda_tangan}`" alt="Foto Tanda Tangan" class="max-w-xs border rounded" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex gap-2 flex-wrap">
                <Button
                    v-if="(item.status === 'menunggu' || item.status === 'diperbaiki') && can?.Verifikasi"
                    @click="handleVerifikasi"
                >
                    Verifikasi
                </Button>
            </div>
        </template>
    </PageShow>
</template>

