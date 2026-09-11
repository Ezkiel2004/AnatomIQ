/* ============================================================
   AnatomIQ – Anatomy Data (All 9 Body Systems)
   ============================================================ */

'use strict';

const AnatomyData = {
  systems: [
    {
      id: 'skeletal',
      name: 'Skeletal System',
      icon: '🦴',
      color: '#f59e0b',
      gradient: 'linear-gradient(135deg, #92400e, #f59e0b)',
      description: 'The skeletal system forms the structural framework of the body, providing shape, support, and protection for vital organs. It works with the muscular system to enable movement.',
      keyFacts: {
        'Bones in Adult': '206 bones',
        'Bone Cells': 'Osteocytes, Osteoblasts, Osteoclasts',
        'Main Mineral': 'Calcium & Phosphorus',
        'Longest Bone': 'Femur (thigh bone)',
        'Smallest Bone': 'Stapes (ear)',
      },
      structures: [
        { name: 'Skull', emoji: '💀', desc: 'Protects the brain and supports facial structures. Composed of 22 bones.' },
        { name: 'Vertebral Column', emoji: '🫀', desc: '33 vertebrae forming the spine, protecting the spinal cord.' },
        { name: 'Rib Cage', emoji: '🫁', desc: '12 pairs of ribs protecting the heart and lungs.' },
        { name: 'Femur', emoji: '🦵', desc: 'The longest and strongest bone in the human body.' },
        { name: 'Pelvis', emoji: '🦿', desc: 'Supports the spine and connects to the lower limbs.' },
      ],
      modules: 3, lessons: 12, completionRate: 85,
    },
    {
      id: 'muscular',
      name: 'Muscular System',
      icon: '💪',
      color: '#ef4444',
      gradient: 'linear-gradient(135deg, #7f1d1d, #ef4444)',
      description: 'The muscular system consists of over 600 muscles responsible for movement, posture maintenance, and heat production. Muscles work by contracting and relaxing.',
      keyFacts: {
        'Total Muscles': 'Over 600',
        'Types': 'Skeletal, Cardiac, Smooth',
        'Largest Muscle': 'Gluteus Maximus',
        'Smallest Muscle': 'Stapedius',
        'Strongest': 'Masseter (jaw)',
      },
      structures: [
        { name: 'Skeletal Muscle', emoji: '💪', desc: 'Voluntary muscles attached to bones for movement.' },
        { name: 'Cardiac Muscle', emoji: '🫀', desc: 'Involuntary, specialized heart muscle tissue.' },
        { name: 'Smooth Muscle', emoji: '🫁', desc: 'Involuntary muscles lining organs and blood vessels.' },
        { name: 'Biceps Brachii', emoji: '🦾', desc: 'Muscle in the upper arm that flexes the elbow.' },
        { name: 'Quadriceps', emoji: '🦵', desc: 'Group of four muscles at the front of the thigh.' },
      ],
      modules: 3, lessons: 10, completionRate: 70,
    },
    {
      id: 'circulatory',
      name: 'Circulatory System',
      icon: '🫀',
      color: '#dc2626',
      gradient: 'linear-gradient(135deg, #7f1d1d, #dc2626)',
      description: 'The circulatory system transports blood, oxygen, nutrients, hormones, and waste products throughout the body using the heart, blood vessels, and approximately 5 liters of blood.',
      keyFacts: {
        'Heart Rate': '60–100 beats per minute',
        'Blood Volume': '~5 liters in adults',
        'Blood Vessels': 'Arteries, Veins, Capillaries',
        'Red Blood Cells': '25 trillion cells',
        'Blood Types': 'A, B, AB, O (± Rh factor)',
      },
      structures: [
        { name: 'Heart', emoji: '🫀', desc: 'Muscular organ pumping blood through the circulatory system. Has 4 chambers.' },
        { name: 'Aorta', emoji: '🩸', desc: 'Largest artery; carries oxygenated blood from the heart.' },
        { name: 'Veins', emoji: '🩻', desc: 'Blood vessels carrying deoxygenated blood back to the heart.' },
        { name: 'Capillaries', emoji: '🔬', desc: 'Microscopic vessels enabling nutrient and gas exchange.' },
        { name: 'Blood', emoji: '🩸', desc: 'Liquid connective tissue: plasma, RBCs, WBCs, platelets.' },
      ],
      modules: 4, lessons: 14, completionRate: 60,
    },
    {
      id: 'respiratory',
      name: 'Respiratory System',
      icon: '🫁',
      color: '#3b82f6',
      gradient: 'linear-gradient(135deg, #1e3a8a, #3b82f6)',
      description: 'The respiratory system enables gas exchange — taking in oxygen and expelling carbon dioxide. The lungs are the primary organs, supported by airways and breathing muscles.',
      keyFacts: {
        'Breathing Rate': '12–20 breaths/min',
        'Lung Capacity': '~6 liters total',
        'Alveoli Count': '~480 million',
        'Trachea Length': '10–12 cm',
        'Gas Exchange': 'O₂ in, CO₂ out',
      },
      structures: [
        { name: 'Lungs', emoji: '🫁', desc: 'Paired organs for gas exchange, containing millions of alveoli.' },
        { name: 'Trachea', emoji: '🌬️', desc: 'Windpipe connecting the throat to the bronchi.' },
        { name: 'Diaphragm', emoji: '💨', desc: 'Dome-shaped muscle controlling breathing movements.' },
        { name: 'Bronchi', emoji: '🔀', desc: 'Branching airways leading from the trachea into each lung.' },
        { name: 'Alveoli', emoji: '🫧', desc: 'Tiny air sacs where gas exchange occurs in the lungs.' },
      ],
      modules: 3, lessons: 11, completionRate: 90,
    },
    {
      id: 'digestive',
      name: 'Digestive System',
      icon: '🥗',
      color: '#10b981',
      gradient: 'linear-gradient(135deg, #064e3b, #10b981)',
      description: 'The digestive system breaks down food into nutrients that can be absorbed and used for energy. It spans about 9 meters from mouth to anus.',
      keyFacts: {
        'GI Tract Length': '~9 meters',
        'Digestion Time': '24–72 hours',
        'Stomach Acid': 'pH 1.5–3.5 (HCl)',
        'Small Intestine': '6–7 meters long',
        'Large Intestine': '1.5 meters long',
      },
      structures: [
        { name: 'Stomach', emoji: '🫃', desc: 'J-shaped organ that stores and churns food with digestive acid.' },
        { name: 'Small Intestine', emoji: '🔄', desc: 'Primary site of nutrient absorption; 6–7 meters long.' },
        { name: 'Liver', emoji: '🟤', desc: 'Largest gland; produces bile, detoxifies blood, metabolizes nutrients.' },
        { name: 'Pancreas', emoji: '🫀', desc: 'Produces digestive enzymes and hormones (insulin, glucagon).' },
        { name: 'Large Intestine', emoji: '💧', desc: 'Absorbs water and forms solid waste for elimination.' },
      ],
      modules: 3, lessons: 13, completionRate: 75,
    },
    {
      id: 'urinary',
      name: 'Urinary System',
      icon: '🫘',
      color: '#f59e0b',
      gradient: 'linear-gradient(135deg, #78350f, #f59e0b)',
      description: 'The urinary system filters blood, removes metabolic waste products, regulates fluid balance and blood pressure, and produces urine for elimination.',
      keyFacts: {
        'Urine Production': '~1.5 liters/day',
        'Kidney Weight': '~150 grams each',
        'Nephrons per Kidney': '~1 million',
        'Bladder Capacity': '300–500 mL',
        'Filtration Rate': '125 mL/min',
      },
      structures: [
        { name: 'Kidneys', emoji: '🫘', desc: 'Bean-shaped organs that filter blood and produce urine.' },
        { name: 'Ureters', emoji: '🔗', desc: 'Tubes transporting urine from kidneys to the bladder.' },
        { name: 'Urinary Bladder', emoji: '💧', desc: 'Elastic sac storing urine until elimination.' },
        { name: 'Urethra', emoji: '🚰', desc: 'Tube through which urine exits the body.' },
        { name: 'Nephron', emoji: '🔬', desc: 'Functional unit of the kidney; each kidney has ~1 million.' },
      ],
      modules: 2, lessons: 8, completionRate: 50,
    },
    {
      id: 'nervous',
      name: 'Nervous System',
      icon: '🧠',
      color: '#8b5cf6',
      gradient: 'linear-gradient(135deg, #4c1d95, #8b5cf6)',
      description: 'The nervous system coordinates body activities by transmitting signals between different body parts using neurons. It consists of the central and peripheral nervous systems.',
      keyFacts: {
        'Brain Weight': '~1.4 kg',
        'Neurons': '~86 billion',
        'Signal Speed': 'Up to 120 m/s',
        'Spinal Cord Length': '~45 cm',
        'Nerve Pairs': '12 cranial, 31 spinal',
      },
      structures: [
        { name: 'Brain', emoji: '🧠', desc: 'Control center of the body; consists of cerebrum, cerebellum, brainstem.' },
        { name: 'Spinal Cord', emoji: '🦴', desc: 'Nerve highway between brain and body; enables reflexes.' },
        { name: 'Neurons', emoji: '⚡', desc: 'Specialized cells transmitting nerve impulses (signals).' },
        { name: 'Cerebrum', emoji: '🧠', desc: 'Largest brain region; controls thought, speech, senses, and movement.' },
        { name: 'Cerebellum', emoji: '🎯', desc: 'Coordinates balance, posture, and fine motor movements.' },
      ],
      modules: 4, lessons: 16, completionRate: 40,
    },
    {
      id: 'reproductive',
      name: 'Reproductive System',
      icon: '🌸',
      color: '#ec4899',
      gradient: 'linear-gradient(135deg, #831843, #ec4899)',
      description: 'The reproductive system is responsible for sexual reproduction and production of sex hormones. It differs between biological males and females.',
      keyFacts: {
        'Sperm Production': '~300 million/day',
        'Egg Cells': '~1–2 million at birth',
        'Menstrual Cycle': '~28 days average',
        'Gestation Period': '~40 weeks',
        'Sex Hormones': 'Estrogen, Progesterone, Testosterone',
      },
      structures: [
        { name: 'Gonads', emoji: '🌸', desc: 'Primary reproductive organs (ovaries/testes) producing gametes and hormones.' },
        { name: 'Uterus', emoji: '🫀', desc: 'Hollow muscular organ where fetal development occurs.' },
        { name: 'Ovaries', emoji: '🥚', desc: 'Female gonads producing eggs (ova) and sex hormones.' },
        { name: 'Testes', emoji: '🔵', desc: 'Male gonads producing sperm and testosterone.' },
        { name: 'Placenta', emoji: '🌿', desc: 'Temporary organ providing nutrients to the developing fetus.' },
      ],
      modules: 2, lessons: 8, completionRate: 30,
    },
    {
      id: 'endocrine',
      name: 'Endocrine System',
      icon: '⚗️',
      color: '#14b8a6',
      gradient: 'linear-gradient(135deg, #134e4a, #14b8a6)',
      description: 'The endocrine system regulates body functions through hormones secreted by glands directly into the bloodstream. It controls metabolism, growth, mood, and reproduction.',
      keyFacts: {
        'Major Glands': '9 major endocrine glands',
        'Master Gland': 'Pituitary (hypothalamus controls it)',
        'Insulin Source': 'Pancreas (beta cells)',
        'Stress Hormone': 'Cortisol (adrenal gland)',
        'Growth Hormone': 'Produced by pituitary',
      },
      structures: [
        { name: 'Pituitary Gland', emoji: '⚗️', desc: '"Master gland" controlling other endocrine glands; pea-sized.' },
        { name: 'Thyroid Gland', emoji: '🦋', desc: 'Butterfly-shaped gland regulating metabolism and energy.' },
        { name: 'Adrenal Glands', emoji: '⚡', desc: 'Sit atop kidneys; produce adrenaline and cortisol.' },
        { name: 'Pancreas', emoji: '🫀', desc: 'Produces insulin and glucagon for blood sugar regulation.' },
        { name: 'Hypothalamus', emoji: '🧠', desc: 'Brain region linking nervous and endocrine systems.' },
      ],
      modules: 3, lessons: 10, completionRate: 25,
    },
  ],

  getSystem(id) {
    return this.systems.find(s => s.id === id);
  },

  getAllSystems() {
    return this.systems;
  }
};

/// ── Anatomy 3D Viewer Bridge (Delegates to assets/js/anatomy-three.js) ──
if (typeof window.AnatomyViewer === 'undefined') {
  window.AnatomyViewer = {
    init(id) { console.info('[AnatomIQ] Initializing Three.js WebGL AnatomyViewer...'); },
    setSystem(s) {},
    zoomIn() {},
    zoomOut() {},
    resetView() {},
    toggleLabels() { return true; },
    toggleHighlight() { return false; },
    setLayer() {}
  };
}
