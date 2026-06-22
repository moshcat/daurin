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

/** Real photo when uploaded, otherwise a deterministic placeholder for demo. */
export function listingImageUrl(item: Pick<ListingItem, 'id' | 'foto_path'>): string {
    if (item.foto_path) {
        return `/storage/${item.foto_path}`
    }

    return `https://picsum.photos/seed/daurin${item.id}/500/500`
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
        : `https://picsum.photos/seed/daurinbb${bb.id}/500/500`

    return {
        id: bb.id,
        layer: 'bahan_baku',
        title: jenisLabel(bb.jenis_sampah),
        subtitle: `${Number(bb.berat)} kg · ${bb.peruntukan ?? 'Bahan baku'}`,
        price: bb.harga_awal,
        image,
        badge: 'Bahan Baku',
        trace,
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
        : `https://picsum.photos/seed/daurinbj${bj.id}/500/500`

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
