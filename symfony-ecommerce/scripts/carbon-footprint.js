#!/usr/bin/env node

/**
 * Script de mesure de l'empreinte carbone par page
 * Utilise co2.js (@tgwf/co2) pour estimer les émissions CO2
 * basées sur le poids de chaque page (transfert réseau).
 *
 * Usage :
 *   node scripts/carbon-footprint.js [--url http://127.0.0.1:8000] [--json] [--verbose]
 *                                   [--product-id 1] [--category-id 1]
 *
 * Les URLs mesurées sont une liste définie dans ce fichier (pas un crawl du site).
 * Pour les pages avec paramètres (fiche produit, catégorie), les IDs par défaut
 * sont 1 ; adaptez-les si votre base locale n’a pas ces enregistrements.
 */

const { co2 } = require("@tgwf/co2");

const BASE_URL = (() => {
  const idx = process.argv.indexOf("--url");
  return idx !== -1 ? process.argv[idx + 1] : "http://127.0.0.1:8000";
})();
const OUTPUT_JSON = process.argv.includes("--json");
const VERBOSE = process.argv.includes("--verbose");

const PRODUCT_ID = (() => {
  const idx = process.argv.indexOf("--product-id");
  return idx !== -1 ? process.argv[idx + 1] : "1";
})();
const CATEGORY_ID = (() => {
  const idx = process.argv.indexOf("--category-id");
  return idx !== -1 ? process.argv[idx + 1] : "1";
})();

// Pages publiques (boutique) — GET, sans compte requis pour l’affichage de base
const PAGES = [
  { name: "Accueil", path: "/" },
  { name: "Liste produits", path: "/product" },
  { name: "Liste produits (/products)", path: "/products" },
  { name: "Recherche produits (JSON)", path: "/product/search?q=chat" },
  { name: `Fiche produit (id=${PRODUCT_ID})`, path: `/product/details/${PRODUCT_ID}` },
  { name: `Catégorie (id=${CATEGORY_ID})`, path: `/category/${CATEGORY_ID}` },
  { name: "Index catégories CRUD", path: "/category/" },
  { name: "Liste catégories (vitrine)", path: "/categories" },
  { name: "Panier", path: "/cart" },
  { name: "Checkout", path: "/cart/checkout" },
  { name: "À propos", path: "/about" },
  { name: "Contact", path: "/contact" },
  { name: "Confidentialité", path: "/privacy" },
  { name: "CGV", path: "/terms" },
  { name: "Promotions", path: "/promotions" },
  { name: "Connexion", path: "/login" },
  { name: "Inscription", path: "/register" },
  { name: "Mot de passe oublié", path: "/forgot-password" },
  { name: "Sitemap XML", path: "/sitemap.xml" },
  { name: "Robots.txt", path: "/robots.txt" },
];

const swd = new co2({ model: "swd" }); // Sustainable Web Design model

/**
 * Récupère une page et retourne le nombre d'octets transférés.
 * Comptabilise le corps de la réponse + les en-têtes HTTP estimés.
 */
async function measurePage(name, path) {
  const url = `${BASE_URL}${path}`;

  try {
    const response = await fetch(url, {
      redirect: "follow",
      signal: AbortSignal.timeout(15000),
    });

    const body = await response.arrayBuffer();
    const bodyBytes = body.byteLength;

    // Estimation des en-têtes HTTP (non exposés par fetch)
    const headersEstimate = 500;
    const totalBytes = bodyBytes + headersEstimate;

    const co2Grams = swd.perByte(totalBytes);
    const status = response.status;

    return {
      name,
      path,
      url,
      status,
      bodyBytes,
      totalBytes,
      co2Grams,
      error: null,
    };
  } catch (err) {
    return {
      name,
      path,
      url,
      status: null,
      bodyBytes: 0,
      totalBytes: 0,
      co2Grams: 0,
      error: err.message,
    };
  }
}

function formatBytes(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function formatCO2(grams) {
  if (grams < 0.001) return `${(grams * 1000000).toFixed(2)} µg CO2`;
  if (grams < 1) return `${(grams * 1000).toFixed(3)} mg CO2`;
  return `${grams.toFixed(4)} g CO2`;
}

function getRating(grams) {
  // Seuils basés sur les recommandations de websitecarbon.com
  // Median page : ~0.5g CO2, Green page : <0.1g CO2
  if (grams <= 0.095) return { label: "A+", color: "\x1b[32m" }; // vert vif
  if (grams <= 0.185) return { label: "A ", color: "\x1b[32m" }; // vert
  if (grams <= 0.34) return { label: "B ", color: "\x1b[33m" }; // jaune
  if (grams <= 0.49) return { label: "C ", color: "\x1b[33m" }; // jaune
  if (grams <= 0.65) return { label: "D ", color: "\x1b[31m" }; // rouge
  if (grams <= 0.85) return { label: "E ", color: "\x1b[31m" }; // rouge
  return { label: "F ", color: "\x1b[31m" }; // rouge foncé
}

async function run() {
  if (!OUTPUT_JSON) {
    console.log("\n=== Mesure de l'empreinte carbone par page ===");
    console.log(`Serveur : ${BASE_URL}`);
    console.log(`Modèle  : Sustainable Web Design (SWD)\n`);
  }

  const results = [];
  for (const page of PAGES) {
    if (!OUTPUT_JSON)
      process.stdout.write(`Analyse ${page.name.padEnd(34)}...`);
    const result = await measurePage(page.name, page.path);
    results.push(result);
    if (!OUTPUT_JSON) console.log(" OK");
  }

  if (OUTPUT_JSON) {
    const output = results.map(
      ({ name, path, status, bodyBytes, totalBytes, co2Grams, error }) => ({
        name,
        path,
        status,
        bodyBytes,
        totalBytes,
        co2Grams: parseFloat(co2Grams.toFixed(6)),
        error,
      }),
    );
    console.log(JSON.stringify(output, null, 2));
    return;
  }

  const reset = "\x1b[0m";
  const bold = "\x1b[1m";
  const dim = "\x1b[2m";

  console.log("\n" + "─".repeat(80));
  console.log(
    `${bold}${"Note".padEnd(6)} ${"Page".padEnd(34)} ${"Chemin".padEnd(24)} ${"Poids".padStart(10)} ${"CO2 estimé".padStart(18)}${reset}`,
  );
  console.log("─".repeat(80));

  let totalBytes = 0;
  let totalCO2 = 0;
  let errorCount = 0;

  const sorted = [...results].sort((a, b) => b.co2Grams - a.co2Grams);

  for (const r of sorted) {
    if (r.error) {
      errorCount++;
      console.log(
        `${dim}${"ERR".padEnd(6)} ${r.name.padEnd(34)} ${r.path.padEnd(24)} ${"-".padStart(10)} ${"ERREUR".padStart(18)}  ${r.error}${reset}`,
      );
      continue;
    }

    const { label, color } = getRating(r.co2Grams);
    totalBytes += r.totalBytes;
    totalCO2 += r.co2Grams;

    const weight = formatBytes(r.bodyBytes);
    const co2 = formatCO2(r.co2Grams);
    const statusBadge = r.status === 200 ? "" : ` [${r.status}]`;

    console.log(
      `${color}${bold}${label}${reset}  ${r.name.padEnd(34)} ${(r.path + statusBadge).padEnd(24)} ${weight.padStart(10)} ${co2.padStart(18)}`,
    );

    if (VERBOSE) {
      console.log(`${dim}       URL : ${r.url}${reset}`);
    }
  }

  console.log("─".repeat(80));

  const successCount = results.length - errorCount;
  if (successCount > 0) {
    const avgCO2 = totalCO2 / successCount;
    const avgRating = getRating(avgCO2);
    console.log(
      `${bold}${"TOTAL".padEnd(6)} ${`${successCount} pages`.padEnd(34)} ${"".padEnd(24)} ${formatBytes(totalBytes).padStart(10)} ${formatCO2(totalCO2).padStart(18)}${reset}`,
    );
    console.log(
      `${bold}${"MOY.".padEnd(6)} ${"par page".padEnd(34)} ${"".padEnd(24)} ${formatBytes(Math.round(totalBytes / successCount)).padStart(10)} ${(avgRating.color + bold + formatCO2(avgCO2) + reset).padStart(18 + avgRating.color.length + bold.length + reset.length)}${reset}`,
    );
  }

  console.log("\n  Echelle de notation :");
  console.log(
    "  \x1b[32mA+ <= 0.095g  A <= 0.185g\x1b[0m  (pages très eco-responsables)",
  );
  console.log(
    "  \x1b[33mB  <= 0.340g  C <= 0.490g\x1b[0m  (pages dans la moyenne)",
  );
  console.log(
    "  \x1b[31mD  <= 0.650g  E <= 0.850g  F > 0.850g\x1b[0m  (pages à optimiser)",
  );
  console.log("\n  Source des seuils : websitecarbon.com\n");

  if (errorCount > 0) {
    console.log(
      `  \x1b[33mAvertissement : ${errorCount} page(s) inaccessible(s). Le serveur est-il démarré ?\x1b[0m\n`,
    );
  }
}

run().catch((err) => {
  console.error("Erreur fatale :", err.message);
  process.exit(1);
});
