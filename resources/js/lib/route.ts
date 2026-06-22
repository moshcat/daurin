export interface Point {
    id: string | number;
    lat: number;
    lng: number;
    label?: string;
}

export type TransportMode = 'mobil' | 'jalan' | 'sepeda';

/** Rata-rata kecepatan urban (km/jam) per moda transport. */
const SPEED_KMH: Record<TransportMode, number> = {
    mobil: 40,
    jalan: 5,
    sepeda: 15,
};

/** Faktor detour: koreksi jarak haversine garis-lurus menjadi perkiraan jarak jalan nyata. */
const ROAD_FACTOR = 1.4;

export interface RouteResult {
    order: Point[];
    totalDistanceKm: number;
    estimatedMinutes: number;
    estimatedCostRp: number;
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
 * Nearest-neighbour TSP heuristic.
 * Starts from the depot (pengepul location) or the first point.
 */
export function planRoute(
    points: Point[],
    startLat?: number,
    startLng?: number,
    mode: TransportMode = 'mobil',
): RouteResult {
    if (points.length === 0) {
        return { order: [], totalDistanceKm: 0, estimatedMinutes: 0, estimatedCostRp: 0 };
    }

    const depot: Point =
        startLat !== undefined && startLng !== undefined
            ? { id: 'depot', lat: startLat, lng: startLng }
            : points[0];

    const remaining = [...points];
    const order: Point[] = [];
    let current = depot;
    let totalDist = 0;

    while (remaining.length > 0) {
        let nearestIdx = 0;
        let nearestDist = haversine(current, remaining[0]);

        for (let i = 1; i < remaining.length; i++) {
            const d = haversine(current, remaining[i]);

            if (d < nearestDist) {
                nearestDist = d;
                nearestIdx = i;
            }
        }

        const nearest = remaining[nearestIdx];
        order.push(nearest);
        totalDist += nearestDist;
        current = nearest;
        remaining.splice(nearestIdx, 1);
    }

    // Koreksi jarak garis-lurus menjadi perkiraan jarak jalan nyata.
    const roadDist = totalDist * ROAD_FACTOR;
    const costPerKm = 3000; // Rp 3.000/km estimate

    return {
        order,
        totalDistanceKm: Math.round(roadDist * 10) / 10,
        estimatedMinutes: Math.round((roadDist / SPEED_KMH[mode]) * 60),
        estimatedCostRp: Math.round(roadDist * costPerKm),
    };
}
