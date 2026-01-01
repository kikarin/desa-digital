<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { getMarkerColorByJenisRumah, getMarkerColorByModule, createColoredMarkerIcon } from '@/utils/markerColors';
import { getBoundaryColor } from '@/utils/boundaryColors';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Package, Calendar, User, FileText } from 'lucide-vue-next';
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.css';

/* Fix default marker */
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const props = defineProps<{
    height?: string;
    filterParams?: { rw_id?: number; rt_id?: number };
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let markersLayer: L.LayerGroup | null = null; // Use LayerGroup untuk manage semua markers
const boundaryLayers = ref<L.Polygon[]>([]);
const boundaryLabels = ref<L.Marker[]>([]);
let isLoadingMarkers = false; // Flag untuk prevent concurrent loads

// Modal state
const isModalOpen = ref(false);
const selectedItem = ref<any>(null);
const selectedModule = ref<string>(''); // 'houses', 'bank-sampah', 'aduan-masyarakat', 'layanan-darurat', 'pengajuan-proposal'
const houseResidents = ref<any[]>([]);
const houseAssistancePrograms = ref<Record<number, any[]>>({});
const isLoadingDetail = ref(false);
let lightboxInstance: any = null;

// Batas wilayah Desa Galuga
const outerMask: [number, number][] = [
    [-7.5, 105.5],
    [-7.5, 108.5],
    [-5.5, 108.5],
    [-5.5, 105.5],
];

const galugaBoundary: [number, number][] = [
    // Garis 1
    [-6.5577674, 106.6478618],
    [-6.5578207, 106.6478725],
    [-6.557874, 106.6479718],
    [-6.558335, 106.6479637],
    [-6.5584309, 106.6480549],
    [-6.5593262, 106.6480254],
    [-6.5595181, 106.648071],
    [-6.5597419, 106.6482829],
    [-6.5598911, 106.6484304],
    [-6.560107, 106.6484546],
    [-6.5601602, 106.6484412],
    [-6.5602935, 106.6483178],
    [-6.5603468, 106.648291],
    [-6.5604027, 106.648299],
    [-6.560448, 106.6483205],
    [-6.560496, 106.6483527],
    [-6.5605466, 106.6484653],
    [-6.5605733, 106.6484868],
    [-6.5606745, 106.6484921],
    [-6.5607731, 106.6484492],
    [-6.560917, 106.648189],
    [-6.5610076, 106.6481461],
    [-6.5615405, 106.6483178],
    [-6.5617617, 106.648181],
    [-6.5621907, 106.6485297],
    [-6.5623453, 106.648527],
    [-6.5626544, 106.6484224],
    [-6.5627663, 106.6484519],
    [-6.5629661, 106.6485055],
    [-6.5630674, 106.6485994],
    [-6.5631953, 106.6488274],
    [-6.5632672, 106.6489159],
    [-6.5633445, 106.6489454],
    [-6.5634351, 106.6489749],
    [-6.5635124, 106.6489857],
    [-6.563571, 106.648983],
    [-6.5636323, 106.6489454],
    [-6.5636722, 106.6489454],
    [-6.5638028, 106.6488757],
    [-6.5638987, 106.648763],
    [-6.5639494, 106.648704],
    [-6.5640027, 106.6486772],
    [-6.5640426, 106.6486853],
    [-6.5640773, 106.6486692],
    [-6.5644263, 106.6487684],
    [-6.5650845, 106.648806],
    [-6.5651313, 106.6488846],
    [-6.5651473, 106.649008],
    [-6.5651126, 106.6491749],
    [-6.5651286, 106.6492936],
    [-6.5651852, 106.6493875],
    [-6.5653471, 106.6495357],
    [-6.5654563, 106.6495712],
    [-6.5656196, 106.649586],
    [-6.5657428, 106.6497208],
    [-6.5658908, 106.6498148],
    [-6.565992, 106.6498256],
    [-6.5662265, 106.6497853],
    [-6.5664077, 106.6497183],
    [-6.5664716, 106.6497209],
    [-6.5666129, 106.6498336],
    [-6.5668314, 106.6500562],
    [-6.5669513, 106.6501689],
    [-6.5670712, 106.6502386],
    [-6.5671405, 106.6502708],
    [-6.5675108, 106.6504076],
    [-6.5676121, 106.6504666],
    [-6.5677027, 106.6505176],
    [-6.56774, 106.6505497],
    [-6.5678759, 106.6505417],
    [-6.5683395, 106.6503271],
    [-6.5683875, 106.6500482],
    [-6.568598, 106.64978],
    [-6.5685714, 106.6495895],
    [-6.568622, 106.6495037],
    [-6.5686993, 106.6494635],
    [-6.5689737, 106.6490289],
    [-6.5697504, 106.6489498],
    [-6.569873, 106.6487218],
    [-6.5704779, 106.6486802],
    [-6.5708669, 106.6486159],
    [-6.5717622, 106.6488787],
    [-6.5718288, 106.6487205],
    [-6.5720766, 106.6486427],
    [-6.572431, 106.6482565],
    [-6.5734249, 106.6471621],
    [-6.5758595, 106.6451643],
    [-6.5757056, 106.6445662],
    [-6.575675, 106.6423614],
    [-6.575639, 106.6422005],
    [-6.5753179, 106.6412966],
    [-6.5750888, 106.6408473],
    [-6.5750422, 106.6404959],
    [-6.5749942, 106.6400466],
    [-6.5745812, 106.6394056],
    [-6.5738498, 106.639985],
    [-6.5734767, 106.6400708],
    [-6.5728719, 106.6400761],
    [-6.5724855, 106.6398535],
    [-6.5718593, 106.6387914],
    [-6.571028, 106.638271],
    [-6.5698236, 106.6365893],
    [-6.5690748, 106.6360984],
    [-6.5687471, 106.636077],
    [-6.5682674, 106.6361172],
    [-6.5680503, 106.6366215],
    [-6.5681875, 106.6384085],
    [-6.5681216, 106.6394093],
    [-6.5670494, 106.6397487],
    [-6.5661531, 106.6398414],
    // Garis 2
    [-6.5645428, 106.6392851],
    [-6.5644575, 106.6388988],
    [-6.56644, 106.6370106],
    [-6.5634556, 106.6340065],
    [-6.5629653, 106.6335988],
    [-6.5623258, 106.6331267],
    [-6.5615158, 106.6327405],
    [-6.5606418, 106.6325688],
    [-6.5600449, 106.6327619],
    [-6.5595333, 106.6330194],
    [-6.5589364, 106.6329551],
    [-6.5579345, 106.6330194],
    [-6.5568686, 106.6329765],
    [-6.5561012, 106.6326547],
    [-6.5549927, 106.6334271],
    [-6.5549074, 106.6339207],
    [-6.5556109, 106.6350579],
    [-6.5563357, 106.6354227],
    [-6.5573163, 106.6355514],
    [-6.5579132, 106.6358948],
    [-6.5584035, 106.6360235],
    [-6.5600236, 106.6359162],
    [-6.5605991, 106.6360664],
    [-6.5609615, 106.6364312],
    [-6.5610042, 106.637311],
    [-6.560322, 106.6379762],
    [-6.5593201, 106.6381693],
    [-6.558979, 106.6380834],
    [-6.5586806, 106.638577],
    [-6.558574, 106.6395855],
    // Garis 3
    [-6.5595497, 106.6402292],
    [-6.5597629, 106.6407871],
    [-6.5594857, 106.64186],
    [-6.5585904, 106.643877],
    [-6.5581854, 106.6447568],
    [-6.5576231, 106.6477555],
    // Penutup (kembali ke titik awal)
    [-6.5577674, 106.6478618],
];

// Helper functions
const getJenisRumahLabel = (jenis: string): string => {
    const labels: Record<string, string> = {
        'RUMAH_TINGGAL': 'Rumah Tinggal',
        'KONTRAKAN': 'Kontrakan',
        'WARUNG_TOKO_USAHA': 'Warung / Toko / Usaha',
        'FASILITAS_UMUM': 'Fasilitas Umum',
    };
    return labels[jenis] || jenis;
};

const getHouseFields = (house: any) => {
    const rtValue = house.rt?.rw 
        ? `${house.rt.nomor_rt} - RW ${house.rt.rw.nomor_rw} - ${house.rt.rw.desa}, ${house.rt.rw.kecamatan}, ${house.rt.rw.kabupaten}`
        : house.rt?.nomor_rt || '-';
    
    const baseFields = [
        { label: 'Jenis Rumah', value: getJenisRumahLabel(house.jenis_rumah) },
        { label: 'RT', value: rtValue },
    ];

    if (house.jenis_rumah === 'RUMAH_TINGGAL') {
        baseFields.push(
            { label: 'Nomor Rumah', value: house.nomor_rumah },
            { 
                label: 'Pemilik', 
                value: house.pemilik 
                    ? `${house.pemilik.nik} - ${house.pemilik.nama}` 
                    : (house.nama_pemilik || '-')
            },
            { label: 'Keterangan', value: house.keterangan || '-' }
        );
    }

    if (house.jenis_rumah === 'KONTRAKAN') {
        baseFields.push(
            { label: 'Nomor Bangunan', value: house.nomor_rumah },
            { label: 'Nama Pemilik', value: house.nama_pemilik || '-' },
            { label: 'Status Hunian', value: house.status_hunian === 'DIHUNI' ? 'Dihuni' : house.status_hunian === 'KOSONG' ? 'Kosong' : '-' },
            { label: 'Keterangan', value: house.keterangan || '-' }
        );
    }

    if (house.jenis_rumah === 'WARUNG_TOKO_USAHA') {
        baseFields.push(
            { label: 'Nomor Bangunan', value: house.nomor_rumah },
            { label: 'Nama Usaha', value: house.nama_usaha || '-' },
            { label: 'Nama Pemilik / Pengelola', value: house.nama_pengelola || '-' },
            { label: 'Jenis Usaha', value: house.jenis_usaha || '-' },
            { label: 'Keterangan', value: house.keterangan || '-' }
        );
    }

    if (house.jenis_rumah === 'FASILITAS_UMUM') {
        baseFields.push(
            { label: 'Nomor Bangunan', value: house.nomor_rumah || '-' },
            { label: 'Nama Fasilitas', value: house.nama_fasilitas || '-' },
            { label: 'Pengelola', value: house.pengelola || '-' },
            { label: 'Keterangan', value: house.keterangan || '-' }
        );
    }

    return baseFields;
};

const calculateAge = (tanggalLahir: string): number => {
    if (!tanggalLahir) return 0;
    const birthDate = new Date(tanggalLahir);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const getJenisKelaminLabel = (jenisKelamin: string): string => {
    return jenisKelamin === 'L' ? 'Laki-laki' : jenisKelamin === 'P' ? 'Perempuan' : '-';
};

const formatDate = (dateString: string): string => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const getStatusBadgeClass = (status: string): string => {
    if (status === 'SELESAI' || status === 'DATANG') {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } else if (status === 'TIDAK_DATANG') {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } else {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    }
};

const getStatusLabel = (status: string): string => {
    if (status === 'TIDAK_DATANG') {
        return 'TIDAK DATANG';
    }
    return status;
};

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
};

const isCashItem = (tipe: string): boolean => {
    return tipe === 'UANG' || tipe === 'UANG_TUNAI' || tipe?.toUpperCase().includes('UANG');
};

// Helper functions untuk berbagai module
const getModalTitle = (module: string): string => {
    const titles: Record<string, string> = {
        'houses': 'Detail Rumah',
        'bank-sampah': 'Detail Bank Sampah',
        'aduan-masyarakat': 'Detail Aduan Masyarakat',
        'layanan-darurat': 'Detail Layanan Darurat',
        'pengajuan-proposal': 'Detail Pengajuan Proposal',
    };
    return titles[module] || 'Detail';
};

const getItemFields = (module: string, item: any) => {
    if (module === 'houses') {
        return getHouseFields(item);
    } else if (module === 'bank-sampah') {
        return [
            { label: 'Nama Lokasi', value: item.nama_lokasi || '-' },
            { label: 'Title', value: item.title || '-' },
            { label: 'Alamat', value: item.alamat || '-' },
            { label: 'Deskripsi', value: item.deskripsi || '-' },
        ];
    } else if (module === 'aduan-masyarakat') {
        return [
            { label: 'Judul', value: item.judul || '-' },
            { label: 'Kategori Aduan', value: item.kategori_aduan_nama || '-' },
            { label: 'Nama Lokasi', value: item.nama_lokasi || '-' },
            { label: 'Kecamatan', value: item.kecamatan_nama || '-' },
            { label: 'Desa', value: item.desa_nama || '-' },
            { label: 'Deskripsi Lokasi', value: item.deskripsi_lokasi || '-' },
            { label: 'Jenis Aduan', value: item.jenis_aduan || '-' },
            { label: 'Status', value: item.status || '-' },
            { label: 'Detail Aduan', value: item.detail_aduan || '-' },
            { label: 'Alasan Melaporkan', value: item.alasan_melaporkan || '-' },
        ];
    } else if (module === 'layanan-darurat') {
        return [
            { label: 'Kategori', value: item.kategori_label || item.kategori || '-' },
            { label: 'Title', value: item.title || '-' },
            { label: 'Alamat', value: item.alamat || '-' },
            { label: 'Nomor WhatsApp', value: item.nomor_whatsapp || '-' },
        ];
    } else if (module === 'pengajuan-proposal') {
        return [
            { label: 'Nama Kegiatan', value: item.nama_kegiatan || '-' },
            { label: 'Kategori Proposal', value: item.kategori_proposal_nama || '-' },
            { label: 'Nama Pengaju', value: item.resident_nama || '-' },
            { label: 'NIK Pengaju', value: item.resident_nik || '-' },
            { label: 'Usulan Anggaran', value: item.usulan_anggaran_formatted || '-' },
            { label: 'Status', value: item.status_label || item.status || '-' },
            { label: 'Kecamatan', value: item.kecamatan || '-' },
            { label: 'Kelurahan/Desa', value: item.kelurahan_desa || '-' },
            { label: 'Deskripsi Lokasi Tambahan', value: item.deskripsi_lokasi_tambahan || '-' },
            { label: 'Deskripsi Kegiatan', value: item.deskripsi_kegiatan || '-' },
        ];
    }
    return [];
};

// Computed untuk mendapatkan semua program bantuan dari semua keluarga
const allAssistancePrograms = computed(() => {
    if (!selectedItem.value?.families || selectedModule.value !== 'houses') return [];
    
    const allPrograms: any[] = [];
    selectedItem.value.families.forEach((family: any) => {
        const programs = houseAssistancePrograms.value[family.id] || [];
        programs.forEach((program: any) => {
            allPrograms.push({
                ...program,
                family_no_kk: family.no_kk,
                family_id: family.id,
            });
        });
    });
    
    return allPrograms;
});

// Load detail item saat marker diklik
const loadItemDetail = async (module: string, id: number) => {
    isLoadingDetail.value = true;
    selectedModule.value = module;
    try {
        let response;
        switch (module) {
            case 'houses':
                response = await axios.get(`/api/houses/${id}`);
                if (response.data?.item) {
                    selectedItem.value = response.data.item;
                    houseResidents.value = response.data.residents || [];
                    
                    // Load assistance programs untuk setiap keluarga
                    if (selectedItem.value.families && Array.isArray(selectedItem.value.families)) {
                        const programsMap: Record<number, any[]> = {};
                        
                        for (const family of selectedItem.value.families) {
                            try {
                                const familyResponse = await axios.get(`/api/families/${family.id}`);
                                if (familyResponse.data?.assistancePrograms) {
                                    programsMap[family.id] = familyResponse.data.assistancePrograms;
                                }
                            } catch (error) {
                                console.error(`Error loading programs for family ${family.id}:`, error);
                            }
                        }
                        
                        houseAssistancePrograms.value = programsMap;
                    }
                    
                    // Initialize GLightbox setelah foto ter-render
                    if (selectedItem.value.fotos && selectedItem.value.fotos.length > 0) {
                        setTimeout(() => {
                            if (lightboxInstance) {
                                lightboxInstance.destroy();
                            }
                            lightboxInstance = GLightbox({
                                selector: '.glightbox-trigger',
                                touchNavigation: true,
                                loop: true,
                                autoplayVideos: false,
                            });
                        }, 100);
                    }
                }
                break;
            case 'bank-sampah':
                response = await axios.get(`/api/bank-sampah/${id}`);
                if (response.data?.item) {
                    selectedItem.value = response.data.item;
                    // Initialize GLightbox untuk foto
                    if (selectedItem.value.foto) {
                        setTimeout(() => {
                            if (lightboxInstance) {
                                lightboxInstance.destroy();
                            }
                            lightboxInstance = GLightbox({
                                selector: '.glightbox-trigger',
                                touchNavigation: true,
                                loop: true,
                                autoplayVideos: false,
                            });
                        }, 100);
                    }
                }
                break;
            case 'aduan-masyarakat':
                response = await axios.get(`/api/aduan-masyarakat/${id}`);
                if (response.data?.item) {
                    selectedItem.value = response.data.item;
                    // Initialize GLightbox untuk files
                    if (selectedItem.value.files && selectedItem.value.files.length > 0) {
                        setTimeout(() => {
                            if (lightboxInstance) {
                                lightboxInstance.destroy();
                            }
                            lightboxInstance = GLightbox({
                                selector: '.glightbox-trigger',
                                touchNavigation: true,
                                loop: true,
                                autoplayVideos: false,
                            });
                        }, 100);
                    }
                }
                break;
            case 'layanan-darurat':
                response = await axios.get(`/api/layanan-darurat/${id}`);
                if (response.data?.item) {
                    selectedItem.value = response.data.item;
                }
                break;
            case 'pengajuan-proposal':
                response = await axios.get(`/api/pengajuan-proposal/${id}`);
                if (response.data?.item) {
                    selectedItem.value = response.data.item;
                    // Initialize GLightbox untuk thumbnail_foto_banner
                    if (selectedItem.value.thumbnail_foto_banner) {
                        setTimeout(() => {
                            if (lightboxInstance) {
                                lightboxInstance.destroy();
                            }
                            lightboxInstance = GLightbox({
                                selector: '.glightbox-trigger',
                                touchNavigation: true,
                                loop: true,
                                autoplayVideos: false,
                            });
                        }, 100);
                    }
                }
                break;
        }
    } catch (error) {
        console.error(`Error loading ${module} detail:`, error);
    } finally {
        isLoadingDetail.value = false;
    }
};

// Handle marker click
const handleMarkerClick = (module: string, item: any) => {
    loadItemDetail(module, item.id);
    isModalOpen.value = true;
};

// Load semua markers dari semua module
const loadAllMarkers = async () => {
    // Prevent concurrent calls
    if (isLoadingMarkers || !map) return;
    isLoadingMarkers = true;
    
    // Clear existing markers menggunakan LayerGroup
    if (markersLayer) {
        markersLayer.clearLayers();
        map.removeLayer(markersLayer);
    }
    
    // Buat LayerGroup baru untuk markers
    markersLayer = L.layerGroup().addTo(map);
    
    // Build params dengan filter
    const params: Record<string, any> = { per_page: -1 };
    if (props.filterParams?.rw_id) {
        params.filter_rw_id = props.filterParams.rw_id;
    }
    if (props.filterParams?.rt_id) {
        params.filter_rt_id = props.filterParams.rt_id;
    }
    
    // Load Houses
    try {
        const housesResponse = await axios.get('/api/houses', { params });
        if (housesResponse.data?.data && Array.isArray(housesResponse.data.data)) {
            housesResponse.data.data
                .filter((house: any) => house.latitude && house.longitude)
                .forEach((house: any) => {
                    const lat = parseFloat(house.latitude);
                    const lng = parseFloat(house.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const color = getMarkerColorByJenisRumah(house.jenis_rumah);
                        const markerIcon = createColoredMarkerIcon(color);
                        const marker = L.marker([lat, lng], { icon: markerIcon });
                        marker.bindTooltip(`<b>Rumah: ${getJenisRumahLabel(house.jenis_rumah)}</b><br/>Nomor: ${house.nomor_rumah || '-'}`, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -10],
                        });
                        marker.on('click', () => handleMarkerClick('houses', house));
                        markersLayer!.addLayer(marker);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading houses markers:', error);
    }

    // Load Bank Sampah
    try {
        const bankSampahResponse = await axios.get('/api/bank-sampah', {
            params: { per_page: -1 },
        });
        if (bankSampahResponse.data?.data && Array.isArray(bankSampahResponse.data.data)) {
            const color = getMarkerColorByModule('bank-sampah');
            const markerIcon = createColoredMarkerIcon(color);
            bankSampahResponse.data.data
                .filter((item: any) => item.latitude && item.longitude)
                .forEach((item: any) => {
                    const lat = parseFloat(item.latitude);
                    const lng = parseFloat(item.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const marker = L.marker([lat, lng], { icon: markerIcon });
                        marker.bindTooltip(`<b>Bank Sampah</b><br/>${item.nama_lokasi || item.title || '-'}`, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -10],
                        });
                        marker.on('click', () => handleMarkerClick('bank-sampah', item));
                        markersLayer!.addLayer(marker);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading bank-sampah markers:', error);
    }

    // Load Aduan Masyarakat
    try {
        const aduanResponse = await axios.get('/api/aduan-masyarakat', {
            params: { per_page: -1 },
        });
        if (aduanResponse.data?.data && Array.isArray(aduanResponse.data.data)) {
            const color = getMarkerColorByModule('aduan-masyarakat');
            const markerIcon = createColoredMarkerIcon(color);
            aduanResponse.data.data
                .filter((item: any) => item.latitude && item.longitude)
                .forEach((item: any) => {
                    const lat = parseFloat(item.latitude);
                    const lng = parseFloat(item.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const marker = L.marker([lat, lng], { icon: markerIcon });
                        marker.bindTooltip(`<b>Aduan Masyarakat</b><br/>${item.judul || '-'}`, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -10],
                        });
                        marker.on('click', () => handleMarkerClick('aduan-masyarakat', item));
                        markersLayer!.addLayer(marker);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading aduan-masyarakat markers:', error);
    }

    // Load Layanan Darurat
    try {
        const layananResponse = await axios.get('/api/layanan-darurat', {
            params: { per_page: -1 },
        });
        if (layananResponse.data?.data && Array.isArray(layananResponse.data.data)) {
            const color = getMarkerColorByModule('layanan-darurat');
            const markerIcon = createColoredMarkerIcon(color);
            layananResponse.data.data
                .filter((item: any) => item.latitude && item.longitude)
                .forEach((item: any) => {
                    const lat = parseFloat(item.latitude);
                    const lng = parseFloat(item.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const marker = L.marker([lat, lng], { icon: markerIcon });
                        marker.bindTooltip(`<b>Layanan Darurat: ${item.kategori_label || item.kategori || '-'}</b><br/>${item.title || '-'}`, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -10],
                        });
                        marker.on('click', () => handleMarkerClick('layanan-darurat', item));
                        markersLayer!.addLayer(marker);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading layanan-darurat markers:', error);
    }

    // Load Pengajuan Proposal
    try {
        const proposalResponse = await axios.get('/api/pengajuan-proposal', {
            params: { per_page: -1 },
        });
        if (proposalResponse.data?.data && Array.isArray(proposalResponse.data.data)) {
            const color = getMarkerColorByModule('pengajuan-proposal');
            const markerIcon = createColoredMarkerIcon(color);
            proposalResponse.data.data
                .filter((item: any) => item.latitude && item.longitude)
                .forEach((item: any) => {
                    const lat = parseFloat(item.latitude);
                    const lng = parseFloat(item.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const marker = L.marker([lat, lng], { icon: markerIcon });
                        marker.bindTooltip(`<b>Pengajuan Proposal</b><br/>${item.nama_kegiatan || '-'}`, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -10],
                        });
                        marker.on('click', () => handleMarkerClick('pengajuan-proposal', item));
                        markersLayer!.addLayer(marker);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading pengajuan-proposal markers:', error);
    } finally {
        isLoadingMarkers = false;
    }
};

/**
 * Render boundary polygon dengan label di tengah
 */
const renderBoundary = (
    coordinates: number[][], 
    label: string, 
    color: string,
): { polygon: L.Polygon; label: L.Marker } | null => {
    if (!map) return null;
    
    // Convert coordinates ke format Leaflet [lat, lng][]
    const latlngs = coordinates.map(coord => [coord[0], coord[1]] as [number, number]);
    
    // Buat polygon dengan warna border dan fill transparan
    const polygon = L.polygon(latlngs, {
        color: color,
        weight: 3,
        fillColor: color,
        fillOpacity: 0.2, // Transparan
        opacity: 1,
    }).addTo(map);
    
    // Hitung center point untuk label
    const bounds = polygon.getBounds();
    const center = bounds.getCenter();
    
    // Buat label menggunakan DivIcon
    const labelIcon = L.divIcon({
        className: 'boundary-label',
        html: `
            <div style="
                background-color: rgba(255, 255, 255, 0.9);
                border: 2px solid ${color};
                border-radius: 8px;
                padding: 1px 20px;
                font-weight: bold;
                font-size: 6px;
                color: ${color};
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                white-space: capitalize;
                text-align: left;
            ">
                ${label}
            </div>
        `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
    });
    
    const labelMarker = L.marker(center, { icon: labelIcon, interactive: false })
        .addTo(map);
    
    return { polygon, label: labelMarker };
};

/**
 * Load dan render RW boundaries
 */
const loadRwBoundaries = async () => {
    try {
        const response = await axios.get('/api/rws', {
            params: { per_page: -1 },
        });
        
        if (response.data?.data && Array.isArray(response.data.data)) {
            response.data.data
                .filter((rw: any) => rw.boundary && Array.isArray(rw.boundary) && rw.boundary.length > 0)
                .forEach((rw: any) => {
                    const color = getBoundaryColor(rw.id, 'rw');
                    const label = `RW${rw.nomor_rw}`;
                    const result = renderBoundary(
                        rw.boundary,
                        label,
                        color,
                    );
                    
                    if (result) {
                        boundaryLayers.value.push(result.polygon);
                        boundaryLabels.value.push(result.label);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading RW boundaries:', error);
    }
};

/**
 * Load dan render RT boundaries
 */
const loadRtBoundaries = async () => {
    try {
        const response = await axios.get('/api/rts', {
            params: { per_page: -1 },
        });
        
        if (response.data?.data && Array.isArray(response.data.data)) {
            response.data.data
                .filter((rt: any) => rt.boundary && Array.isArray(rt.boundary) && rt.boundary.length > 0)
                .forEach((rt: any) => {
                    const color = getBoundaryColor(rt.id, 'rt');
                    const label = `RT${rt.nomor_rt}`;
                    const result = renderBoundary(
                        rt.boundary,
                        label,
                        color,
                    );
                    
                    if (result) {
                        boundaryLayers.value.push(result.polygon);
                        boundaryLabels.value.push(result.label);
                    }
                });
        }
    } catch (error) {
        console.error('Error loading RT boundaries:', error);
    }
};

onMounted(async () => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value, {
        zoomControl: true,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Mask polygon
    L.polygon(
        [outerMask, galugaBoundary],
        {
            stroke: false,
            fillColor: '#000000',
            fillOpacity: 0.6,
        }
    ).addTo(map);

    // Garis batas Galuga
    L.polygon(galugaBoundary, {
        color: '#dc2626',
        weight: 3,
        fillOpacity: 0,
    }).addTo(map);

    // Fit ke Galuga
    map.fitBounds(L.latLngBounds(galugaBoundary), {
        padding: [20, 20],
    });

    await loadAllMarkers();
    
    // Load boundaries RW dan RT
    await loadRwBoundaries();
    await loadRtBoundaries();
});

// Watch filter changes dan reload markers
watch(
    () => [props.filterParams?.rw_id, props.filterParams?.rt_id],
    () => {
        if (map && !isLoadingMarkers) {
            loadAllMarkers();
        }
    },
    { deep: true }
);

onUnmounted(() => {
    // Cleanup markers menggunakan LayerGroup
    if (markersLayer && map) {
        markersLayer.clearLayers();
        map.removeLayer(markersLayer);
        markersLayer = null;
    }
    
    // Cleanup boundary layers
    boundaryLayers.value.forEach(layer => {
        if (map && layer) {
            // @ts-ignore - Leaflet layer types
            map.removeLayer(layer);
        }
    });
    
    boundaryLabels.value.forEach(label => {
        if (map && label) {
            // @ts-ignore - Leaflet layer types
            map.removeLayer(label);
        }
    });
    
    boundaryLayers.value = [];
    boundaryLabels.value = [];

    // Cleanup GLightbox
    if (lightboxInstance) {
        lightboxInstance.destroy();
        lightboxInstance = null;
    }

    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div
        ref="mapContainer"
        :style="{ height: height || '600px' }"
        class="relative z-0 w-full rounded-lg border border-border shadow-lg"
    ></div>

    <!-- Modal Detail -->
    <Dialog v-model:open="isModalOpen">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto welcome-map-modal">
            <DialogHeader>
                <DialogTitle>{{ getModalTitle(selectedModule) }}</DialogTitle>
            </DialogHeader>

            <div v-if="isLoadingDetail" class="py-8 text-center">
                <p class="text-muted-foreground">Memuat data...</p>
            </div>

            <div v-else-if="selectedItem" class="space-y-6">
                <!-- Fields Information -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="field in getItemFields(selectedModule, selectedItem)"
                                :key="field.label"
                                class="space-y-1"
                            >
                                <p class="text-sm font-medium text-muted-foreground">{{ field.label }}</p>
                                <p class="text-sm">{{ field.value }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Gallery Foto/Images -->
                <!-- Houses: Multiple fotos -->
                <div v-if="selectedModule === 'houses'">
                    <h3 class="text-lg font-semibold mb-4">Foto</h3>
                    <div v-if="selectedItem.fotos && Array.isArray(selectedItem.fotos) && selectedItem.fotos.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a
                            v-for="(foto, index) in selectedItem.fotos"
                            :key="foto.id || index"
                            :href="foto.url"
                            class="glightbox-trigger relative group cursor-pointer"
                            :data-gallery="`gallery-${selectedItem.id}`"
                        >
                            <img
                                :src="foto.url"
                                :alt="foto.name || 'Foto'"
                                class="w-full h-48 object-cover rounded-lg border hover:opacity-90 transition-opacity"
                                loading="lazy"
                                @error="(e: Event) => {
                                    const img = e.target as HTMLImageElement;
                                    img.style.display = 'none';
                                }"
                            />
                        </a>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground">
                        <p>Tidak ada foto</p>
                    </div>
                </div>

                <!-- Bank Sampah: Single foto -->
                <div v-if="selectedModule === 'bank-sampah' && selectedItem.foto">
                    <h3 class="text-lg font-semibold mb-4">Foto</h3>
                    <a
                        :href="selectedItem.foto"
                        class="glightbox-trigger inline-block"
                        :data-gallery="`gallery-${selectedItem.id}`"
                    >
                        <img
                            :src="selectedItem.foto"
                            alt="Foto Bank Sampah"
                            class="max-w-full h-auto rounded-lg border hover:opacity-90 transition-opacity"
                            loading="lazy"
                        />
                    </a>
                </div>

                <!-- Aduan Masyarakat: Files -->
                <div v-if="selectedModule === 'aduan-masyarakat' && selectedItem.files && selectedItem.files.length > 0">
                    <h3 class="text-lg font-semibold mb-4">File Lampiran</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a
                            v-for="(file, index) in selectedItem.files"
                            :key="file.id || index"
                            :href="file.file_path"
                            class="glightbox-trigger relative group cursor-pointer"
                            :data-gallery="`gallery-${selectedItem.id}`"
                        >
                            <img
                                v-if="file.file_type === 'image'"
                                :src="file.file_path"
                                :alt="file.file_name || 'File'"
                                class="w-full h-48 object-cover rounded-lg border hover:opacity-90 transition-opacity"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="w-full h-48 flex items-center justify-center bg-muted rounded-lg border"
                            >
                                <FileText class="h-12 w-12 text-muted-foreground" />
                                <p class="text-xs text-muted-foreground mt-2">{{ file.file_name }}</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Pengajuan Proposal: Thumbnail -->
                <div v-if="selectedModule === 'pengajuan-proposal' && selectedItem.thumbnail_foto_banner">
                    <h3 class="text-lg font-semibold mb-4">Foto Banner</h3>
                    <a
                        :href="selectedItem.thumbnail_foto_banner"
                        class="glightbox-trigger inline-block"
                        :data-gallery="`gallery-${selectedItem.id}`"
                    >
                        <img
                            :src="selectedItem.thumbnail_foto_banner"
                            alt="Foto Banner"
                            class="max-w-full h-auto rounded-lg border hover:opacity-90 transition-opacity"
                            loading="lazy"
                        />
                    </a>
                </div>

                <!-- Daftar Warga (Houses only) -->
                <div v-if="selectedModule === 'houses' && houseResidents && houseResidents.length > 0">
                    <h3 class="text-lg font-semibold mb-4">Daftar Warga</h3>
                    <div class="border rounded-lg overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>NIK</TableHead>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Jenis Kelamin</TableHead>
                                    <TableHead>Usia</TableHead>
                                    <TableHead>No. KK</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Keterangan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="resident in houseResidents" :key="resident.id">
                                    <TableCell>{{ resident.nik }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <span>{{ resident.nama }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>{{ getJenisKelaminLabel(resident.jenis_kelamin) }}</TableCell>
                                    <TableCell>{{ calculateAge(resident.tanggal_lahir) }} tahun</TableCell>
                                    <TableCell>{{ resident.no_kk }}</TableCell>
                                    <TableCell>
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': resident.status === 'Aktif',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': resident.status === 'Pindah',
                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': resident.status === 'Meninggal',
                                            }"
                                        >
                                            {{ resident.status }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="resident.is_kepala_keluarga" class="text-sm text-muted-foreground">Kepala Keluarga</span>
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <!-- Program Bantuan yang Diterima (Houses only) -->
                <div v-if="selectedModule === 'houses' && allAssistancePrograms.length > 0" class="mt-6">
                    <h3 class="text-lg font-semibold mb-4">Program Bantuan yang Diterima</h3>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="program in allAssistancePrograms"
                            :key="`${program.family_id}-${program.id}`"
                            class="hover:shadow-lg transition-shadow cursor-pointer"
                            @click="router.visit(`/program-bantuan/program-bantuan/${program.program_id}`)"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <CardTitle class="text-base mb-1 line-clamp-2">
                                            {{ program.nama_program }}
                                        </CardTitle>
                                        <CardDescription class="text-xs">
                                            {{ program.tahun }} • {{ program.periode }}
                                        </CardDescription>
                                        <CardDescription class="text-xs mt-1">
                                            No. KK: {{ program.family_no_kk }}
                                        </CardDescription>
                                    </div>
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap ml-2"
                                        :class="getStatusBadgeClass(program.status)"
                                    >
                                        {{ getStatusLabel(program.status) }}
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <!-- Item Bantuan -->
                                <div v-if="program.items && program.items.length > 0" class="space-y-3">
                                    <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                                        <Package class="h-4 w-4 text-muted-foreground" />
                                        <span>Item Bantuan yang Diterima</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(item, index) in program.items"
                                            :key="index"
                                            class="flex items-center justify-between p-2 bg-muted/50 rounded-lg"
                                        >
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-foreground truncate">
                                                    {{ item.nama_item }}
                                                </p>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ item.tipe }}
                                                </p>
                                            </div>
                                            <div class="text-right ml-2">
                                                <p class="text-sm font-semibold text-foreground">
                                                    <span v-if="isCashItem(item.tipe)">
                                                        {{ formatCurrency(item.jumlah) }}
                                                    </span>
                                                    <span v-else>
                                                        {{ item.jumlah }}
                                                    </span>
                                                </p>
                                                <p class="text-xs text-muted-foreground">
                                                    <span v-if="isCashItem(item.tipe)">Rupiah</span>
                                                    <span v-else>{{ item.satuan }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-muted-foreground italic">
                                    Tidak ada item bantuan
                                </div>

                                <!-- Informasi Tambahan -->
                                <div class="mt-4 pt-4 border-t space-y-2">
                                    <div v-if="program.tanggal_penyaluran" class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Calendar class="h-3.5 w-3.5" />
                                        <span>Tanggal: {{ formatDate(program.tanggal_penyaluran) }}</span>
                                    </div>
                                    <div v-if="program.perwakilan && program.perwakilan !== '-'" class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <User class="h-3.5 w-3.5" />
                                        <span>Perwakilan: {{ program.perwakilan }}</span>
                                    </div>
                                    <div v-if="program.catatan && program.catatan !== '-'" class="flex items-start gap-2 text-xs text-muted-foreground">
                                        <FileText class="h-3.5 w-3.5 mt-0.5" />
                                        <span class="line-clamp-2">{{ program.catatan }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
                <div v-else-if="selectedModule === 'houses' && selectedItem?.families && selectedItem.families.length > 0" class="mt-6">
                    <h3 class="text-lg font-semibold mb-4">Program Bantuan yang Diterima</h3>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="text-center py-8 text-muted-foreground">
                                <Package class="h-12 w-12 mx-auto mb-3 opacity-50" />
                                <p class="text-sm">Belum ada program bantuan yang diterima oleh keluarga di rumah ini.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style>
/* Override z-index untuk modal agar tidak tertutup navbar - menggunakan global style */
body [data-slot="dialog-content"].welcome-map-modal,
body .welcome-map-modal[data-slot="dialog-content"] {
    z-index: 9999 !important;
    position: fixed !important;
}

body [data-slot="dialog-overlay"] {
    z-index: 9998 !important;
    position: fixed !important;
}

body > [data-reka-portal] {
    z-index: 9998 !important;
}
</style>

