export interface ListingItem {
    id: number
    jenis_sampah: string
    berat: number | string
    harga: number | string
    status: string
    foto_path?: string | null
    ai_label?: string | null
    user: { name: string }
}

export interface BahanBakuItem {
    id: number
    jenis_sampah: string
    peruntukan?: string | null
    berat: number | string
    harga_awal: number | string
    status: string
    user: { name: string }
    source_listing?: (ListingItem & { user?: { name: string } }) | null
    lelang?: { id: number; status: string } | null
}

export interface BahanJadiItem {
    id: number
    nama: string
    jenis_sampah: string
    deskripsi?: string | null
    berat: number | string
    harga: number | string
    status: string
    foto_path?: string | null
    user: { name: string }
    source_pesanan?: {
        bahan_baku?: BahanBakuItem | null
    } | null
}

export type Layer = 'sampah' | 'bahan_baku' | 'bahan_jadi'

export interface TraceNode {
    role: 'rumah_tangga' | 'pengepul' | 'industri'
    actor: string
    title: string
    detail: string
}

export interface MarketProduct {
    id: number
    layer: Layer
    title: string
    subtitle: string
    price: number | string
    image: string
    badge: string
    trace?: TraceNode[]
    lelangId?: number | null
}

export const jenisSampahLabel: Record<string, string> = {
    plastik_pet: 'Plastik PET',
    plastik_hdpe: 'Plastik HDPE',
    kertas: 'Kertas',
    kardus: 'Kardus',
    logam: 'Logam',
    kaleng: 'Kaleng',
    kaca: 'Kaca',
    elektronik: 'Elektronik',
}

export function jenisLabel(v: string): string {
    return jenisSampahLabel[v] ?? v
}

export function formatRp(n: number | string): string {
    return 'Rp ' + Number(n).toLocaleString('id-ID')
}

export function listingImageUrl(item: Pick<ListingItem, 'id' | 'foto_path'>): string {
    if (item.foto_path) {
        return `/storage/${item.foto_path}`
    }

    return `data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22500%22%20height%3D%22500%22%20viewBox%3D%220%200%20500%20500%22%3E%3Crect%20width%3D%22500%22%20height%3D%22500%22%20fill%3D%22%23e2e8f0%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20font-family%3D%22sans-serif%22%20font-size%3D%2224%22%20fill%3D%22%2394a3b8%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E` // Fallback to local generic placeholder
}

// ── Normalised product mappers (one card for all three layers) ───────────────

function listingTraceNode(l: ListingItem & { user?: { name: string } }): TraceNode {
    return {
        role: 'rumah_tangga',
        actor: l.user?.name ?? 'Rumah Tangga',
        title: jenisLabel(l.jenis_sampah),
        detail: `${Number(l.berat)} kg${l.ai_label ? ` · AI: ${l.ai_label}` : ''}`,
    }
}

export function productFromListing(item: ListingItem): MarketProduct {
    return {
        id: item.id,
        layer: 'sampah',
        title: jenisLabel(item.jenis_sampah),
        subtitle: `${Number(item.berat)} kg · ${item.ai_label ?? 'Sangat baik'}`,
        price: item.harga,
        image: listingImageUrl(item),
        badge: jenisLabel(item.jenis_sampah),
    }
}

export function productFromBahanBaku(bb: BahanBakuItem): MarketProduct {
    const trace: TraceNode[] = []

    if (bb.source_listing) {
        trace.push(listingTraceNode(bb.source_listing))
    }

    trace.push({
        role: 'pengepul',
        actor: bb.user?.name ?? 'Pengepul',
        title: jenisLabel(bb.jenis_sampah),
        detail: `${Number(bb.berat)} kg · bahan baku`,
    })

    const image = bb.source_listing?.foto_path
        ? `/storage/${bb.source_listing.foto_path}`
        : `data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22500%22%20height%3D%22500%22%20viewBox%3D%220%200%20500%20500%22%3E%3Crect%20width%3D%22500%22%20height%3D%22500%22%20fill%3D%22%23e2e8f0%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20font-family%3D%22sans-serif%22%20font-size%3D%2224%22%20fill%3D%22%2394a3b8%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E`

    return {
        id: bb.id,
        layer: 'bahan_baku',
        title: jenisLabel(bb.jenis_sampah),
        subtitle: `${Number(bb.berat)} kg · ${bb.peruntukan ?? 'Bahan baku'}`,
        price: bb.harga_awal,
        image,
        badge: bb.lelang ? 'Negosiasi' : 'Bahan Baku',
        trace,
        lelangId: bb.lelang?.id ?? null,
    }
}

export function productFromBahanJadi(bj: BahanJadiItem): MarketProduct {
    const src = bj.source_pesanan?.bahan_baku ?? null
    const trace: TraceNode[] = []

    if (src?.source_listing) {
        trace.push(listingTraceNode(src.source_listing))
    }

    if (src) {
        trace.push({
            role: 'pengepul',
            actor: src.user?.name ?? 'Pengepul',
            title: jenisLabel(src.jenis_sampah),
            detail: `${Number(src.berat)} kg · bahan baku`,
        })
    }

    trace.push({
        role: 'industri',
        actor: bj.user?.name ?? 'Industri',
        title: bj.nama,
        detail: `${Number(bj.berat)} kg · bahan jadi`,
    })

    const image = bj.foto_path
        ? `/storage/${bj.foto_path}`
        : `data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22500%22%20height%3D%22500%22%20viewBox%3D%220%200%20500%20500%22%3E%3Crect%20width%3D%22500%22%20height%3D%22500%22%20fill%3D%22%23e2e8f0%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20font-family%3D%22sans-serif%22%20font-size%3D%2224%22%20fill%3D%22%2394a3b8%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E`

    return {
        id: bj.id,
        layer: 'bahan_jadi',
        title: bj.nama,
        subtitle: `${Number(bj.berat)} kg · ${jenisLabel(bj.jenis_sampah)}`,
        price: bj.harga,
        image,
        badge: 'Bahan Jadi',
        trace,
    }
}
