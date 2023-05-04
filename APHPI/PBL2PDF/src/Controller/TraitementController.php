<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
require_once('/var/www/html/vendor/phpseclib/phpseclib/phpseclib/Crypt/RSA.php');
require_once('/var/www/html/vendor/phpseclib/phpseclib/phpseclib/Net/SSH2.php');
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;


class TraitementController extends AbstractController
{
    #[Route('/traitement', name: 'app_traitement')]
    public function index(Request $request): Response
    {
        $fs = new FileSystem();

        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->add('header', FileType::class, [
                'required' => false,
            ])
            ->add('footer', FileType::class, [
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $header = $form->get('header')->getData();
            $footer = $form->get('footer')->getData();


            $validExtensions = ['html', 'htm'];

            $extensionFile = $form->get('file')->getData()->getClientOriginalExtension();
            if (!in_array($extensionFile, $validExtensions)) {
                throw new \RuntimeException("Le fichier doit être un fichier HTML ou HTM. $extensionFile");
            }
            
            $extensionHeader = $form->get('header')->getData() ? $form->get('header')->getData()->getClientOriginalExtension() : null;
            if (isset($extensionHeader) && !in_array($extensionHeader, $validExtensions)) {
                throw new \RuntimeException("Le fichier de l'en-tête doit être un fichier HTML ou HTM.");
            }
            
            $extensionFooter = $form->get('footer')->getData() ? $form->get('footer')->getData()->getClientOriginalExtension() : null;
            if (isset($extensionFooter) && !in_array($extensionFooter, $validExtensions)) {
                throw new \RuntimeException("Le fichier de pied de page doit être un fichier HTML ou HTM.");
            }
            

            $fileName = uniqid() . '.html';
            $headerName = 'header.html';
            $footerName = 'footer.html';


            $file->move(
                $this->getParameter('files_directory'),
                $fileName
            );

            if($header){
                $header->move(
                    $this->getParameter('files_directory'),
                    $headerName
                );
            }else{
                $fs->copy($this->getParameter('default_file_path'),$this->getParameter('files_directory')."/$headerName");
            }
            if($footer){
                $footer->move(
                    $this->getParameter('files_directory'),
                    $footerName
                );
            }else{
                $fs->copy($this->getParameter('default_file_path'),$this->getParameter('files_directory')."/$footerName");
            }

            $this->traiterFichier($fileName, $headerName, $footerName);

            $this->addFlash('success', 'Le fichier a été téléchargé avec succès.');

            unlink($this->getParameter('files_directory') . '/' . $fileName);
            unlink($this->getParameter('files_directory') . '/' . $headerName);
            unlink($this->getParameter('files_directory') . '/' . $footerName);

            return $this->redirectToRoute('app_liste_fichiers');
        }

        return $this->render('traitement/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    

    private function traiterFichier(string $filePath, string $headerPath, string $footerPath): string
    {
        
        $ssh = new SFTP('html2pdf');
        $privateKey = RSA::load(file_get_contents('/var/www/html/id_rsa'));
        $publicKey = RSA::load(file_get_contents('/var/www/html/id_rsa.pub'));
    


        if (!$ssh->login('root', $privateKey)) {
            exit('Login Failed :' .$ssh->getLastError());
        }

        // Chemin du fichier distant et local
        $remoteDir = '/Work/Convert/';
        $localPath = $this->getParameter('files_directory')."/$filePath";

        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        // Téléversement du fichier le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$filePath", $localPath, SFTP::SOURCE_LOCAL_FILE)){
            exit("Le versement du fichier a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        $remoteDir = '/Work/Divers/';
        $localHeader = $this->getParameter('files_directory')."/$headerPath";
        $localFooter = $this->getParameter('files_directory')."/$footerPath";


        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }


        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$headerPath", $localHeader, SFTP::SOURCE_LOCAL_FILE)){
            exit("Le versement du header a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$footerPath", $localFooter, SFTP::SOURCE_LOCAL_FILE)){
            exit("Le versement du footer a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Exécution du script run.sh sur le conteneur ubuntu
        $command = '/Work/run.sh';
        if(!$ssh->exec($command)){
            exit("Problème lors de la conversion du fichier");
        }
        
        //téléchargement du pdf depuis le conteneur ubuntu
        if(!$ssh->get("/Work/Convert/$filePath.pdf",$this->getParameter('files_directory')."/$filePath.pdf")){
            exit("Impossible de télécharger le fichier");
        }

        $command = "rm /Work/Convert/$filePath.pdf";
        if($ssh->exec($command)){
            exit("Problème lors du nettoyage");
        }
        
        $ssh->disconnect();
    
        return "success";
    }


    #[Route('/fichiers', name: 'app_fichiers')]
    public function listeFichiers(): Response
    {
        $fichiers = scandir($this->getParameter('files_directory'));
        $fichiers = array_filter($fichiers, function ($fichier) {
            return $fichier !== '.' && $fichier !== '..';
        });
    
        return $this->render('traitement/liste_fichiers.html.twig', [
            'fichiers' => $fichiers,
        ]);
    }
    
    #[Route('/fichier/{nomFichier}', name: 'app_fichier')]
    public function afficherFichier(Request $request, string $nomFichier): Response
    {
        $extension = $request->query->get('extension');
        $filePath = $this->getParameter('files_directory') . '/' . $nomFichier . '.html.' . $extension;
    
        $response = new Response();
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContent(file_get_contents($filePath));
    
        return $response;
    }
    
    #[Route('/fichier/{nomFichier}/supprimer', name: 'app_supprimer_fichier', methods: ['POST'])]
    public function supprimerFichier(string $nomFichier): Response
    {
        $fichierPath = $this->getParameter('files_directory') . '/' . $nomFichier .'.html.pdf';
        if (file_exists($fichierPath)) {
            unlink($fichierPath);
            $this->addFlash('success', 'Le fichier a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du fichier.');
        }
    
        return $this->redirectToRoute('app_fichiers');
    }
    


}

