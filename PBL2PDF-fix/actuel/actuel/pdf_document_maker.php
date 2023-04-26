<?php
/**
 * @version 21060802.0.1
 * @date 2016
 * @author  Killian KOPP <killiankopp@gmail.com>
 *
 * rapport
 *
 * @bug
 * - aucun
 *
 * @warning
 * - aucun
 *
 * @todo
 * - aucun
 *
 */

if(!defined('MODULE'))
{
    // d�finition des constantes
    define("DEBUG_MODE",        'NON');
    define("RACINE_VERSION",    '../../../../');
    define("RACINE_MODULE",     '../../');
    define("PPP",               'private');
    define("MODULE",	    	'document');

    // inclusion du "haut"
    require_once RACINE_VERSION.'common/php/includes/haut3.php';
} // fin de si pas déjà défini

if($_REQUEST['document_modele_id'])
{
    $oObjet = new DocumentModele($_REQUEST['document_modele_id']);
} // fin de si modele
else
{
    $document_maker_id = $_REQUEST['document_maker_id'] ? $_REQUEST['document_maker_id'] : $document_maker_id;
    $oObjet = new DocumentMaker($document_maker_id);
} // fin de si modele

$oObjet->loadObject(array('name' => 'tout'));

$oModele    = get_class($oObjet) == 'DocumentMaker' ? $oObjet->oModele : $oObjet;
$oMaker     = get_class($oObjet) == 'DocumentMaker' ? $oObjet : '';

if(!function_exists('tagger'))
{
    function tagger($oDocument, $oModele, $oMaker = '')
    {
        $nom = $oModele->getOrganized_name();

        $orientation = $oModele->getPapier_paysage() == 'OUI' ? 'L' : 'P';
        $format = $oModele->getPapier_format() != 'A4' ? $oModele->getPapier_format() : 'A4';

        if(is_object($oMaker))
        {
            $orientation = $oMaker->getPapier_paysage() == 'OUI' ? 'L' : 'P';
            $format = $oMaker->getPapier_format() != 'A4' ? $oMaker->getPapier_format() : 'A4';

            $tlc = $oMaker->getTlc();
            $tli = $oMaker->getTli();
            $dossier = $oMaker->getDossier();

            // si on a de quoi faire, on va remplacer les variables avec l'objet passé
            if($tlc && $tli)
            {
                $oTlc = new $tlc($tli);
                $oTlc->loadObject(array('name' => 'tout'));

                $oTpl = new Template();
                $oTpl->setHtml($nom);
                $oTpl = $oTlc->template_replace($oTpl);
                $nom = $oTpl->compiler();

                $oTpl = new Template();
                $oTpl->setHtml($dossier);
                $oTpl = $oTlc->template_replace($oTpl);
                $dossier = $oTpl->compiler();
            } // fin de si tlc et tli
        } // fin de si objet maker

        $oDocument->tagger(
            [
                'position' 					=> $oModele->getQrcode_position(),
                'type_document'				=> $oModele->getType_document(),
                'document_id__original' 	=> $oDocument->getId(),
                'to_sign' 					=> $oModele->getOrganized_to_sign(),
                'to_check' 					=> $oModele->getOrganized_to_check(),
                'nom' 					    => $nom,
                'tlc'                       => $tlc,
                'tli'                       => $tli,
                'dossier'                   => $dossier,
                'callback'					=> $oModele->getOrganized_callback(),
                'orientation'               => $orientation,
                'format'                    => $format
            ]
        );
        if($oModele->getOrganized_to_sign() == 'OUI')
        {
            $oDocument->SetTo_sign_original('OUI');
        } // fin de si le document est à signer
        $oDocument->create_url();
        $oDocument->record();

        if($oModele->getTamponner())
        {
            $oDocument->tamponner(
                [
                    'structure_id' => $oModele->getTamponner(),
                ]
            );
        } // fin de si tamponner

        if($oModele->getParapher())
        {
            $oDocument->parapher(
                [
                    'login_id' => $oModele->getParapher(),
                ]
            );
        } // fin de si parapher

        if($oModele->getSigner())
        {
            $oDocument->signer(
                [
                    'login_id' => $oModele->getSigner(),
                ]
            );
        } // fin de si signer

        return $oDocument;
    } // fin de la fonction tagger
} // fin de si la fonction n'existe pas

if(MKGDocument::getConfigValue('MODULE_DOCUMENT_GENERATEUR') == 'html')
{

    $file = $oObjet->getFileLink();
    $file_convert = preg_replace('/\.htm$/', '', $file);
    $file_convert .= '-convert.htm';

    // Vérification de l'existence d'un fichier à jour pour le preview
    if(file_exists($file_convert.'.pdf') && $_REQUEST['action'] != 'generer' && $action != 'generer')
    {

        $ob_dm = $oObjet->getDm("Y-m-d H:i:s");
        $file_dm = date("Y-m-d H:i:s", filemtime($file_convert.'.pdf'));

        if ($file_dm < $ob_dm) {
            $generate = true;
        } else {
            $generate = false;
        }

    } // fin de si fichier existe
    else
    {
        $generate = true;
    } // fin de else de si fichier existe


    if ($generate) {

        // Compilation du code HTML
        $file_content = fctFileLoad($file);

        // Document configuration
        // Convert cm to mm
        $mt = MKGDocument::getConfigValue('MODULE_DOCUMENT_MARGE_HAUTE') * 10;
        $mr = MKGDocument::getConfigValue('MODULE_DOCUMENT_MARGE_DROITE') * 10;
        $mb = MKGDocument::getConfigValue('MODULE_DOCUMENT_MARGE_BASSE') * 10;
        $ml = MKGDocument::getConfigValue('MODULE_DOCUMENT_MARGE_GAUCHE') * 10;

        /**
         * Now we use Spipu Html2Pdf library to generate PDF
         * However, the following source should goes to classe_document_maker generer() method.
         */

        $header = '';

        if(is_object($oObjet->oEntete))
        {
            $header = $oObjet->oEntete->getContenu();
        } // fin de si objet

        // Pied de page
        if ($oObjet->oPied->getId()) {
            $footer = $oObjet->oPied->getContenu();
        } else {
            if(MKGDocument::getConfigValue('MODULE_DOCUMENT_AFFICHER_MENTIONS_BASSES') != 'NON')
            {

                if ($oObjet->getStructure()) {

                    $oObjet->oStructure->loadObject([
                        'name' => 'personne'
                    ]);
                    $o = $oObjet->oStructure->oPersonne;
                    $o->loadObject([
                        'name' => 'tout'
                    ]);
                    $footer = $o->getNom().' - '.$o->getSiret().'<br>';
                    $footer .= $o->oAdresse->getVoie()." ".$o->oAdresse->getCp()." ".$o->oAdresse->getLocalite().'<br>';
                    $footer .= "Tel : ".$o->oTel1->getNumero()." - Mail : ".$o->oMail1->getAdresse()." - Web : ".$o->oUrl1->getUrl();

                } else {

                    $footer = STRUCTURE_NOM_COMPLET.' - '.STRUCTURE_FORME_JURIDIQUE.' - '.STRUCTURE_SIRET.'<br>';
                    $footer .= STRUCTURE_ADRESSE_VOIE." ".STRUCTURE_ADRESSE_CP." ".STRUCTURE_ADRESSE_LOCALITE.'<br>';
                    $footer .= "Tel : ".STRUCTURE_TEL." - Fax : ".STRUCTURE_FAX." - Mail : ".STRUCTURE_MAIL." - Web : ".STRUCTURE_SITE_URL;

                    if(DECLARATION_ACTIVITE != 'DECLARATION_ACTIVITE' && DECLARATION_ACTIVITE != '')
                    {
                        $footer .= '<br>'.DECLARATION_ACTIVITE;
                    }

                }

            } // fin de si ne pas afficher les mentions basses
            else {
                $footer = '';
            }
        }

        if ($header) {
            $header = '<page_header><div class="header-page">'.$header.'</div></page_header>';
        }
        if ($footer) {
            $footer = '<page_footer><div class="footer-page">'.$footer.'</div></page_footer>';
        }

        /**
         * In the following, we divide body in pages and add header and footer to each page groups.
         */
        $body = explode('<p>SAUT_DE_PAGE</p>', $file_content);
        $length = count($body);

        if (is_a($oModele, 'DocumentModele')) {

            $marges_label = ['top' => 25, 'bottom' => 25, 'left' => 10, 'right' => 10];
            $back = [];

            foreach ($marges_label as $label => $val) {
                $get = 'getBack'.$label;

                if ($oModele->$get() === null) {
                    $back[$label] = $val;
                }
                else {
                    $back[$label] = $oModele->$get() * 10;
                }
            }
        }
        else {
            $back = [25,25,10,10];
        }

        for ($i=0; $i<$length; $i++) {
            $body[$i] = '<page
                backtop="'.$back['top'].'mm" backbottom="'.$back['bottom'].'mm" backleft="'.$back['left'].'mm" backright="'.$back['right'].'mm">'.$header.$footer.$body[$i].'</page>';
        }

        $body = implode('', $body);

        // Création du fichier intermédiaire de convertion
        fctFileWrite($file_convert, $body);
        // Fin du fichier intermédiaire de convertion

        $page_format = $oObjet->getPapier_format() ?? 'A4';
        $page_orientation = $oObjet->getPapier_paysage() == 'OUI' ? 'L' : 'P';

        $pdf_file = $file_convert.'.pdf';
        $pdf_file = str_replace('../', '', $pdf_file);
        $pdf_file = $_SERVER['DOCUMENT_ROOT'].'/'.$pdf_file;
        $pdf_file = str_replace('mkg/data', 'data', $pdf_file);

        // Call html2pdf class to make convertion
vdl($file_convert);
        try {
            $pdf = new \Spipu\Html2Pdf\Html2Pdf($page_orientation,$page_format,'fr', true, 'UTF-8', array($ml, $mt, $mr, $mb)); 
            $pdf->writeHTML($body);
            //$pdf->output();
            $pdf->output($pdf_file, 'F');
        }
        catch (Exception $e) {
            echo "<div>Nous avons rencontré une erreur dans la création du PDF. Le générateur HTML2PDF à retourné l'erreur suivante :</div>";
            echo "<div style=\"color:#C70039;\"><em>".$e->getMessage()."</em></div>";
            die();
        }

        if($_REQUEST['document_modele_id'])
        {
            copy(RACINE_VERSION.'..'.$pdf_file, $oObjet->getTempPDFChemin());
            $oObjet->creer_miniature();
        } // fin de si modele
    }

    $file_convert .= '.pdf';

    // récupération de la date de modification
	$fichier_date = gmdate("D, d M Y H:i:s", @filemtime($file_convert))." GMT";

    // récupération du poids du fichier
	$fichier_poids = @filesize($file_convert);

	if(!$fichier_poids){
		$contenu .= 'fichier vide (chemin : '.$file_convert.')';
		fctHTMLAffichage3();
		exit;
	} // fin de si le fichier est illisible

    if($_REQUEST['action'] == 'generer' || $action == 'generer')
    {
        if(is_object($oMaker))
        {
            $tdi = $oMaker->getDocument_id__cible();
        } // fin de si maker

        $oDocument = new MKGDocument($tdi);
        $oDocument->setLiaison($oObjet->getTlc(), $oObjet->getTli());
        $oDocument->setDossier($oObjet->getDossier());
        $oDocument->record();

        fctFileMove($file_convert, RACINE_VERSION.'../data/'.$oObjet->getDossier().'/'.$oDocument->getId().'.pdf');

        if(is_object($oObjet->oTypeDocument))
        {
            $oDocument->setType_document($oObjet->oTypeDocument->getId());
        } // fin de si on un un type de document

        $oDocument->setType('pdf');
        $oDocument->determinerPoids();
        $oDocument->setNom($oObjet->getNom());
        $oDocument->record();
        $oDocument = tagger($oDocument, $oModele, $oMaker);

        if($noretour != 'OUI')
        {
            header('Location: document_3_fiche.php?document_id='.$oDocument->getId());
        } // fin de si pas de noretour
    }
    else
    {
        // envoi des headers et affichage du fichier
        header("HTTP/1.0 200 OK");
        header("Pragma: private");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Type: application/pdf');
        header("Content-Length: ".$fichier_poids);
        header("Last-Modified: ".$fichier_date);
        header('Content-Disposition: inline; filename="'.$oObjet->getNom().'.pdf"');
        readfile($file_convert);
    }
} // fin de si générateur html
else
{
    require(RACINE_VERSION.'thirdparty/html2pdf/html2pdf.php');

    // si noTemplate est � OUI : alors pas de template pour utiliser du papier � lettre par exemple
    $noTemplate = $_GET["nt"];
    if($noTemplate != 'OUI') { $noTemplate = 'NON'; }

    global $PdfDocumentMaker__declaration;

    if(!$PdfDocumentMaker__declaration)
    {
        // pour ne pas redéclarer une classe qui existerai déjà
        $PdfDocumentMaker__declaration = 'done';

        class PdfDocumentMaker extends PDF_HTML {
            function Header() {
        		if(MODULE_DOCUMENT_FOND != '' && MODULE_DOCUMENT_FOND != "MODULE_DOCUMENT_FOND")
        		{
        			$this->Image(MODULE_DOCUMENT_FOND,0,0, 210, 297);
        		} // fin de si il y a un fond

        		if(MODULE_DOCUMENT_LOGO != '' && MODULE_DOCUMENT_LOGO != "MODULE_DOCUMENT_LOGO" && MODULE_DOCUMENT_AFFICHER_LOGO != 'NON')
        		{
        			$this->Image(MODULE_DOCUMENT_LOGO,10,10,-300);
        		} // fin de si le logo existe
        	} // fin de la fonction header

        	function Footer() {
        		if(MODULE_DOCUMENT_AFFICHER_MENTIONS_BASSES != 'NON')
        		{
        			$this->SetY(-35);

        			$this->SetTextColor(127);
        			$this->SetFont('Arial','',8);

        			$this->Cell(0,10,''.STRUCTURE_NOM_COMPLET.' - '.STRUCTURE_FORME_JURIDIQUE.' - '.STRUCTURE_SIRET.'',0,0,'C');
        			$this->Ln(5);

        			$this->Cell(0,10,"".STRUCTURE_ADRESSE_VOIE." ".STRUCTURE_ADRESSE_CP." ".STRUCTURE_ADRESSE_LOCALITE."",0,0,'C');
        			$this->Ln(5);

        			$this->Cell(0,10,"Tel : ".STRUCTURE_TEL." - Fax : ".STRUCTURE_FAX." - Mail : ".STRUCTURE_MAIL." - Web : ".STRUCTURE_SITE_URL."",0,0,'C');
        			$this->Ln(5);

        			if(DECLARATION_ACTIVITE != 'DECLARATION_ACTIVITE' && DECLARATION_ACTIVITE != '')
        			{
        				$this->Cell(0,10,utf8_decode(DECLARATION_ACTIVITE),0,0,'C');
        				$this->Ln(10);
        			}
        		} // fin de si ne pas afficher les mentions basses

        		$this->SetFont('Arial','B',12);
        		$this->SetY(-16);
        		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'R');
        	} // fin de la fonction footer
        } // fin de class PDF extends FPDF
    } // fin de si pas ddéjà déclarée


    //require_once RACINE_VERSION.'thirdparty/fpdf_thirdparty/code39.php';

    $margeV = 40;

    //Instanciation de la classe d�riv�e
    //$pdf=new PDF();
    $pdf = new PdfDocumentMaker();
    $pdf->SetMargins(15,$margeV);
    $pdf->SetAutoPageBreak(1,$margeV);
    $pdf->AliasNbPages();

    $orientation = $oObjet->getPapier_paysage() == 'OUI' ? 'L' : 'P';
    $format = $oObjet->getPapier_format() != 'A4' ? $oObjet->getPapier_format() : 'A4';

    $pdf->AddPage($orientation, $format);
    $pdf->SetFont('Arial','',10);

    $file = $oObjet->getFileLink();
    $texte = fctFileLoad($file);

    $pages = explode("SAUT_DE_PAGE", $texte);
    $nb_pages = count($pages);

    $i = 1;

    foreach ($pages as $texte_page)
    {
        $pdf->WriteHTML($texte_page);
        if($i < $nb_pages)
        {
            $pdf->AddPage();
        } // fin de si ce n'est pas la dernière page
        $i++;
    } // fin de lecture des page

    $nom_fichier = $_REQUEST["document_nom"];

    // on se créé un beau nom pour le fichier
    if(!$nom_fichier)
    {
        $t  = '';
        $t .= fctStringLeonTotal($oObjet->getNom());
        $t .= '.pdf';
    } // fin de si pas de nom de fihcier
    else
    {
        $t = $nom_fichier;
    } // fin de sle de si pas de nom de fichier

    if($_REQUEST['action'] == 'generer' || $action == 'generer')
    {
        $oDocument = new MKGDocument();
        $oDocument->setLiaison($oObjet->getTlc(), $oObjet->getTli());
        $oDocument->setDossier($oObjet->getDossier());
        $oDocument->record();

        $pdf->Output(RACINE_VERSION.'../data/'.$oObjet->getDossier().'/'.$oDocument->getId().'.pdf', 'F');

        if(is_object($oObjet->oTypeDocument))
        {
            $oDocument->setType_document($oObjet->oTypeDocument->getId());
        } // fin de si on un un type de document

        $oDocument->setType('pdf');
        $oDocument->determinerPoids();
        if($oObjet->getNom())
        {
            $oDocument->setNom($oObjet->getNom());
        } // fin de si nom
        $oDocument->record();
        $oDocument = tagger($oDocument, $oModele, $oMaker);

        if($noretour != 'OUI')
        {
            header('Location: document_3_fiche.php?document_id='.$oDocument->getId());
        } // fin de si pas de noretour
    }
    else
    {
        $d = 'I';
        $pdf->Output($t, $d);

        if($_REQUEST['document_modele_id'])
        {
            $pdf->Output($oObjet->getTempPDFChemin(), 'F');
            $oObjet->creer_miniature();
        } // fin de si modele
    }
} // fin de else de si générateur html
?>
