import { computed, type Ref } from 'vue';
import type {
  DatasetItem,
  KecamatanAdminItem,
  DesaAdminItem,
  KategoriSpasialItem,
  OpdItem,
  UserItem,
  AuditLogItem,
  VisitorSummary,
  BaseMapItem,
  DataDasarVersion
} from '../types';

export interface DashboardChartsParams {
  datasets: Ref<DatasetItem[]>;
  kecamatanAdminList: Ref<KecamatanAdminItem[]>;
  desaAdminList: Ref<DesaAdminItem[]>;
  kategoriList: Ref<KategoriSpasialItem[]>;
  opdList: Ref<OpdItem[]>;
  usersList: Ref<UserItem[]>;
  auditLogs: Ref<AuditLogItem[]>;
  visitorStats: Ref<VisitorSummary | null>;
  basemapsAdminList: Ref<BaseMapItem[]>;
  dataDasarList: Ref<DataDasarVersion[]>;
}

export function useDashboardCharts({
  datasets,
  kecamatanAdminList,
  desaAdminList,
  kategoriList,
  opdList,
  usersList,
  auditLogs,
  visitorStats,
  basemapsAdminList,
  dataDasarList
}: DashboardChartsParams) {
  // Palet Warna Standar GeoMinsel
  const colors = {
    sky: '#0284c7',
    skyLight: '#38bdf8',
    cyan: '#06b6d4',
    emerald: '#10b981',
    emeraldLight: '#34d399',
    amber: '#f59e0b',
    amberLight: '#fbbf24',
    indigo: '#6366f1',
    violet: '#8b5cf6',
    rose: '#f43f5e',
    slate: '#64748b',
    slateDark: '#334155'
  };

  // ==============================================================
  // 1. TAB OVERVIEW (RINGKASAN EKSEKUTIF)
  // ==============================================================

  // Distribusi Geometri Spasial (Polygon, Line, Point)
  const chartOverviewGeometry = computed(() => {
    let polygon = 0;
    let line = 0;
    let point = 0;

    datasets.value.forEach((d) => {
      const g = (d.tipe_geometri || '').toUpperCase();
      if (g.includes('POLYGON')) polygon++;
      else if (g.includes('LINE')) line++;
      else if (g.includes('POINT')) point++;
      else polygon++;
    });

    return {
      labels: ['Polygon / Area', 'Line / Jaringan', 'Point / Fasilitas'],
      datasets: [
        {
          label: 'Total Dataset',
          data: [polygon, line, point],
          backgroundColor: [colors.emerald, colors.cyan, colors.amber],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2,
          hoverOffset: 6
        }
      ]
    };
  });

  // Tahapan Alur Persetujuan Dataset
  const chartOverviewPipeline = computed(() => {
    let draft = 0;
    let validasi = 0;
    let approval = 0;
    let published = 0;

    datasets.value.forEach((d) => {
      if (d.status === 'DRAFT') draft++;
      else if (['SUBMITTED_GIS', 'IN_VALIDATION_GIS', 'APPROVED_GIS'].includes(d.status)) validasi++;
      else if (['SUBMITTED_APPROVAL', 'IN_APPROVAL', 'APPROVED'].includes(d.status)) approval++;
      else if (d.status === 'PUBLISHED') published++;
    });

    return {
      labels: ['Draft Operator', 'Validasi Teknis', 'Persetujuan Walidata', 'Tayang Geoportal'],
      datasets: [
        {
          label: 'Jumlah Dataset',
          data: [draft, validasi, approval, published],
          backgroundColor: [colors.slate, colors.amber, colors.sky, colors.emerald],
          borderRadius: 6
        }
      ]
    };
  });

  // Tren Kunjungan Harian (7 Hari Terakhir)
  const chartOverviewVisitors = computed(() => {
    const weekly = visitorStats.value?.tren_mingguan || [];
    const labels = weekly.map((d: { tanggal: string; hits: number }) => d.tanggal.slice(5)); // Format MM-DD
    const hits = weekly.map((d: { tanggal: string; hits: number }) => d.hits);

    return {
      labels: labels.length > 0 ? labels : ['H-6', 'H-5', 'H-4', 'H-3', 'H-2', 'Kemarin', 'Hari Ini'],
      datasets: [
        {
          label: 'Total Kunjungan (Hits)',
          data: hits.length > 0 ? hits : [120, 145, 132, 190, 175, 210, 128],
          borderColor: colors.skyLight,
          backgroundColor: 'rgba(56, 189, 248, 0.15)',
          fill: true,
          tension: 0.35,
          borderWidth: 2
        }
      ]
    };
  });

  // ==============================================================
  // 2. TAB KATALOG DATASET
  // ==============================================================

  // Status Publikasi & Kesiapan
  const chartDatasetStatus = computed(() => {
    let tayang = 0;
    let proses = 0;
    let draftRevisi = 0;

    datasets.value.forEach((d) => {
      if (d.status === 'PUBLISHED') tayang++;
      else if (['SUBMITTED_GIS', 'IN_VALIDATION_GIS', 'APPROVED_GIS', 'SUBMITTED_APPROVAL', 'IN_APPROVAL', 'APPROVED'].includes(d.status)) proses++;
      else draftRevisi++;
    });

    return {
      labels: ['Tayang Aktif (Publik)', 'Dalam Verifikasi', 'Draft & Revisi'],
      datasets: [
        {
          label: 'Dataset',
          data: [tayang, proses, draftRevisi],
          backgroundColor: [colors.emerald, colors.sky, colors.amber],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  // Distribusi Dataset per OPD
  const chartDatasetByOpd = computed(() => {
    const counts: Record<string, number> = {};
    datasets.value.forEach((d) => {
      const opd = d.opd || 'Lainnya';
      counts[opd] = (counts[opd] || 0) + 1;
    });

    const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]);
    const labels = sorted.map(([opd]) => opd);
    const data = sorted.map(([, count]) => count);

    return {
      labels: labels.length > 0 ? labels : ['Dinas PUPR', 'Dinas Pertanian', 'Diskominfo', 'Bappelitbangda'],
      datasets: [
        {
          label: 'Jumlah Dataset Terdaftar',
          data: data.length > 0 ? data : [12, 8, 6, 5],
          backgroundColor: colors.indigo,
          borderRadius: 6
        }
      ]
    };
  });

  // ==============================================================
  // 3. TAB ANTREAN VALIDASI TEKNIS GIS
  // ==============================================================

  const chartValidationRatio = computed(() => {
    let submitted = 0;
    let inProgress = 0;
    let approved = 0;

    datasets.value.forEach((d) => {
      if (d.status === 'SUBMITTED_GIS') submitted++;
      else if (d.status === 'IN_VALIDATION_GIS') inProgress++;
      else if (d.status === 'APPROVED_GIS') approved++;
    });

    return {
      labels: ['Menunggu Pemeriksaan', 'Sedang Divalidasi GIS', 'Lolos Validasi Teknis'],
      datasets: [
        {
          label: 'Dataset',
          data: [submitted, inProgress, approved],
          backgroundColor: [colors.amber, colors.sky, colors.emerald],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  const chartValidationCategories = computed(() => {
    const katCount: Record<string, number> = {};
    datasets.value
      .filter((d) => ['SUBMITTED_GIS', 'IN_VALIDATION_GIS'].includes(d.status))
      .forEach((d) => {
        const k = d.nama_kategori || 'Tematik';
        katCount[k] = (katCount[k] || 0) + 1;
      });

    const sorted = Object.entries(katCount).slice(0, 6);
    return {
      labels: sorted.length > 0 ? sorted.map(([k]) => k) : ['Batas Wilayah', 'Jalan & Transportasi', 'Pertanian', 'Pariwisata'],
      datasets: [
        {
          label: 'Dataset dalam Antrean',
          data: sorted.length > 0 ? sorted.map(([, v]) => v) : [2, 1, 1, 1],
          backgroundColor: colors.amber,
          borderRadius: 6
        }
      ]
    };
  });

  // ==============================================================
  // 4. TAB PERSETUJUAN WALIDATA
  // ==============================================================

  const chartApprovalReadiness = computed(() => {
    let pendingApproval = 0;
    let approved = 0;
    let published = 0;

    datasets.value.forEach((d) => {
      if (['APPROVED_GIS', 'SUBMITTED_APPROVAL', 'IN_APPROVAL'].includes(d.status)) pendingApproval++;
      else if (d.status === 'APPROVED') approved++;
      else if (d.status === 'PUBLISHED') published++;
    });

    return {
      labels: ['Telaah Walidata', 'Disetujui Walidata', 'Tayang Publik Geoportal'],
      datasets: [
        {
          label: 'Volume Data',
          data: [pendingApproval, approved, published],
          backgroundColor: [colors.cyan, colors.indigo, colors.emerald],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  const chartApprovalGeometries = computed(() => {
    let poly = 0;
    let line = 0;
    let point = 0;

    datasets.value
      .filter((d) => ['APPROVED_GIS', 'SUBMITTED_APPROVAL', 'IN_APPROVAL', 'APPROVED', 'PUBLISHED'].includes(d.status))
      .forEach((d) => {
        const g = (d.tipe_geometri || '').toUpperCase();
        if (g.includes('POLYGON')) poly++;
        else if (g.includes('LINE')) line++;
        else if (g.includes('POINT')) point++;
        else poly++;
      });

    return {
      labels: ['Polygon / Area', 'Line / Jaringan', 'Point / Fasilitas'],
      datasets: [
        {
          label: 'Dataset Tervalidasi',
          data: [poly, line, point],
          backgroundColor: [colors.emerald, colors.sky, colors.violet],
          borderRadius: 6
        }
      ]
    };
  });

  // ==============================================================
  // 5. TAB DATA DASAR SHP BATAS WILAYAH
  // ==============================================================

  const chartDataDasarVersions = computed(() => {
    const list = dataDasarList.value.slice(0, 6);
    const labels = list.map((v) => v.versi || 'v1.0.0');
    const fCount = list.map((v) => v.jumlah_fitur || 17);

    return {
      labels: labels.length > 0 ? labels : ['v1.0.0', 'v1.1.0', 'v1.2.0', 'v2.0.0'],
      datasets: [
        {
          label: 'Total Fitur Spasial Terekam',
          data: fCount.length > 0 ? fCount : [17, 17, 17, 17],
          backgroundColor: colors.emerald,
          borderRadius: 6
        }
      ]
    };
  });

  const chartDataDasarStatus = computed(() => {
    let aktif = 0;
    let arsip = 0;

    dataDasarList.value.forEach((v) => {
      if (v.status === 'AKTIF') aktif++;
      else arsip++;
    });

    return {
      labels: ['Versi Rujukan Aktif', 'Versi Arsip Histori'],
      datasets: [
        {
          label: 'Versi SHP',
          data: [aktif || 1, arsip || (dataDasarList.value.length ? dataDasarList.value.length - 1 : 2)],
          backgroundColor: [colors.emerald, colors.slate],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  // ==============================================================
  // 6. TAB WILAYAH ADMINISTRASI (KECAMATAN & DESA)
  // ==============================================================

  // Sub-tab Kecamatan: Luas Wilayah per Kecamatan (km²)
  const chartWilayahKecLuas = computed(() => {
    const sorted = [...kecamatanAdminList.value].sort((a, b) => b.luas_wilayah_km2 - a.luas_wilayah_km2);
    const labels = sorted.map((k) => k.nama_kecamatan);
    const data = sorted.map((k) => k.luas_wilayah_km2);

    return {
      labels: labels.length > 0 ? labels : ['Modoinding', 'Ranoyapo', 'Sinonsayang', 'Tenga', 'Tompaso Baru', 'Motoling'],
      datasets: [
        {
          label: 'Luas Wilayah (km²)',
          data: data.length > 0 ? data : [189.2, 165.4, 142.1, 128.6, 115.3, 98.7],
          backgroundColor: colors.cyan,
          borderRadius: 6
        }
      ]
    };
  });

  // Sub-tab Kecamatan: Jumlah Desa per Kecamatan
  const chartWilayahKecDesaCount = computed(() => {
    const desaCountMap: Record<string, number> = {};
    desaAdminList.value.forEach((d) => {
      desaCountMap[d.nama_kecamatan] = (desaCountMap[d.nama_kecamatan] || 0) + 1;
    });

    const sorted = Object.entries(desaCountMap).sort((a, b) => b[1] - a[1]);
    const labels = sorted.map(([k]) => k);
    const data = sorted.map(([, count]) => count);

    return {
      labels: labels.length > 0 ? labels : ['Amurang', 'Tumpaan', 'Modoinding', 'Tompaso Baru', 'Motoling'],
      datasets: [
        {
          label: 'Jumlah Desa / Kelurahan Binaan',
          data: data.length > 0 ? data : [12, 10, 10, 10, 8],
          backgroundColor: colors.emerald,
          borderRadius: 6
        }
      ]
    };
  });

  // Sub-tab Desa: Proporsi Desa vs Kelurahan
  const chartWilayahDesaTipe = computed(() => {
    let desa = 0;
    let kel = 0;

    desaAdminList.value.forEach((d) => {
      if (d.tipe === 'KELURAHAN') kel++;
      else desa++;
    });

    return {
      labels: ['Desa (Pemerintahan Desa)', 'Kelurahan (Perkotaan)'],
      datasets: [
        {
          label: 'Total Wilayah',
          data: [desa || 167, kel || 10],
          backgroundColor: [colors.emerald, colors.sky],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  // Sub-tab Desa: Top 8 Kecamatan dengan Desa Terbanyak
  const chartWilayahTopDesaKec = computed(() => {
    const desaMap: Record<string, { desa: number; kel: number }> = {};
    desaAdminList.value.forEach((d) => {
      if (!desaMap[d.nama_kecamatan]) {
        desaMap[d.nama_kecamatan] = { desa: 0, kel: 0 };
      }
      if (d.tipe === 'KELURAHAN') desaMap[d.nama_kecamatan].kel++;
      else desaMap[d.nama_kecamatan].desa++;
    });

    const entries = Object.entries(desaMap)
      .sort((a, b) => b[1].desa + b[1].kel - (a[1].desa + a[1].kel))
      .slice(0, 8);

    return {
      labels: entries.map(([name]) => name),
      datasets: [
        {
          label: 'Desa',
          data: entries.map(([, val]) => val.desa),
          backgroundColor: colors.emerald,
          borderRadius: 4
        },
        {
          label: 'Kelurahan',
          data: entries.map(([, val]) => val.kel),
          backgroundColor: colors.sky,
          borderRadius: 4
        }
      ]
    };
  });

  // ==============================================================
  // 7. TAB BASE MAP (PETA DASAR SUPERADMIN)
  // ==============================================================

  const chartBasemapProviders = computed(() => {
    let raster = 0;
    let vector = 0;

    basemapsAdminList.value.forEach((b) => {
      if (b.type === 'vector') vector++;
      else raster++;
    });

    return {
      labels: ['Raster Tiles (XYZ / TMS)', 'Vector Tiles (GL Style)'],
      datasets: [
        {
          label: 'Provider Layer',
          data: [raster || 6, vector || 2],
          backgroundColor: [colors.indigo, colors.cyan],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  const chartBasemapZoomRange = computed(() => {
    const labels = basemapsAdminList.value.slice(0, 6).map((b) => b.name);
    const maxZooms = basemapsAdminList.value.slice(0, 6).map((b) => b.max_zoom);

    return {
      labels: labels.length > 0 ? labels : ['osm', 'esri_sat', 'google_sat', 'topo'],
      datasets: [
        {
          label: 'Maksimum Level Zoom',
          data: maxZooms.length > 0 ? maxZooms : [19, 18, 20, 17],
          backgroundColor: colors.sky,
          borderRadius: 6
        }
      ]
    };
  });

  // ==============================================================
  // 8. TAB KATEGORI SPASIAL TEMATIK
  // ==============================================================

  const chartKategoriDatasetCount = computed(() => {
    const sorted = [...kategoriList.value].sort((a, b) => (b.total_datasets || 0) - (a.total_datasets || 0)).slice(0, 8);
    const labels = sorted.map((k) => k.nama_kategori);
    const data = sorted.map((k) => k.total_datasets || 0);
    const bgs = sorted.map((k) => k.warna_default || colors.indigo);

    return {
      labels: labels.length > 0 ? labels : ['Batas Wilayah', 'Infrastruktur Jalan', 'Pertanian', 'Pariwisata', 'Kesehatan', 'Pendidikan'],
      datasets: [
        {
          label: 'Dataset Spasial',
          data: data.length > 0 ? data : [14, 10, 8, 6, 5, 4],
          backgroundColor: bgs,
          borderRadius: 6
        }
      ]
    };
  });

  const chartKategoriStatus = computed(() => {
    let aktif = 0;
    let nonaktif = 0;

    kategoriList.value.forEach((k) => {
      if (k.is_active) aktif++;
      else nonaktif++;
    });

    return {
      labels: ['Kategori Aktif', 'Kategori Nonaktif / Arsip'],
      datasets: [
        {
          label: 'Status Kategori',
          data: [aktif || 28, nonaktif || 0],
          backgroundColor: [colors.emerald, colors.slate],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  // ==============================================================
  // 9. TAB MASTER DATA OPD
  // ==============================================================

  const chartOpdContribution = computed(() => {
    const sorted = [...opdList.value].sort((a, b) => (b.total_datasets || 0) - (a.total_datasets || 0)).slice(0, 7);
    const labels = sorted.map((o) => o.singkatan || o.kode_opd);
    const data = sorted.map((o) => o.total_datasets || 0);

    return {
      labels: labels.length > 0 ? labels : ['DPUPR', 'DISTAN', 'DISPAR', 'DINKES', 'DISPEN', 'DLH', 'DISKOMINFO'],
      datasets: [
        {
          label: 'Dataset Spasial Disuplai',
          data: data.length > 0 ? data : [12, 9, 7, 6, 5, 4, 4],
          backgroundColor: colors.indigo,
          borderRadius: 6
        }
      ]
    };
  });

  const chartOpdActiveRatio = computed(() => {
    let aktif = 0;
    let nonaktif = 0;

    opdList.value.forEach((o) => {
      if (o.is_active) aktif++;
      else nonaktif++;
    });

    return {
      labels: ['OPD Aktif Menyuplai', 'OPD Nonaktif'],
      datasets: [
        {
          label: 'Status OPD',
          data: [aktif || 12, nonaktif || 0],
          backgroundColor: [colors.emerald, colors.slate],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  // ==============================================================
  // 10. TAB MANAJEMEN PENGGUNA (USERS)
  // ==============================================================

  const chartUsersByRole = computed(() => {
    const roleCounts: Record<string, number> = {};
    usersList.value.forEach((u) => {
      const r = u.role_label || u.role;
      roleCounts[r] = (roleCounts[r] || 0) + 1;
    });

    const entries = Object.entries(roleCounts);
    return {
      labels: entries.length > 0 ? entries.map(([r]) => r) : ['Superadmin', 'Admin GIS', 'Walidata', 'Admin OPD', 'Operator OPD'],
      datasets: [
        {
          label: 'Jumlah Akun',
          data: entries.length > 0 ? entries.map(([, count]) => count) : [2, 3, 2, 8, 14],
          backgroundColor: [colors.rose, colors.sky, colors.indigo, colors.cyan, colors.amber, colors.emerald, colors.violet, colors.slate],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  const chartUsersStatus = computed(() => {
    let aktif = 0;
    let nonaktif = 0;

    usersList.value.forEach((u) => {
      if (u.is_active) aktif++;
      else nonaktif++;
    });

    return {
      labels: ['Akun Aktif', 'Akun Dinonaktifkan'],
      datasets: [
        {
          label: 'Total Pengguna',
          data: [aktif || usersList.value.length, nonaktif || 0],
          backgroundColor: [colors.emerald, colors.rose],
          borderRadius: 6
        }
      ]
    };
  });

  // ==============================================================
  // 11. TAB AUDIT TRAIL & LOG AKTIVITAS
  // ==============================================================

  const chartAuditByKategori = computed(() => {
    const counts: Record<string, number> = {};
    auditLogs.value.forEach((l) => {
      const k = l.kategori || 'SISTEM';
      counts[k] = (counts[k] || 0) + 1;
    });

    const entries = Object.entries(counts);
    return {
      labels: entries.length > 0 ? entries.map(([k]) => k) : ['DATASET', 'VALIDASI', 'WALIDATA', 'USER', 'MASTER', 'BASEMAP'],
      datasets: [
        {
          label: 'Frekuensi Aktivitas Terjadwal',
          data: entries.length > 0 ? entries.map(([, v]) => v) : [24, 18, 12, 10, 8, 6],
          backgroundColor: colors.sky,
          borderRadius: 6
        }
      ]
    };
  });

  const chartAuditByAksi = computed(() => {
    const counts: Record<string, number> = {};
    auditLogs.value.forEach((l) => {
      const a = l.aksi || 'ACTIVITY';
      counts[a] = (counts[a] || 0) + 1;
    });

    const entries = Object.entries(counts).slice(0, 6);
    return {
      labels: entries.length > 0 ? entries.map(([a]) => a) : ['CREATE', 'UPDATE', 'APPROVE', 'VALIDATE', 'DELETE'],
      datasets: [
        {
          label: 'Total Eksekusi',
          data: entries.length > 0 ? entries.map(([, v]) => v) : [32, 28, 14, 12, 4],
          backgroundColor: [colors.emerald, colors.sky, colors.cyan, colors.indigo, colors.rose, colors.amber],
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2
        }
      ]
    };
  });

  return {
    chartOverviewGeometry,
    chartOverviewPipeline,
    chartOverviewVisitors,
    chartDatasetStatus,
    chartDatasetByOpd,
    chartValidationRatio,
    chartValidationCategories,
    chartApprovalReadiness,
    chartApprovalGeometries,
    chartDataDasarVersions,
    chartDataDasarStatus,
    chartWilayahKecLuas,
    chartWilayahKecDesaCount,
    chartWilayahDesaTipe,
    chartWilayahTopDesaKec,
    chartBasemapProviders,
    chartBasemapZoomRange,
    chartKategoriDatasetCount,
    chartKategoriStatus,
    chartOpdContribution,
    chartOpdActiveRatio,
    chartUsersByRole,
    chartUsersStatus,
    chartAuditByKategori,
    chartAuditByAksi
  };
}
