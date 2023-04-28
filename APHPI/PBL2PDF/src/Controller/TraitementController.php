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
        $remotePath = '/Work/Convert/file.html';
        $localPath = $this->getParameter('files_directory').'./file.html';

        // Téléchargement du fichier
        $ssh->put($remotePath, $localPath);

        // Exécution de la commande dans le conteneur Ubuntu
        $command = 'cd /Work && sh run.sh';
        $result = $ssh->exec($command);
    
        $ssh->get($this->getParameter('files_directory').'./file.html.pdf','/Work/Convert/file.html.pdf');

        $ssh->disconnect();
    
        return $result;
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
        $contenu = file_get_contents($this->getParameter('files_directory') . '/' . $nomFichier . '.' . $extension);
        return new Response($contenu);
    }
}

