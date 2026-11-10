import * as THREE from 'three';

export interface Story01CameraRig {
  camera: THREE.PerspectiveCamera;
  update: (progress: number, aspect: number) => void;
  dispose: () => void;
}

export function createCamera(width: number, height: number): Story01CameraRig {
  const camera = new THREE.PerspectiveCamera(46, width / height, 0.5, 400);

  const currentPos = new THREE.Vector3();
  const currentTarget = new THREE.Vector3();

  const update = (t: number, aspect: number) => {
    // Responsive FOV adjustment based on aspect ratio
    camera.aspect = aspect;
    if (aspect < 0.75) {
      camera.fov = 58;
    } else if (aspect < 1.15) {
      camera.fov = 50;
    } else {
      camera.fov = 46;
    }

    // -----------------------------------------------------------------------
    // SHOT 01: The Night Sky (0.00 -> 0.12)
    // Camera looks straight up into the starry sky, tilting down slowly
    // -----------------------------------------------------------------------
    if (t < 0.12) {
      const p = t / 0.12; // 0 to 1
      // Camera is on ground looking straight up at stars
      currentPos.set(0, -2.5, 8.0);
      // Target tilts from high sky (y = 50) down towards canopy top (y = 8)
      currentTarget.set(0, THREE.MathUtils.lerp(45.0, 8.0, p), -5.0);
    }
    // -----------------------------------------------------------------------
    // SHOT 02: Into the Wilderness (0.12 -> 0.25)
    // Camera descends through the canopy toward the focused branch
    // -----------------------------------------------------------------------
    else if (t < 0.25) {
      const p = (t - 0.12) / (0.25 - 0.12);
      currentPos.set(
        THREE.MathUtils.lerp(0.0, 1.2, p),
        THREE.MathUtils.lerp(-2.5, -1.0, p),
        THREE.MathUtils.lerp(8.0, 5.5, p)
      );
      currentTarget.set(0, THREE.MathUtils.lerp(8.0, -1.8, p), 0.0);
    }
    // -----------------------------------------------------------------------
    // SHOT 03: The Awakening (0.25 -> 0.42)
    // Camera dollies close to the perched owl at branch level
    // -----------------------------------------------------------------------
    else if (t < 0.42) {
      const p = (t - 0.25) / (0.42 - 0.25);
      currentPos.set(
        THREE.MathUtils.lerp(1.2, 0.4, p),
        THREE.MathUtils.lerp(-1.0, -0.6, p),
        THREE.MathUtils.lerp(5.5, 4.0, p)
      );
      currentTarget.set(0, -0.5, 0.0);
    }
    // -----------------------------------------------------------------------
    // SHOT 04: The First Light (0.42 -> 0.55)
    // Camera reframes to capture sunrise light hitting the owl and expanding wings
    // -----------------------------------------------------------------------
    else if (t < 0.55) {
      const p = (t - 0.42) / (0.55 - 0.42);
      currentPos.set(
        THREE.MathUtils.lerp(0.4, -0.8, p),
        THREE.MathUtils.lerp(-0.6, -0.2, p),
        THREE.MathUtils.lerp(4.0, 6.2, p)
      );
      currentTarget.set(0, 0.0, 0.0);
    }
    // -----------------------------------------------------------------------
    // SHOT 05: The Flight (0.55 -> 0.72)
    // Camera follows the ascending owl from behind and slightly below
    // -----------------------------------------------------------------------
    else if (t < 0.72) {
      const p = (t - 0.55) / (0.72 - 0.55);
      // Follows owl ascending from y = -1.5 to y = 5.0, z from 0 to -8
      const owlY = -1.5 + p * 6.5;
      const owlZ = p * -8.0;

      currentPos.set(0.0, owlY - 1.2, owlZ + 6.5);
      currentTarget.set(0.0, owlY + 0.5, owlZ);
    }
    // -----------------------------------------------------------------------
    // SHOT 06: The Transformation (0.72 -> 0.92)
    // Camera sweeps from behind owl in an orbital arc to the front
    // -----------------------------------------------------------------------
    else if (t < 0.92) {
      const p = (t - 0.72) / (0.92 - 0.72);
      // Orbit from behind (angle PI/2) to front (angle -PI/2)
      const angle = (1 - p) * Math.PI; // PI -> 0
      const radius = THREE.MathUtils.lerp(8.0, 32.0, p);

      const targetOwlY = THREE.MathUtils.lerp(5.0, 0.5, p);
      currentPos.set(
        Math.sin(angle) * 14.0,
        THREE.MathUtils.lerp(3.8, 1.5, p),
        THREE.MathUtils.lerp(-1.5, radius, p)
      );
      currentTarget.set(0, targetOwlY, 0);
    }
    // -----------------------------------------------------------------------
    // SHOT 07: The Emblem Manguni (0.92 -> 1.00)
    // Frontal majestic, stable view with optimal composition framing
    // -----------------------------------------------------------------------
    else {
      // Steady frontal presentation ready for handoff to Story 02
      currentPos.set(0, 1.5, 34.0);
      currentTarget.set(0, 0.5, 0.0);
    }

    camera.position.copy(currentPos);
    camera.lookAt(currentTarget);
    camera.updateProjectionMatrix();
  };

  const dispose = () => {
    // Clean up
  };

  return {
    camera,
    update,
    dispose
  };
}
