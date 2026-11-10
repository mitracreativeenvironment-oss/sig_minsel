import * as THREE from 'three';

export interface Story01Lighting {
  ambientLight: THREE.AmbientLight;
  moonLight: THREE.DirectionalLight;
  sunLight: THREE.DirectionalLight;
  rimLight: THREE.DirectionalLight;
  hemiLight: THREE.HemisphereLight;
  update: (progress: number) => void;
  dispose: () => void;
}

export function createLighting(scene: THREE.Scene): Story01Lighting {
  // 1. Ambient Light (Starts very dim nocturnal, warms up during sunrise)
  const ambientLight = new THREE.AmbientLight(0x0f172a, 1.2);
  scene.add(ambientLight);

  // 2. Moonlight (Cool silver-cyan, active during Shot 01-03)
  const moonLight = new THREE.DirectionalLight(0xc7d2fe, 1.8);
  moonLight.position.set(-25, 45, -15);
  scene.add(moonLight);

  // 3. Sunrise Key Light (Warm golden dawn, awakens at Shot 04)
  const sunLight = new THREE.DirectionalLight(0xfef08a, 0.0); // Starts dark
  sunLight.position.set(35, 20, 25);
  scene.add(sunLight);

  // 4. Rim Light (Golden edge highlight for feathers and emblem)
  const rimLight = new THREE.DirectionalLight(0xf59e0b, 0.0);
  rimLight.position.set(-30, 20, -25);
  scene.add(rimLight);

  // 5. Hemisphere Sky Fill
  const hemiLight = new THREE.HemisphereLight(0x0f172a, 0x020617, 1.0);
  scene.add(hemiLight);

  // Update lighting smoothly based on progress (0.0 to 1.0 within Story 01)
  const update = (t: number) => {
    // SHOT 01-03 (Night Sky to Perched Awakening): Moonlight dominates
    if (t < 0.42) {
      moonLight.intensity = 1.8;
      sunLight.intensity = 0.0;
      rimLight.intensity = 0.0;
      ambientLight.intensity = 1.2;
      ambientLight.color.setHex(0x0f172a);
      hemiLight.intensity = 1.0;
    }
    // SHOT 04 (The First Light, 0.42 -> 0.55): Dawn transitions smoothly
    else if (t < 0.55) {
      const dawnFactor = (t - 0.42) / (0.55 - 0.42); // 0 to 1
      moonLight.intensity = THREE.MathUtils.lerp(1.8, 0.4, dawnFactor);
      sunLight.intensity = THREE.MathUtils.lerp(0.0, 3.2, dawnFactor);
      rimLight.intensity = THREE.MathUtils.lerp(0.0, 2.2, dawnFactor);
      ambientLight.intensity = THREE.MathUtils.lerp(1.2, 2.0, dawnFactor);
      ambientLight.color.lerpColors(new THREE.Color(0x0f172a), new THREE.Color(0x27272a), dawnFactor);
      hemiLight.color.lerpColors(new THREE.Color(0x0f172a), new THREE.Color(0x38bdf8), dawnFactor);
      hemiLight.groundColor.lerpColors(new THREE.Color(0x020617), new THREE.Color(0x78350f), dawnFactor);
    }
    // SHOT 05-07 (Flight & Transformation, 0.55 -> 1.00): Full glorious golden sunrise
    else {
      moonLight.intensity = 0.2;
      sunLight.intensity = 3.6;
      rimLight.intensity = 2.6;
      ambientLight.intensity = 2.2;
      ambientLight.color.setHex(0x334155);
      hemiLight.color.setHex(0x38bdf8);
      hemiLight.groundColor.setHex(0x78350f);
    }
  };

  const dispose = () => {
    scene.remove(ambientLight);
    scene.remove(moonLight);
    scene.remove(sunLight);
    scene.remove(rimLight);
    scene.remove(hemiLight);
  };

  return {
    ambientLight,
    moonLight,
    sunLight,
    rimLight,
    hemiLight,
    update,
    dispose
  };
}
