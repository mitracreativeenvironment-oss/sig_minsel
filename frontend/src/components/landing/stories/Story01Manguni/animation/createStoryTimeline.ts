export interface ShotMetadata {
  index: number;
  id: string;
  name: string;
  desc: string;
  color: string;
}

export const STORY_01_SHOTS: ShotMetadata[] = [
  {
    index: 1,
    id: 'shot-01-night-sky',
    name: 'Shot 01: The Night Sky',
    desc: 'Langit malam berbintang hening di atas belantara Minahasa Selatan',
    color: '#93c5fd'
  },
  {
    index: 2,
    id: 'shot-02-into-wilderness',
    name: 'Shot 02: Into the Wilderness',
    desc: 'Menembus kanopi hutan tropis alami berbalut kabut tipis dan sinar bulan',
    color: '#38bdf8'
  },
  {
    index: 3,
    id: 'shot-03-awakening',
    name: 'Shot 03: The Awakening',
    desc: 'Celepuk Sulawesi bertengger, mata terbuka waspada menatap ufuk fajar',
    color: '#facc15'
  },
  {
    index: 4,
    id: 'shot-04-first-light',
    name: 'Shot 04: The First Light',
    desc: 'Cahaya fajar pertama menyentuh tubuh burung dan kanopi hutan belantara',
    color: '#f59e0b'
  },
  {
    index: 5,
    id: 'shot-05-flight',
    name: 'Shot 05: The Flight',
    desc: 'Burung lepas landas dari dahan, mengepak naik membubung ke angkasa',
    color: '#ea580c'
  },
  {
    index: 6,
    id: 'shot-06-transformation',
    name: 'Shot 06: The Transformation',
    desc: 'Kamera mengitari burung menuju tampilan depan, bertransformasi ke Manguni lambang resmi',
    color: '#dc2626'
  },
  {
    index: 7,
    id: 'shot-07-emblem-manguni',
    name: 'Shot 07: The Emblem Manguni',
    desc: 'Komponen 3D Manguni lambang resmi terbentuk utuh, siap meneruskan perjalanan ke Story 02',
    color: '#fde047'
  }
];

export function getStory01CurrentShot(t: number): ShotMetadata {
  if (t < 0.12) return STORY_01_SHOTS[0];
  if (t < 0.25) return STORY_01_SHOTS[1];
  if (t < 0.42) return STORY_01_SHOTS[2];
  if (t < 0.55) return STORY_01_SHOTS[3];
  if (t < 0.72) return STORY_01_SHOTS[4];
  if (t < 0.92) return STORY_01_SHOTS[5];
  return STORY_01_SHOTS[6];
}
