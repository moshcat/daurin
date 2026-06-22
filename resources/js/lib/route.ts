export interface Point {
    id: string | number;
    lat: number;
    lng: number;
    label?: string;
    /** Berat muatan titik ini (kg) — dipakai untuk batas kapasitas pengepul. */
    weight?: number;
}

/** Rata-rata kecepatan urban (km/jam) kendaraan pengepul. */
const DEFAULT_SPEED_KMH = 40;

/** Faktor detour: koreksi jarak haversine garis-lurus menjadi perkiraan jarak jalan nyata. */
const ROAD_FACTOR = 1.4;

export interface RouteResult {
    order: Point[];
    totalDistanceKm: number;
    estimatedMinutes: number;
    estimatedCostRp: number;
    /** Total berat yang terangkut pada rute (kg). */
    totalWeightKg: number;
    /** Titik yang tidak masuk rute karena melebihi kapasitas. */
    excluded: Point[];
}

/** Haversine great-circle distance in kilometres. */
export function haversine(a: Point, b: Point): number {
    const R = 6371;
    const dLat = ((b.lat - a.lat) * Math.PI) / 180;
    const dLng = ((b.lng - a.lng) * Math.PI) / 180;
    const x =
        Math.sin(dLat / 2) ** 2 +
        Math.cos((a.lat * Math.PI) / 180) *
            Math.cos((b.lat * Math.PI) / 180) *
            Math.sin(dLng / 2) ** 2;

    return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
}

/**
 * Nearest-neighbour TSP heuristic dengan batas kapasitas muatan.
 * Mulai dari depot (lokasi pengepul) atau titik pertama. Saat `capacityKg`
 * diberikan, hanya titik yang muatannya masih muat yang dikunjungi — sisanya
 * dikembalikan pada `excluded`.
 */
export function planRoute(
    points: Point[],
    startLat?: number,
    startLng?: number,
    capacityKg?: number,
): RouteResult {
    if (points.length === 0) {
        return {
            order: [],
            totalDistanceKm: 0,
            estimatedMinutes: 0,
            estimatedCostRp: 0,
            totalWeightKg: 0,
            excluded: [],
        };
    }

    const depot: Point =
        startLat !== undefined && startLng !== undefined
            ? { id: 'depot', lat: startLat, lng: startLng }
            : points[0];

    const cap = capacityKg && capacityKg > 0 ? capacityKg : Infinity;

    const remaining = [...points];
    const order: Point[] = [];
    let current = depot;
    let totalDist = 0;
    let loaded = 0;

    while (remaining.length > 0) {
        // Cari titik terdekat yang muatannya masih muat dalam kapasitas.
        let nearestIdx = -1;
        let nearestDist = Infinity;

        for (let i = 0; i < remaining.length; i++) {
            if (loaded + (remaining[i].weight ?? 0) > cap) {
                continue;
            }

            const d = haversine(current, remaining[i]);

            if (d < nearestDist) {
                nearestDist = d;
                nearestIdx = i;
            }
        }

        // Tidak ada lagi titik yang muat — hentikan rute.
        if (nearestIdx === -1) {
            break;
        }

        const nearest = remaining[nearestIdx];
        order.push(nearest);
        totalDist += nearestDist;
        loaded += nearest.weight ?? 0;
        current = nearest;
        remaining.splice(nearestIdx, 1);
    }

    // Koreksi jarak garis-lurus menjadi perkiraan jarak jalan nyata.
    const roadDist = totalDist * ROAD_FACTOR;
    const costPerKm = 3000; // Rp 3.000/km estimate

    return {
        order,
        totalDistanceKm: Math.round(roadDist * 10) / 10,
        estimatedMinutes: Math.round((roadDist / DEFAULT_SPEED_KMH) * 60),
        estimatedCostRp: Math.round(roadDist * costPerKm),
        totalWeightKg: Math.round(loaded * 10) / 10,
        excluded: remaining,
    };
}
