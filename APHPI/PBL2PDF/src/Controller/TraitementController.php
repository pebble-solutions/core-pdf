<?php

namespace App\Controller;


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
        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $fileName = uniqid() . '.' . $file->guessExtension();

            $file->move(
                $this->getParameter('files_directory'),
                $fileName
            );

            // TODO: Appel à la méthode de traitement du fichier
            $this->traiterFichier($fileName);

            $this->addFlash('success', 'Le fichier a été téléchargé avec succès.');

            unlink($this->getParameter('files_directory') . '/' . $fileName);

            return $this->redirectToRoute('app_liste_fichiers');
        }

        return $this->render('traitement/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function traiterFichier(string $filePath): string
    {
        
        $ssh = new SFTP('172.19.0.3');
        $privateKey = RSA::load(file_get_contents('/var/www/html/id_rsa'));
        $publicKey = RSA::load(file_get_contents('/var/www/html/id_rsa.pub'));
    


        if (!$ssh->login('root', $privateKey)) {
            exit('Login Failed');
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
        $contenu = file_get_contents($this->getParameter('files_directory') . '/' . $nomFichier . '.html.' . $extension);
        return new Response($contenu);
    }
}

