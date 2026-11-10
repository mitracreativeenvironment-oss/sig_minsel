import * as THREE from 'three';

export interface Story01OwlCharacter {
  rootGroup: THREE.Group;
  realisticOwlGroup: THREE.Group;
  emblemManguniGroup: THREE.Group;
  leftWingGroup: THREE.Group;
  rightWingGroup: THREE.Group;
  owlHead: THREE.Group;
  leftEyeLid: THREE.Mesh;
  rightEyeLid: THREE.Mesh;
  talonsGroup: THREE.Group;
  // Official Emblem Components for Story 02 Continuity
  emblemBodyMesh: THREE.Mesh;
  emblemWingLeftMesh: THREE.Mesh;
  emblemWingRightMesh: THREE.Mesh;
  update: (progress: number, elapsed: number) => void;
  dispose: () => void;
}

export function createOwlCharacter(): Story01OwlCharacter {
  const rootGroup = new THREE.Group();

  // =========================================================================
  // 1. REALISTIC SULAWESI OWL (Celepuk Sulawesi / Otus manadensis)
  // =========================================================================
  const realisticOwlGroup = new THREE.Group();
  realisticOwlGroup.position.set(0, -1.9, 0); // Perched on the branch at y = -1.9
  rootGroup.add(realisticOwlGroup);

  // Materials for Celepuk Sulawesi (Natural mottled brown, ochre, and bark plumage)
  const bodyPlumageMat = new THREE.MeshStandardMaterial({
    color: 0x854d27, // Warm Sulawesi earthy brown
    roughness: 0.85,
    metalness: 0.08
  });

  const breastPlumageMat = new THREE.MeshStandardMaterial({
    color: 0xb5784c, // Lighter ochre-buff underparts with barring
    roughness: 0.9,
    metalness: 0.05
  });

  const beakMat = new THREE.MeshStandardMaterial({
    color: 0xca8a04,
    roughness: 0.35,
    metalness: 0.2
  });

  const eyeIrisMat = new THREE.MeshBasicMaterial({
    color: 0xfacc15 // Golden amber iris characteristic of Celepuk Sulawesi
  });

  const eyePupilMat = new THREE.MeshBasicMaterial({
    color: 0x020617 // Deep black nocturnal pupil
  });

  const eyeLidMat = new THREE.MeshStandardMaterial({
    color: 0x6e3d1d,
    roughness: 0.9
  });

  // Torso / Body (Owl_Body)
  const bodyGeo = new THREE.CapsuleGeometry(0.75, 1.2, 16, 24);
  const bodyMesh = new THREE.Mesh(bodyGeo, bodyPlumageMat);
  bodyMesh.position.set(0, 1.1, 0);
  bodyMesh.rotation.x = 0.15; // Natural perching forward lean
  realisticOwlGroup.add(bodyMesh);

  // Breast bib
  const breastGeo = new THREE.SphereGeometry(0.68, 16, 16);
  const breastMesh = new THREE.Mesh(breastGeo, breastPlumageMat);
  breastMesh.position.set(0, 1.15, 0.28);
  breastMesh.scale.set(0.9, 1.1, 0.5);
  realisticOwlGroup.add(breastMesh);

  // Tail (Owl_Tail)
  const tailGeo = new THREE.BoxGeometry(0.6, 0.8, 0.15);
  const tailMesh = new THREE.Mesh(tailGeo, bodyPlumageMat);
  tailMesh.position.set(0, 0.3, -0.6);
  tailMesh.rotation.x = -0.3;
  realisticOwlGroup.add(tailMesh);

  // Head Assembly (Owl_Head)
  const owlHead = new THREE.Group();
  owlHead.position.set(0, 2.05, 0.15);

  const headGeo = new THREE.SphereGeometry(0.65, 20, 20);
  const headMesh = new THREE.Mesh(headGeo, bodyPlumageMat);
  owlHead.add(headMesh);

  // Ear Tufts (Prominent characteristic of Celepuk Sulawesi)
  const tuftGeo = new THREE.ConeGeometry(0.2, 0.6, 8);
  const leftTuft = new THREE.Mesh(tuftGeo, bodyPlumageMat);
  leftTuft.position.set(-0.38, 0.62, 0.05);
  leftTuft.rotation.set(-0.2, 0, 0.35);
  owlHead.add(leftTuft);

  const rightTuft = new THREE.Mesh(tuftGeo, bodyPlumageMat);
  rightTuft.position.set(0.38, 0.62, 0.05);
  rightTuft.rotation.set(-0.2, 0, -0.35);
  owlHead.add(rightTuft);

  // Beak
  const beakGeo = new THREE.ConeGeometry(0.12, 0.35, 8);
  beakGeo.rotateX(Math.PI / 2);
  const beakMesh = new THREE.Mesh(beakGeo, beakMat);
  beakMesh.position.set(0, -0.08, 0.66);
  owlHead.add(beakMesh);

  // Eyes (Forward-Facing Facial Disk)
  function createOwlEye(isLeft: boolean) {
    const eyeGroup = new THREE.Group();
    const xSign = isLeft ? -1 : 1;
    eyeGroup.position.set(xSign * 0.28, 0.08, 0.52);

    // Golden Iris
    const irisGeo = new THREE.CircleGeometry(0.19, 24);
    const iris = new THREE.Mesh(irisGeo, eyeIrisMat);
    eyeGroup.add(iris);

    // Pupil
    const pupilGeo = new THREE.CircleGeometry(0.11, 24);
    const pupil = new THREE.Mesh(pupilGeo, eyePupilMat);
    pupil.position.z = 0.005;
    eyeGroup.add(pupil);

    // Specular Highlight
    const glintGeo = new THREE.CircleGeometry(0.035, 12);
    const glint = new THREE.Mesh(glintGeo, new THREE.MeshBasicMaterial({ color: 0xffffff }));
    glint.position.set(0.04, 0.04, 0.01);
    eyeGroup.add(glint);

    // Eyelid (Upper eyelid that opens from closed in Shot 03)
    const lidGeo = new THREE.SphereGeometry(0.22, 16, 16, 0, Math.PI * 2, 0, Math.PI / 2);
    lidGeo.rotateX(Math.PI / 2);
    const lid = new THREE.Mesh(lidGeo, eyeLidMat);
    lid.position.z = 0.01;
    eyeGroup.add(lid);

    return { eyeGroup, lid };
  }

  const { eyeGroup: leftEye, lid: leftEyeLid } = createOwlEye(true);
  owlHead.add(leftEye);

  const { eyeGroup: rightEye, lid: rightEyeLid } = createOwlEye(false);
  owlHead.add(rightEye);

  realisticOwlGroup.add(owlHead);

  // Wings (Anatomical Left & Right Wings with folding & flight flap pivot)
  const leftWingGroup = new THREE.Group();
  leftWingGroup.position.set(-0.7, 1.4, 0.0);

  const wingShape = new THREE.Shape();
  wingShape.moveTo(0, 0);
  wingShape.bezierCurveTo(-1.8, 0.4, -3.2, 0.1, -4.5, -0.8);
  wingShape.bezierCurveTo(-3.5, -1.8, -1.8, -2.4, 0, -2.0);
  wingShape.closePath();

  const wingGeo = new THREE.ExtrudeGeometry(wingShape, { depth: 0.15, bevelEnabled: true, bevelThickness: 0.08, bevelSize: 0.05 });
  const leftWingMesh = new THREE.Mesh(wingGeo, bodyPlumageMat);
  leftWingGroup.add(leftWingMesh);
  realisticOwlGroup.add(leftWingGroup);

  const rightWingGroup = new THREE.Group();
  rightWingGroup.position.set(0.7, 1.4, 0.0);
  const rightWingMesh = new THREE.Mesh(wingGeo, bodyPlumageMat);
  rightWingMesh.scale.x = -1; // Symmetrical mirror
  rightWingGroup.add(rightWingMesh);
  realisticOwlGroup.add(rightWingGroup);

  // Talons (Owl_Talons gripping branch)
  const talonsGroup = new THREE.Group();
  talonsGroup.position.set(0, 0.1, 0);

  const clawMat = new THREE.MeshStandardMaterial({ color: 0x332211, roughness: 0.6 });
  for (let s of [-0.28, 0.28]) {
    for (let c = -1; c <= 1; c++) {
      const clawGeo = new THREE.ConeGeometry(0.04, 0.25, 8);
      clawGeo.rotateX(Math.PI / 3);
      const claw = new THREE.Mesh(clawGeo, clawMat);
      claw.position.set(s + c * 0.08, 0.02, 0.12);
      talonsGroup.add(claw);
    }
  }
  realisticOwlGroup.add(talonsGroup);

  // =========================================================================
  // 2. OFFICIAL EMBLEM MANGUNI (From Lambang_Kabupaten_Minahasa_Selatan.png)
  // =========================================================================
  const emblemManguniGroup = new THREE.Group();
  emblemManguniGroup.visible = false; // Hidden until Shot 06 Transformation
  rootGroup.add(emblemManguniGroup);

  const textureLoader = new THREE.TextureLoader();

  const texBodyHead = textureLoader.load('/images/minsel_logo_3d/layer_body_head.png');
  texBodyHead.colorSpace = THREE.SRGBColorSpace;

  const texWingLeft = textureLoader.load('/images/minsel_logo_3d/layer_wing_left.png');
  texWingLeft.colorSpace = THREE.SRGBColorSpace;

  const texWingRight = textureLoader.load('/images/minsel_logo_3d/layer_wing_right.png');
  texWingRight.colorSpace = THREE.SRGBColorSpace;

  const planeW = 20.0;
  const planeH = planeW * (1227 / 929);
  const emblemGeo = new THREE.PlaneGeometry(planeW, planeH);

  function createEmblemMaterial(tex: THREE.Texture) {
    return new THREE.MeshStandardMaterial({
      map: tex,
      transparent: true,
      alphaTest: 0.02,
      roughness: 0.35,
      metalness: 0.25,
      side: THREE.DoubleSide
    });
  }

  const emblemBodyMat = createEmblemMaterial(texBodyHead);
  const emblemBodyMesh = new THREE.Mesh(emblemGeo, emblemBodyMat);
  emblemManguniGroup.add(emblemBodyMesh);

  const emblemWingLeftMat = createEmblemMaterial(texWingLeft);
  const emblemWingLeftMesh = new THREE.Mesh(emblemGeo, emblemWingLeftMat);
  emblemWingLeftMesh.position.z = 0.05;
  emblemManguniGroup.add(emblemWingLeftMesh);

  const emblemWingRightMat = createEmblemMaterial(texWingRight);
  const emblemWingRightMesh = new THREE.Mesh(emblemGeo, emblemWingRightMat);
  emblemWingRightMesh.position.z = 0.05;
  emblemManguniGroup.add(emblemWingRightMesh);

  // =========================================================================
  // UPDATE LOOP FOR STORY 01 ANIMATION
  // =========================================================================
  const update = (t: number, elapsed: number) => {
    // -----------------------------------------------------------------------
    // SHOT 01 & 02: Owl is resting peacefully in the dark night
    // -----------------------------------------------------------------------
    if (t < 0.25) {
      leftEyeLid.scale.y = 1.0; // Eyes completely closed
      rightEyeLid.scale.y = 1.0;
      owlHead.rotation.set(0, 0, 0);

      // Folded wings resting at side
      leftWingGroup.rotation.set(0.1, 0.4, 0.7);
      leftWingGroup.position.set(-0.7, 1.4, -0.2);
      rightWingGroup.rotation.set(0.1, -0.4, -0.7);
      rightWingGroup.position.set(0.7, 1.4, -0.2);

      realisticOwlGroup.position.set(0, -1.9, 0);
      realisticOwlGroup.rotation.set(0, 0, 0);
      realisticOwlGroup.visible = true;
      emblemManguniGroup.visible = false;
    }
    // -----------------------------------------------------------------------
    // SHOT 03: The Awakening (0.25 -> 0.42)
    // -----------------------------------------------------------------------
    else if (t < 0.42) {
      const awakenProgress = (t - 0.25) / (0.42 - 0.25); // 0 to 1

      // Eyelids open dramatically
      const lidOpen = Math.max(0, 1.0 - awakenProgress * 1.3);
      leftEyeLid.scale.y = lidOpen;
      rightEyeLid.scale.y = lidOpen;

      // Head twitches slightly then looks toward horizon
      owlHead.rotation.y = Math.sin(awakenProgress * Math.PI) * 0.35;
      owlHead.rotation.x = -awakenProgress * 0.15; // Lifts gaze toward sky

      // Wings remain folded but tense up slightly
      leftWingGroup.rotation.set(0.1, 0.4, 0.7);
      rightWingGroup.rotation.set(0.1, -0.4, -0.7);

      realisticOwlGroup.position.set(0, -1.9, 0);
      realisticOwlGroup.rotation.set(0, 0, 0);
      realisticOwlGroup.visible = true;
      emblemManguniGroup.visible = false;
    }
    // -----------------------------------------------------------------------
    // SHOT 04: The First Light (0.42 -> 0.55)
    // -----------------------------------------------------------------------
    else if (t < 0.55) {
      const dawnProgress = (t - 0.42) / (0.55 - 0.42);

      leftEyeLid.scale.y = 0.0; // Eyes wide open
      rightEyeLid.scale.y = 0.0;
      owlHead.rotation.y = 0;
      owlHead.rotation.x = -0.2;

      // Owl lifts torso upwards from crouch
      realisticOwlGroup.position.y = -1.9 + dawnProgress * 0.4;

      // Wings unfurl and spread wide in preparation for takeoff
      const wingSpread = dawnProgress;
      leftWingGroup.rotation.set(0.1 * (1 - wingSpread), 0.4 * (1 - wingSpread), 0.7 * (1 - wingSpread));
      leftWingGroup.position.set(-0.7, 1.4, 0.0);
      rightWingGroup.rotation.set(0.1 * (1 - wingSpread), -0.4 * (1 - wingSpread), -0.7 * (1 - wingSpread));
      rightWingGroup.position.set(0.7, 1.4, 0.0);

      realisticOwlGroup.visible = true;
      emblemManguniGroup.visible = false;
    }
    // -----------------------------------------------------------------------
    // SHOT 05: The Flight (0.55 -> 0.72)
    // -----------------------------------------------------------------------
    else if (t < 0.72) {
      const flightProgress = (t - 0.55) / (0.72 - 0.55);

      leftEyeLid.scale.y = 0.0;
      rightEyeLid.scale.y = 0.0;

      // Flight trajectory: ascends from branch (y = -1.5) up to sky (y = 5.0), moving forward along z
      realisticOwlGroup.position.y = -1.5 + flightProgress * 6.5;
      realisticOwlGroup.position.z = flightProgress * -8.0;
      realisticOwlGroup.rotation.x = 0.25; // Aerodynamic forward flight pitch

      // Cyclic wing flapping
      const flapAngle = Math.sin(elapsed * 9.0) * 0.65;
      leftWingGroup.rotation.z = flapAngle;
      rightWingGroup.rotation.z = -flapAngle;

      // Talons tucked into feathers during flight
      talonsGroup.position.y = -0.2;
      talonsGroup.rotation.x = -0.8;

      realisticOwlGroup.visible = true;
      emblemManguniGroup.visible = false;
    }
    // -----------------------------------------------------------------------
    // SHOT 06: The Transformation (0.72 -> 0.92)
    // -----------------------------------------------------------------------
    else if (t < 0.92) {
      const morphProgress = (t - 0.72) / (0.92 - 0.72); // 0 to 1

      // Flight slows, wings spread wide facing front
      leftEyeLid.scale.y = 0.0;
      rightEyeLid.scale.y = 0.0;
      leftWingGroup.rotation.z = Math.sin(elapsed * 2.0) * 0.1; // Gentle gliding spread
      rightWingGroup.rotation.z = -Math.sin(elapsed * 2.0) * 0.1;

      realisticOwlGroup.position.set(0, 5.0 - morphProgress * 4.5, -8.0 + morphProgress * 8.0);
      realisticOwlGroup.rotation.x = THREE.MathUtils.lerp(0.25, 0.0, morphProgress);

      // Smooth cross-fade between realistic owl and official 3D Manguni emblem
      bodyPlumageMat.opacity = Math.max(0, 1.0 - morphProgress * 1.5);
      bodyPlumageMat.transparent = true;
      breastPlumageMat.opacity = Math.max(0, 1.0 - morphProgress * 1.5);
      breastPlumageMat.transparent = true;

      emblemManguniGroup.visible = true;
      emblemManguniGroup.position.set(0, 0.5, 0);
      emblemManguniGroup.scale.setScalar(0.25 + morphProgress * 0.75);
      emblemBodyMat.opacity = Math.min(1.0, morphProgress * 1.5);
      emblemWingLeftMat.opacity = Math.min(1.0, morphProgress * 1.5);
      emblemWingRightMat.opacity = Math.min(1.0, morphProgress * 1.5);
    }
    // -----------------------------------------------------------------------
    // SHOT 07: The Emblem Manguni (0.92 -> 1.00)
    // -----------------------------------------------------------------------
    else {
      realisticOwlGroup.visible = false;

      // Official 3D Manguni is fully formed, proud and active in golden dawn
      emblemManguniGroup.visible = true;
      emblemManguniGroup.position.set(0, 0.5, 0);
      emblemManguniGroup.scale.setScalar(1.0);
      emblemBodyMat.opacity = 1.0;
      emblemWingLeftMat.opacity = 1.0;
      emblemWingRightMat.opacity = 1.0;

      // Gentle majestic floating levitation, continuing flight readiness for Story 02
      const levitate = Math.sin(elapsed * 1.8) * 0.25;
      emblemManguniGroup.position.y = 0.5 + levitate;
    }
  };

  const dispose = () => {
    bodyGeo.dispose();
    breastGeo.dispose();
    headGeo.dispose();
    tuftGeo.dispose();
    beakGeo.dispose();
    wingGeo.dispose();
    emblemGeo.dispose();

    bodyPlumageMat.dispose();
    breastPlumageMat.dispose();
    beakMat.dispose();
    eyeIrisMat.dispose();
    eyePupilMat.dispose();
    eyeLidMat.dispose();
    emblemBodyMat.dispose();
    emblemWingLeftMat.dispose();
    emblemWingRightMat.dispose();
  };

  return {
    rootGroup,
    realisticOwlGroup,
    emblemManguniGroup,
    leftWingGroup,
    rightWingGroup,
    owlHead,
    leftEyeLid,
    rightEyeLid,
    talonsGroup,
    emblemBodyMesh,
    emblemWingLeftMesh,
    emblemWingRightMesh,
    update,
    dispose
  };
}
