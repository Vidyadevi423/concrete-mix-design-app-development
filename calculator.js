/**
 * Concrete Mix Design Calculator
 * Based on Indian Standard IS 10262:2009
 */

// Material densities (kg/m³) - typical values
const MATERIAL_DENSITIES = {
  cement: 1440,        // OPC cement
  water: 1000,        // Water
  sand: 2650,         // Fine aggregate (SSD)
  coarseAggregate: 2700, // Coarse aggregate (SSD)
  admixture: 1450     // Typical admixture
};

// Cost per kg (₹) - can be customized
const MATERIAL_COSTS = {
  cement: 8.0,        // ₹8 per kg
  water: 0.05,        // ₹0.05 per liter
  sand: 1.5,          // ₹1.5 per kg
  coarseAggregate: 1.2, // ₹1.2 per kg
  admixture: 250      // ₹250 per kg
};

// Grade data with default mix ratios
const GRADE_DATA = {
  '20': { ratio: '1:1.5:3', cementRatio: 1, sandRatio: 1.5, aggRatio: 3 },
  '40': { ratio: '1:1:2', cementRatio: 1, sandRatio: 1, aggRatio: 2 },
  '50': { ratio: '1:0.75:1.5', cementRatio: 1, sandRatio: 0.75, aggRatio: 1.5 },
  '80': { ratio: '1:0.5:1', cementRatio: 1, sandRatio: 0.5, aggRatio: 1 }
};

/**
 * Calculate target mean strength
 * fck = characteristic compressive strength
 * S = standard deviation
 * Target mean strength = fck + 1.65 * S
 */
function calculateTargetMeanStrength(fck, stdDev) {
  return fck + (1.65 * stdDev);
}

/**
 * Estimate water content based on slump and aggregate size
 * IS 10262:2009 guidelines
 */
function estimateWaterContent(slump, maxAggSize) {
  // Base water content for 25-50mm slump
  let waterContent = 208; // kg/m³ for 20mm aggregate
  
  if (maxAggSize === 40) {
    waterContent = 186;
  } else if (maxAggSize === 10) {
    waterContent = 227;
  }
  
  // Adjust for slump (every 25mm increase adds about 3% water)
  const slumpAdjustment = ((slump - 50) / 25) * 0.03 * waterContent;
  
  return Math.round(waterContent + slumpAdjustment);
}

/**
 * Calculate cement content from water content and w/c ratio
 */
function calculateCementContent(waterContent, wcRatio) {
  return Math.round(waterContent / wcRatio);
}

/**
 * Calculate admixture content
 */
function calculateAdmixtureContent(cementContent, admixturePercentage) {
  return (cementContent * admixturePercentage) / 100;
}

/**
 * Calculate aggregate content using absolute volume method
 */
function calculateAggregates(cementContent, waterContent, sandPercentage = 0.35) {
  // Absolute volume = mass / (density * 1000)
  const cementVolume = cementContent / MATERIAL_DENSITIES.cement;
  const waterVolume = waterContent / MATERIAL_DENSITIES.water;
  
  // Total aggregates volume (assuming 1 m³ concrete)
  // Volume = 1 - (cement + water + entrapped air)
  const entrappedAir = 0.02; // 2% for 20mm aggregate
  const totalAggregateVolume = 1 - (cementVolume + waterVolume + entrappedAir);
  
  // Sand volume (adjustable, typically 30-40%)
  const sandVolume = totalAggregateVolume * sandPercentage;
  const coarseVolume = totalAggregateVolume * (1 - sandPercentage);
  
  // Calculate masses
  const sandMass = Math.round(sandVolume * MATERIAL_DENSITIES.sand);
  const coarseMass = Math.round(coarseVolume * MATERIAL_DENSITIES.coarseAggregate);
  
  return { sandMass, coarseMass };
}

/**
 * Calculate total cost
 */
function calculateCost(cement, water, sand, coarseAgg, admixture) {
  const cementCost = cement * MATERIAL_COSTS.cement;
  const waterCost = water * MATERIAL_COSTS.water;
  const sandCost = sand * MATERIAL_COSTS.sand;
  const coarseCost = coarseAgg * MATERIAL_COSTS.coarseAggregate;
  const admixtureCost = admixture * MATERIAL_COSTS.admixture;
  
  return {
    total: cementCost + waterCost + sandCost + coarseCost + admixtureCost,
    breakdown: {
      cement: cementCost,
      water: waterCost,
      sand: sandCost,
      coarseAggregate: coarseCost,
      admixture: admixtureCost
    }
  };
}

/**
 * Main calculation function - calculates all mix design parameters
 */
function calculateMixDesign(inputs) {
  const { 
    fck = 20,           // Characteristic compressive strength (MPa)
    maxAggSize = 20,   // Maximum aggregate size (mm)
    slump = 50,        // Workability (mm)
    wcRatio = 0.5,     // Water-cement ratio
    admixturePercentage = 0,  // Admixture percentage of cement
    stdDev = 5         // Standard deviation
  } = inputs;
  
  // Step 1: Calculate target mean strength
  const targetMeanStrength = calculateTargetMeanStrength(fck, stdDev);
  
  // Step 2: Estimate water content
  const waterContent = estimateWaterContent(slump, maxAggSize);
  
  // Step 3: Calculate cement content
  const cementContent = calculateCementContent(waterContent, wcRatio);
  
  // Step 4: Calculate admixture content
  const admixtureContent = calculateAdmixtureContent(cementContent, admixturePercentage);
  
  // Step 5: Calculate aggregates
  const { sandMass, coarseMass } = calculateAggregates(cementContent, waterContent);
  
  // Step 6: Calculate cost
  const costResult = calculateCost(cementContent, waterContent, sandMass, coarseMass, admixtureContent);
  
  // Get mix ratio
  const gradeInfo = GRADE_DATA[fck.toString()] || GRADE_DATA['20'];
  
  return {
    // Input parameters
    inputs: {
      fck,
      maxAggSize,
      slump,
      wcRatio,
      admixturePercentage,
      stdDev
    },
    // Results
    results: {
      targetMeanStrength: targetMeanStrength.toFixed(2),
      waterContent: waterContent,
      cementContent: cementContent,
      admixtureContent: admixtureContent.toFixed(2),
      sandMass: sandMass,
      coarseMass: coarseMass,
      mixRatio: gradeInfo.ratio
    },
    // Cost
    cost: costResult,
    // For detailed breakdown
    detailed: {
      densities: MATERIAL_DENSITIES,
      costs: MATERIAL_COSTS
    }
  };
}

/**
 * Calculate for 1 bag (50kg) of cement
 */
function calculatePerBag(inputs) {
  const fullCalc = calculateMixDesign(inputs);
  const cementContent = fullCalc.results.cementContent;
  
  const bagRatio = 50 / cementContent;
  
  return {
    cement: 50,
    water: Math.round(fullCalc.results.waterContent * bagRatio),
    sand: Math.round(fullCalc.results.sandMass * bagRatio),
    coarseAggregate: Math.round(fullCalc.results.coarseMass * bagRatio),
    admixture: (fullCalc.results.admixtureContent * bagRatio).toFixed(2)
  };
}

/**
 * Validate input parameters
 */
function validateInputs(inputs) {
  const errors = [];
  
  if (!inputs.fck || inputs.fck < 10 || inputs.fck > 100) {
    errors.push("Invalid concrete grade");
  }
  if (!inputs.slump || inputs.slump < 25 || inputs.slump > 150) {
    errors.push("Invalid slump value (should be 25-150mm)");
  }
  if (!inputs.wcRatio || inputs.wcRatio < 0.25 || inputs.wcRatio > 0.65) {
    errors.push("Invalid W/C ratio (should be 0.25-0.65)");
  }
  if (!inputs.maxAggSize || ![10, 20, 40].includes(inputs.maxAggSize)) {
    errors.push("Invalid aggregate size (should be 10, 20, or 40mm)");
  }
  
  return errors;
}

// Export functions for use in HTML
if (typeof window !== 'undefined') {
  window.ConcreteCalculator = {
    calculateMixDesign,
    calculatePerBag,
    calculateTargetMeanStrength,
    estimateWaterContent,
    calculateCementContent,
    calculateAggregates,
    calculateCost,
    validateInputs,
    MATERIAL_DENSITIES,
    MATERIAL_COSTS,
    GRADE_DATA
  };
}

// For Node.js usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    calculateMixDesign,
    calculatePerBag,
    calculateTargetMeanStrength,
    estimateWaterContent,
    calculateCementContent,
    calculateAggregates,
    calculateCost,
    validateInputs,
    MATERIAL_DENSITIES,
    MATERIAL_COSTS,
    GRADE_DATA
  };
}
