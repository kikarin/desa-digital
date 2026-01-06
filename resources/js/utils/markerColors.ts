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

/**
 * Membuat icon home (rumah) dengan warna tertentu
 */
export const createHomeIcon = (color: string): L.DivIcon => {
    return L.divIcon({
        className: 'custom-home-icon',
        html: `
            <div style="
                width: 36px;
                height: 36px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <svg 
                    width="36" 
                    height="36" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    xmlns="http://www.w3.org/2000/svg"
                    style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"
                >
                    <!-- Background circle -->
                    <circle cx="12" cy="12" r="11" fill="white" stroke="${color}" stroke-width="2"/>
                    <!-- Home icon -->
                    <path 
                        d="M12 3L20 9V21H15V14H9V21H4V9L12 3Z" 
                        fill="${color}"
                        stroke="${color}"
                        stroke-width="1"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
        `,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        popupAnchor: [0, -18],
    });
};

