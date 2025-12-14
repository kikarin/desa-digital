<script setup lang="ts">
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/toast/useToast';
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    open: boolean;
    recipient: any;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
    (e: 'close'): void;
}>();

const { toast } = useToast();

const form = useForm({
    status: 'DATANG',
    tanggal_penyaluran: '',
    penerima_lapangan_id: null as number | null,
    catatan: '',
});

const familyResidents = ref<Array<{ id: number; nik: string; nama: string }>>([]);
const loadingResidents = ref(false);
const submitting = ref(false);

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

const showPerwakilan = computed(() => form.status === 'DATANG');
const showTanggal = computed(() => form.status === 'DATANG');

// Helper function untuk mendapatkan tanggal waktu Asia/Jakarta dalam format YYYY-MM-DD
const getJakartaDate = () => {
    const now = new Date();
    // Convert ke Asia/Jakarta timezone (UTC+7)
    const jakartaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
    const year = jakartaTime.getFullYear();
    const month = String(jakartaTime.getMonth() + 1).padStart(2, '0');
    const day = String(jakartaTime.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

watch(() => props.recipient, (newRecipient) => {
    if (newRecipient) {
        form.status = newRecipient.status === 'PROSES' ? 'DATANG' : newRecipient.status;
        // Auto-set tanggal penyaluran ke waktu Asia/Jakarta saat ini jika status DATANG
        if (form.status === 'DATANG') {
            form.tanggal_penyaluran = getJakartaDate();
        } else {
            form.tanggal_penyaluran = newRecipient.tanggal_penyaluran
                ? new Date(newRecipient.tanggal_penyaluran).toISOString().split('T')[0]
                : '';
        }
        form.penerima_lapangan_id = newRecipient.penerima_lapangan_id || null;
        form.catatan = newRecipient.catatan || '';

        // Load family residents jika status DATANG
        if (form.status === 'DATANG' && newRecipient.id) {
            loadFamilyResidents(newRecipient.id);
        }
    }
}, { immediate: true });

watch(() => form.status, (newStatus) => {
    if (newStatus === 'DATANG' && props.recipient?.id) {
        loadFamilyResidents(props.recipient.id);
        // Auto-set tanggal penyaluran ke waktu Asia/Jakarta saat ini
        form.tanggal_penyaluran = getJakartaDate();
    } else if (newStatus === 'TIDAK_DATANG') {
        form.penerima_lapangan_id = null;
        form.tanggal_penyaluran = '';
    }
});

const loadFamilyResidents = async (recipientId: number) => {
    loadingResidents.value = true;
    try {
        const response = await axios.get(`/api/assistance-recipients/${recipientId}/family-residents`);
        familyResidents.value = response.data.data || [];
    } catch (error: any) {
        console.error('Error loading family residents:', error);
        toast({
            title: 'Gagal memuat data perwakilan',
            variant: 'destructive',
        });
    } finally {
        loadingResidents.value = false;
    }
};

const handleSubmit = async () => {
    if (!props.recipient?.id) {
        return;
    }

    // Auto-set tanggal penyaluran jika status DATANG dan belum di-set
    if (form.status === 'DATANG' && !form.tanggal_penyaluran) {
        form.tanggal_penyaluran = getJakartaDate();
    }

    submitting.value = true;
    try {
        await axios.put(`/api/assistance-recipients/${props.recipient.id}/distribution`, {
            status: form.status,
            tanggal_penyaluran: form.status === 'DATANG' ? form.tanggal_penyaluran : null,
            penerima_lapangan_id: form.status === 'DATANG' ? form.penerima_lapangan_id : null,
            catatan: form.catatan,
        });

        toast({
            title: 'Status penyaluran berhasil diperbarui',
            variant: 'success',
        });

        emit('success');
    } catch (error: any) {
        console.error('Error updating distribution:', error);
        toast({
            title: error.response?.data?.message || 'Gagal memperbarui status penyaluran',
            variant: 'destructive',
        });
    } finally {
        submitting.value = false;
    }
};

const handleClose = () => {
    form.reset();
    familyResidents.value = [];
    emit('close');
};
</script>

<template>
    <Dialog v-model:open="isOpen" @update:open="(val: boolean) => !val && handleClose()">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Form Penyaluran</DialogTitle>
            </DialogHeader>

            <div v-if="recipient" class="space-y-4 py-4">
                <!-- Info Penerima -->
                <div class="p-4 bg-muted/50 rounded-lg space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">Penerima</p>
                    <p class="font-semibold">
                        {{ recipient.target_type === 'KELUARGA' ? (recipient.family?.no_kk || '-') : (recipient.resident?.nama || '-') }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ recipient.target_type === 'KELUARGA' ? 'KELUARGA' : 'INDIVIDU' }}
                    </p>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">Status <span class="text-destructive">*</span></Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="status">
                            <SelectValue placeholder="Pilih Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="DATANG">DATANG</SelectItem>
                            <SelectItem value="TIDAK_DATANG">TIDAK DATANG</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Tanggal Penyaluran (Auto-set, readonly) -->
                <div v-if="showTanggal" class="space-y-2">
                    <Label for="tanggal_penyaluran">Tanggal Penyaluran</Label>
                    <Input
                        id="tanggal_penyaluran"
                        :model-value="form.tanggal_penyaluran"
                        type="text"
                        readonly
                        class="bg-muted cursor-not-allowed"
                    />
                    <p class="text-xs text-muted-foreground">
                        Tanggal otomatis di-set ke waktu Asia/Jakarta saat ini
                    </p>
                </div>

                <!-- Perwakilan -->
                <div v-if="showPerwakilan" class="space-y-2">
                    <Label for="penerima_lapangan_id">Perwakilan</Label>
                    <Select
                        v-model="form.penerima_lapangan_id"
                        :disabled="loadingResidents"
                    >
                        <SelectTrigger id="penerima_lapangan_id">
                            <SelectValue placeholder="Pilih Perwakilan (Opsional)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">Tidak Ada Perwakilan</SelectItem>
                            <SelectItem
                                v-for="resident in familyResidents"
                                :key="resident.id"
                                :value="resident.id"
                            >
                                {{ resident.nik }} - {{ resident.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Pilih anggota keluarga yang sama sebagai perwakilan penerima bantuan
                    </p>
                </div>

                <!-- Catatan -->
                <div class="space-y-2">
                    <Label for="catatan">Catatan</Label>
                    <textarea
                        id="catatan"
                        v-model="form.catatan"
                        placeholder="Catatan tambahan (opsional)"
                        rows="3"
                        class="border-input bg-background text-foreground placeholder:text-muted-foreground focus-visible:ring-ring min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleClose" :disabled="submitting">Batal</Button>
                <Button @click="handleSubmit" :disabled="submitting">
                    {{ submitting ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

