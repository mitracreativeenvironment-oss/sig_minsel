import * as THREE from 'three';

export interface Story01Environment {
  group: THREE.Group;
  starField: THREE.Points;
  branchGroup: THREE.Group;
  canopyGroup: THREE.Group;
  update: (progress: number, elapsed: number) => void;
  dispose: () => void;
}

export function createEnvironment(): Story01Environment {
  const group = new THREE.Group();

  // 1. Celestial Starfield (Shot 01 The Night Sky)
  const starCount = 350;
  const starGeo = new THREE.BufferGeometry();
  const starPositions = new Float32Array(starCount * 3);
  const starOpacities = new Float32Array(starCount);

  for (let i = 0; i < starCount; i++) {
    // Distribute across wide dome above
    starPositions[i * 3] = (Math.random() - 0.5) * 180;
    starPositions[i * 3 + 1] = 10 + Math.random() * 90; // High in the sky
    starPositions[i * 3 + 2] = (Math.random() - 0.5) * 180;
    starOpacities[i] = 0.4 + Math.random() * 0.6;
  }

  starGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
  starGeo.setAttribute('alpha', new THREE.BufferAttribute(starOpacities, 1));

  const starMat = new THREE.PointsMaterial({
    color: 0x93c5fd,
    size: 1.25,
    transparent: true,
    opacity: 0.95,
    blending: THREE.AdditiveBlending
  });

  const starField = new THREE.Points(starGeo, starMat);
  group.add(starField);

  // 2. Wilderness Canopy Silhouettes (Shot 01-02 Into the Wilderness)
  const canopyGroup = new THREE.Group();
  canopyGroup.position.set(0, -6, -10);

  // Layered tropical tree silhouettes
  const treeCount = 14;
  for (let i = 0; i < treeCount; i++) {
    const height = 18 + Math.random() * 12;
    const radius = 3.5 + Math.random() * 3.0;

    // Organic cone canopy
    const trunkGeo = new THREE.CylinderGeometry(0.3, 0.6, height, 8);
    const trunkMat = new THREE.MeshStandardMaterial({
      color: 0x070c14,
      roughness: 0.9,
      metalness: 0.1
    });
    const trunk = new THREE.Mesh(trunkGeo, trunkMat);

    const foliageGeo = new THREE.ConeGeometry(radius, height * 0.75, 8);
    const foliageMat = new THREE.MeshStandardMaterial({
      color: 0x051a14, // Deep nocturnal tropical forest green
      roughness: 0.85,
      metalness: 0.05
    });
    const foliage = new THREE.Mesh(foliageGeo, foliageMat);
    foliage.position.y = height * 0.45;

    const tree = new THREE.Group();
    tree.add(trunk);
    tree.add(foliage);

    const angle = (i / treeCount) * Math.PI * 2;
    const dist = 18 + Math.random() * 24;
    tree.position.set(
      Math.cos(angle) * dist,
      -height / 2,
      Math.sin(angle) * dist
    );
    tree.scale.setScalar(0.8 + Math.random() * 0.5);
    canopyGroup.add(tree);
  }
  group.add(canopyGroup);

  // 3. The Focused Tree Branch (Shot 02-03 Perch for Celepuk Sulawesi)
  const branchGroup = new THREE.Group();
  branchGroup.position.set(0, -2.4, 0);

  // Curved organic branch
  const branchCurve = new THREE.CatmullRomCurve3([
    new THREE.Vector3(-8.0, -1.2, -1.5),
    new THREE.Vector3(-3.0, 0.1, -0.4),
    new THREE.Vector3(0.0, 0.0, 0.0), // Perch spot
    new THREE.Vector3(3.5, -0.3, 0.6),
    new THREE.Vector3(7.5, -1.6, 1.8)
  ]);

  const branchGeo = new THREE.TubeGeometry(branchCurve, 32, 0.45, 12, false);
  const branchMat = new THREE.MeshStandardMaterial({
    color: 0x271c14, // Natural dark bark
    roughness: 0.95,
    metalness: 0.05
  });
  const branchMesh = new THREE.Mesh(branchGeo, branchMat);
  branchGroup.add(branchMesh);

  // Foliage cluster on branch tips
  for (let i = -1; i <= 1; i += 2) {
    const leafClusterGeo = new THREE.DodecahedronGeometry(0.85, 1);
    const leafClusterMat = new THREE.MeshStandardMaterial({
      color: 0x0d3822,
      roughness: 0.8,
      metalness: 0.1
    });
    const cluster = new THREE.Mesh(leafClusterGeo, leafClusterMat);
    cluster.position.set(i * 4.8, -0.6, i * 0.8);
    cluster.scale.set(1.4, 0.8, 1.1);
    branchGroup.add(cluster);
  }

  group.add(branchGroup);

  // Update loop for twinkling stars & subtle canopy breeze
  const update = (_progress: number, elapsed: number) => {
    // Subtle starfield twinkling
    starField.rotation.y = elapsed * 0.008;

    // Gentle canopy drift
    canopyGroup.children.forEach((tree, idx) => {
      tree.rotation.z = Math.sin(elapsed * 0.8 + idx) * 0.015;
    });

    // Branch slight weight settle
    branchMesh.rotation.z = Math.sin(elapsed * 0.6) * 0.005;
  };

  const dispose = () => {
    starGeo.dispose();
    starMat.dispose();
    branchGeo.dispose();
    branchMat.dispose();
  };

  return {
    group,
    starField,
    branchGroup,
    canopyGroup,
    update,
    dispose
  };
}
