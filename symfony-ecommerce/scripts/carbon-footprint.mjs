/**
 * Mesure l'empreinte carbone de chaque page du site.
 *
 * Usage :
 *   node scripts/carbon-footprint.mjs [--base-url http://localhost:8000] [--report]
 *
 * Méthodologie :
 *   - Charge chaque page HTML via fetch
 *   - Extrait et charge toutes les ressources liées (CSS, JS, images)
 *   - Additionne les octets transférés
 *   - Calcule les émissions CO2 via @tgwf/co2 (modèle Sustainable Web Design)
 */

import co2pkg from "@tgwf/co2";
const { co2 } = co2pkg;
import { writeFileSync } from "fs";
import { resolve, dirname } from "path";
import { fileURLToPath } from "url";

const __dirname = dirname(fileURLToPath(import.meta.url));

// --- Configuration ---
const args = process.argv.slice(2);
const BASE_URL = (() => {
  const i = args.indexOf("--base-url");
  return i !== -1 ? args[i + 1] : "http://localhost:8000";
})();
const GENERATE_REPORT = args.includes("--report");

const PAGES = [
  { name: "Accueil", path: "/" },
  { name: "À propos", path: "/about" },
  { name: "Contact", path: "/contact" },
  { name: "Connexion", path: "/login" },
  { name: "Inscription", path: "/register" },
  { name: "Panier", path: "/cart" },
  { name: "Recherche produits", path: "/product/search" },
  { name: "Historique commandes", path: "/historique" },
  { name: "Profil", path: "/profile/" },
  { name: "Mes commandes", path: "/mes-commandes/" },
];

const swd = new co2({ model: "swd" });

// --- Utilitaires ---
function formatBytes(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function formatCO2(grams) {
  if (grams < 1) return `${(grams * 1000).toFixed(2)} mg CO2`;
  return `${grams.toFixed(4)} g CO2`;
}

function getCO2Rating(grams) {
  // Seuils basés sur les standards du Web Sustainability (0.5g = note C médiane)
  if (grams < 0.095) return { grade: "A+", color: "\x1b[32m" };
  if (grams < 0.186) return { grade: "A", color: "\x1b[32m" };
  if (grams < 0.341) return { grade: "B", color: "\x1b[33m" };
  if (grams < 0.493) return { grade: "C", color: "\x1b[33m" };
  if (grams < 0.656) return { grade: "D", color: "\x1b[31m" };
  if (grams < 1.0) return { grade: "E", color: "\x1b[31m" };
  return { grade: "F", color: "\x1b[31m" };
}

async function fetchWithSize(url) {
  const response = await fetch(url, {
    headers: {
      "User-Agent": "CarbonAudit/1.0 (carbon-footprint-script)",
      "Accept-Encoding": "gzip, deflate, br",
    },
    redirect: "follow",
  });

  const buffer = await response.arrayBuffer();
  const bytes = buffer.byteLength;
  const contentType = response.headers.get("content-type") || "";
  return { bytes, status: response.status, contentType, body: Buffer.from(buffer).toString("utf-8") };
}

function extractResources(html, pageUrl) {
  const base = new URL(pageUrl);
  const resources = new Set();

  const patterns = [
    // CSS
    /<link[^>]+rel=["']stylesheet["'][^>]+href=["']([^"']+)["']/gi,
    /<link[^>]+href=["']([^"']+)["'][^>]+rel=["']stylesheet["']/gi,
    // JS
    /<script[^>]+src=["']([^"']+)["']/gi,
    // Images
    /<img[^>]+src=["']([^"']+)["']/gi,
    // Fonts via link preload
    /<link[^>]+href=["']([^"']+\.(woff2?|ttf|eot))["']/gi,
  ];

  for (const pattern of patterns) {
    let match;
    while ((match = pattern.exec(html)) !== null) {
      const href = match[1];
      if (!href || href.startsWith("data:") || href.startsWith("//")) continue;
      try {
        const resourceUrl = new URL(href, base).toString();
        // Seulement les ressources du même domaine
        if (new URL(resourceUrl).origin === base.origin) {
          resources.add(resourceUrl);
        }
      } catch {
        // URL invalide, on ignore
      }
    }
  }

  return [...resources];
}

async function measurePage(page) {
  const url = `${BASE_URL}${page.path}`;
  console.log(`  Chargement : ${url}`);

  let totalBytes = 0;
  const resourceDetails = [];

  try {
    // Charge la page HTML
    const { bytes, status, body } = await fetchWithSize(url);

    if (status >= 400) {
      return { ...page, error: `HTTP ${status}`, totalBytes: 0, co2: 0, resources: [] };
    }

    totalBytes += bytes;
    resourceDetails.push({ type: "HTML", url, bytes });

    // Extrait et charge les ressources liées
    const resourceUrls = extractResources(body, url);

    await Promise.allSettled(
      resourceUrls.map(async (resourceUrl) => {
        try {
          const res = await fetchWithSize(resourceUrl);
          totalBytes += res.bytes;
          const type = res.contentType.includes("css")
            ? "CSS"
            : res.contentType.includes("javascript")
            ? "JS"
            : res.contentType.includes("image")
            ? "Image"
            : res.contentType.includes("font")
            ? "Font"
            : "Autre";
          resourceDetails.push({ type, url: resourceUrl, bytes: res.bytes });
        } catch {
          // Ressource non chargeable, on ignore
        }
      })
    );

    const co2grams = swd.perByte(totalBytes, true); // true = hébergement vert

    return {
      ...page,
      url,
      totalBytes,
      co2: co2grams,
      resources: resourceDetails,
      error: null,
    };
  } catch (err) {
    return {
      ...page,
      url,
      error: err.message,
      totalBytes: 0,
      co2: 0,
      resources: [],
    };
  }
}

function printResults(results) {
  const reset = "\x1b[0m";
  const bold = "\x1b[1m";
  const dim = "\x1b[2m";

  console.log("\n" + "=".repeat(72));
  console.log(`${bold}  Rapport d'empreinte carbone — ${BASE_URL}${reset}`);
  console.log("=".repeat(72));

  const successful = results.filter((r) => !r.error);
  const failed = results.filter((r) => r.error);

  // Tableau des résultats
  console.log(
    `\n  ${"Page".padEnd(28)} ${"Taille".padStart(10)} ${"CO2".padStart(16)} ${"Note".padStart(5)}`
  );
  console.log("  " + "-".repeat(65));

  for (const r of results) {
    if (r.error) {
      console.log(
        `  ${r.name.padEnd(28)} ${"[ERREUR]".padStart(10)} ${dim}${r.error}${reset}`
      );
      continue;
    }

    const { grade, color } = getCO2Rating(r.co2);
    console.log(
      `  ${r.name.padEnd(28)} ${formatBytes(r.totalBytes).padStart(10)} ${formatCO2(r.co2).padStart(16)} ${color}${bold}${grade.padStart(5)}${reset}`
    );
  }

  if (successful.length > 0) {
    const totalBytes = successful.reduce((s, r) => s + r.totalBytes, 0);
    const totalCO2 = successful.reduce((s, r) => s + r.co2, 0);
    const avgCO2 = totalCO2 / successful.length;
    const { grade, color } = getCO2Rating(avgCO2);

    console.log("\n  " + "-".repeat(65));
    console.log(
      `  ${"MOYENNE".padEnd(28)} ${formatBytes(totalBytes / successful.length).padStart(10)} ${formatCO2(avgCO2).padStart(16)} ${color}${bold}${grade.padStart(5)}${reset}`
    );

    console.log("\n" + "=".repeat(72));
    console.log(`${bold}  Equivalent annuel (50 000 visites/mois par page)${reset}`);
    console.log("=".repeat(72));

    const visitsPerMonth = 50000;
    const annualVisits = visitsPerMonth * 12;

    for (const r of successful) {
      const annualCO2kg = (r.co2 * annualVisits) / 1000;
      console.log(
        `  ${r.name.padEnd(28)} ${(annualCO2kg.toFixed(3) + " kg CO2/an").padStart(20)}`
      );
    }

    console.log("\n" + "=".repeat(72));
    console.log(`${bold}  Notation (seuils websitecarbon.com)${reset}`);
    console.log("=".repeat(72));
    const grades = [
      { grade: "A+", max: 0.095 },
      { grade: "A", max: 0.186 },
      { grade: "B", max: 0.341 },
      { grade: "C", max: 0.493 },
      { grade: "D", max: 0.656 },
      { grade: "E", max: 1.0 },
      { grade: "F", max: Infinity },
    ];
    for (const g of grades) {
      console.log(
        `  ${g.grade.padEnd(4)} < ${g.max === Infinity ? "∞" : formatCO2(g.max).padEnd(14)}`
      );
    }
  }

  if (failed.length > 0) {
    console.log(`\n${dim}  ${failed.length} page(s) inaccessible(s) — le serveur est-il démarré ?${reset}`);
  }

  console.log("");
}

function generateJSONReport(results) {
  const report = {
    date: new Date().toISOString(),
    baseUrl: BASE_URL,
    model: "Sustainable Web Design (SWD)",
    pages: results.map((r) => ({
      name: r.name,
      path: r.path,
      url: r.url,
      error: r.error || null,
      totalBytes: r.totalBytes,
      totalKB: parseFloat((r.totalBytes / 1024).toFixed(2)),
      co2Grams: parseFloat(r.co2.toFixed(6)),
      grade: r.error ? null : getCO2Rating(r.co2).grade,
      resources: r.resources?.map((res) => ({
        type: res.type,
        bytes: res.bytes,
        url: res.url,
      })),
    })),
  };

  const outPath = resolve(__dirname, "../var/carbon-report.json");
  writeFileSync(outPath, JSON.stringify(report, null, 2));
  console.log(`  Rapport JSON enregistré : var/carbon-report.json`);
  return outPath;
}

// --- Main ---
async function main() {
  console.log(`\nMesure de l'empreinte carbone — modele Sustainable Web Design`);
  console.log(`Serveur cible : ${BASE_URL}\n`);

  const results = [];

  for (const page of PAGES) {
    process.stdout.write(`  [${(results.length + 1).toString().padStart(2)}/${PAGES.length}] ${page.name}...\n`);
    const result = await measurePage(page);
    results.push(result);
  }

  printResults(results);

  if (GENERATE_REPORT) {
    generateJSONReport(results);
  }
}

main().catch((err) => {
  console.error("Erreur fatale :", err.message);
  process.exit(1);
});
