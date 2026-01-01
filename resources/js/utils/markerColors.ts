/**
 * Helper function untuk mendapatkan warna marker berdasarkan jenis rumah
 */
export const getMarkerColorByJenisRumah = (jenisRumah: string): string => {
    const colorMap: Record<string, string> = {
        'RUMAH_TINGGAL': '#22c55e',      // Hijau
        'KONTRAKAN': '#f59e0b',          // Kuning/Orange
        'WARUNG_TOKO_USAHA': '#ef4444',  // Merah
        'FASILITAS_UMUM': '#a855f7',     // Ungu
    };
    
    return colorMap[jenisRumah] || '#3388ff'; // Default biru jika tidak ditemukan
};

/**
 * Helper function untuk mendapatkan warna marker berdasarkan module
 * Warna dipilih agar berbeda dengan warna jenis rumah:
 * - RUMAH_TINGGAL: #22c55e (Hijau)
 * - KONTRAKAN: #f59e0b (Kuning/Orange)
 * - WARUNG_TOKO_USAHA: #ef4444 (Merah)
 * - FASILITAS_UMUM: #a855f7 (Ungu)
 */
export const getMarkerColorByModule = (module: string): string => {
    const colorMap: Record<string, string> = {
        'houses': '#3388ff',           // Biru (akan diganti oleh jenis rumah)
        'bank-sampah': '#06b6d4',     // Cyan (berbeda dari hijau rumah tinggal)
        'aduan-masyarakat': '#f97316', // Orange lebih terang (berbeda dari kontrakan)
        'layanan-darurat': '#dc2626',  // Merah lebih gelap (berbeda dari warung/toko)
        'pengajuan-proposal': '#6366f1', // Indigo (berbeda dari ungu fasilitas umum)
    };
    
    return colorMap[module] || '#3388ff'; // Default biru jika tidak ditemukan
};

import L from 'leaflet';

/**
 * Membuat custom marker icon dengan warna tertentu
 */
export const createColoredMarkerIcon = (color: string): L.DivIcon => {
    return L.divIcon({
        className: 'custom-colored-marker',
        html: `
            <div style="
                width: 30px;
                height: 30px;
                background-color: ${color};
                border: 3px solid white;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                position: relative;
            ">
                <div style="
                    width: 12px;
                    height: 12px;
                    background-color: white;
                    border-radius: 50%;
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(45deg);
                "></div>
            </div>
        `,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -30],
    });
};

