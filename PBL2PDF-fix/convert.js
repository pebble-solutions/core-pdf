const puppeteer = require('puppeteer');

async function convertToPDF(filePath, outputFileName, headerFilePath, footerFilePath) {
  const browser = await puppeteer.launch({ args: ['--no-sandbox'] });
  const page = await browser.newPage();
  const fileUrl = `file://${filePath}`;
  await page.goto(fileUrl);


  const marginInches = {
    top: 2,
    bottom: 1.5,
    left: 1,
    right: 1
  };

  // Convertir les marges en pixels
  const marginPixels = {
    top: marginInches.top * 96,
    bottom: marginInches.bottom * 96,
    left: marginInches.left * 96,
    right: marginInches.right * 96
  };


  // Charger le contenu des fichiers d'en-tête et de pied de page
  const headerContent = await loadFileContents(headerFilePath);
  const footerContent = await loadFileContents(footerFilePath);

  // Configuration de l'en-tête et du pied de page
  const headerTemplate = `<header style="padding-left: 20px;">${headerContent}</header>`;
  const footerTemplate = `<footer style="padding-left: 20px;">${footerContent}</footer>`;


  // Génération du PDF avec en-tête et pied de page
  await page.pdf({
    printBackground: true,
    path: outputFileName,
    displayHeaderFooter: true,
    headerTemplate,
    footerTemplate,
    margin: {
      top: `${marginPixels.top}px`,
      bottom: `${marginPixels.bottom}px`,
      left: `${marginPixels.left}px`,
      right: `${marginPixels.right}px`
    }
  });

  await browser.close();

  console.log('Conversion terminée. Le fichier PDF a été généré :', outputFileName);
}

// Fonction pour charger le contenu d'un fichier
async function loadFileContents(filePath) {
  const fs = require('fs');
  return fs.promises.readFile(filePath, 'utf-8');
}

// Récupérer les arguments passés lors de l'appel du script
const args = process.argv.slice(2);

// Vérifier s'il y a suffisamment d'arguments
if (args.length < 4) {
  console.error('Veuillez spécifier le chemin du fichier HTML, le nom du fichier de sortie, le chemin du fichier d\'en-tête et le chemin du fichier de pied de page.');
  process.exit(1);
}

// Obtenez les chemins d'accès des fichiers à partir des arguments
const filePath = args[0];
const outputFileName = args[1];
const headerFilePath = args[2];
const footerFilePath = args[3];

// Appel de la fonction de conversion avec les paramètres fournis
convertToPDF(filePath, outputFileName, headerFilePath, footerFilePath);